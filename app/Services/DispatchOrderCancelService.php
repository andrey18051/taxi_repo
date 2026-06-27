<?php

namespace App\Services;

use App\Http\Controllers\AndroidTestOSMController;
use App\Http\Controllers\MemoryOrderChangeController;
use App\Http\Controllers\MessageSentController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Jobs\DispatchOrderCancelRetryJob;
use App\Models\Orderweb;
use App\Support\DispatchOrderCancelSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Единая кампания отмены заказа на диспетчере (нал, безнал, вилка).
 *
 * Расписание повторов — {@see DispatchOrderCancelSchedule}.
 * Остановка только когда все ноги в архиве / close_reason=1 на диспетчере.
 * На 10-й минуте без успеха — Telegram, кампания не прекращается.
 */
class DispatchOrderCancelService
{
    public const CACHE_PREFIX = 'dispatch_cancel_campaign:';

    public const CACHE_INDEX_KEY = 'dispatch_cancel_campaign_index';

    /** Horizon слушает high/medium/low, не default — см. config/horizon.php */
    public const RETRY_JOB_QUEUE = 'low';

    public const CLIENT_MESSAGE_PENDING = 'Запит на скасування замовлення надіслано. Очікуємо підтвердження диспетчера.';

    public const ADD_COST_RECREATION_WAIT_SECONDS = 60;

    /**
     * @param array{
     *     primary_uid: string,
     *     city: string,
     *     application: string,
     *     payment_type?: string|null,
     *     resolve_uid_mapping?: bool,
     *     legs?: array<int, array{uid: string, auth_role?: string, authorization?: string}>|null
     * } $options
     *
     * @return array{
     *     dispatch_cancelled: bool,
     *     client_message: string,
     *     cancel_result_code: int|null,
     *     background_retry: bool
     * }
     */
    public function requestClientCancel(array $options): array
    {
        $rawPrimaryUid = $options['primary_uid'];
        $resolveUidMapping = $options['resolve_uid_mapping'] ?? true;
        $primaryUid = $this->resolveCancelLegUid($rawPrimaryUid, $resolveUidMapping);
        $orderwebLookupUid = $resolveUidMapping
            ? $primaryUid
            : (new MemoryOrderChangeController)->show($rawPrimaryUid);
        $orderweb = Orderweb::where('dispatching_order_uid', $orderwebLookupUid)->first();
        if (!$orderweb) {
            return $this->failureResult(null, 'Замовлення не знайдено.');
        }

        if ($this->isOrderwebAlreadyFinished($orderweb)) {
            return [
                'dispatch_cancelled' => true,
                'client_message' => 'Замовлення вже скасоване.',
                'cancel_result_code' => null,
                'background_retry' => false,
            ];
        }

        if ($orderweb->server === 'my_server_api') {
            (new AndroidTestOSMController())->finalizeDispatchClientCancel(
                $orderweb,
                $primaryUid,
                'Отмена my_server_api (без диспетчера)'
            );

            return [
                'dispatch_cancelled' => true,
                'client_message' => 'Замовлення скасоване.',
                'cancel_result_code' => null,
                'background_retry' => false,
            ];
        }

        $city = $this->normalizeCity($options['city']);
        $application = $options['application'];
        $paymentType = $options['payment_type'] ?? $orderweb->pay_system;
        $legs = $options['legs'] ?? [
            ['uid' => $resolveUidMapping ? $primaryUid : $rawPrimaryUid, 'auth_role' => 'default'],
        ];

        $this->touchCampaignState($primaryUid, $city, $application, $paymentType, $legs, $resolveUidMapping);

        $pass = $this->runCancelPass(
            $orderweb,
            $city,
            $application,
            $paymentType,
            $legs,
            1,
            $resolveUidMapping
        );
        if ($pass['all_settled']) {
            return $this->handleSettledPass($orderweb, $primaryUid, $city, $application, $paymentType, $legs, $pass);
        }

        $pass = $this->runSyncSecondAttempt(
            $orderweb,
            $primaryUid,
            $city,
            $application,
            $paymentType,
            $legs,
            $pass,
            $resolveUidMapping
        );
        if ($pass['all_settled']) {
            return $this->handleSettledPass($orderweb, $primaryUid, $city, $application, $paymentType, $legs, $pass);
        }

        $this->scheduleBackgroundCampaign(
            $primaryUid,
            $city,
            $application,
            $paymentType,
            $legs,
            $pass['attempt_number'],
            $resolveUidMapping
        );

        return [
            'dispatch_cancelled' => false,
            'client_message' => self::CLIENT_MESSAGE_PENDING,
            'cancel_result_code' => $pass['cancel_result_code'],
            'background_retry' => true,
        ];
    }

    /**
     * Синхронное ожидание архивации старых UID перед пересозданием заказа при доплате.
     * Не финализирует отмену в orderweb — только подтверждает close_reason=1 / archive на диспетчере.
     *
     * @param array{
     *     primary_uid: string,
     *     city: string,
     *     application: string,
     *     payment_type?: string|null,
     *     resolve_uid_mapping?: bool,
     *     legs?: array<int, array{uid: string, auth_role?: string, authorization?: string}>|null,
     *     wait_seconds?: int
     * } $options
     *
     * @return array{ready: bool, snapshots: array, cancel_result_code: int|null}
     */
    public function waitForAddCostRecreationCancel(array $options): array
    {
        $rawPrimaryUid = (string) $options['primary_uid'];
        $resolveUidMapping = (bool) ($options['resolve_uid_mapping'] ?? false);
        $primaryUid = $this->resolveCancelLegUid($rawPrimaryUid, $resolveUidMapping);
        $orderwebLookupUid = $resolveUidMapping
            ? $primaryUid
            : (new MemoryOrderChangeController)->show($rawPrimaryUid);
        $orderweb = Orderweb::where('dispatching_order_uid', $orderwebLookupUid)->first();
        if (!$orderweb) {
            return ['ready' => false, 'snapshots' => [], 'cancel_result_code' => null];
        }

        if ($orderweb->server === 'my_server_api') {
            return ['ready' => true, 'snapshots' => [], 'cancel_result_code' => null];
        }

        $city = $this->normalizeCity($options['city']);
        $application = $options['application'];
        $paymentType = $options['payment_type'] ?? $orderweb->pay_system;
        $legs = $options['legs'] ?? [
            ['uid' => $resolveUidMapping ? $primaryUid : $rawPrimaryUid, 'auth_role' => 'default'],
        ];
        $waitSeconds = max(1, (int) ($options['wait_seconds'] ?? self::ADD_COST_RECREATION_WAIT_SECONDS));
        $deadline = time() + $waitSeconds;

        $attempt = 0;
        $cancelResultCode = null;
        $snapshots = [];

        while (time() < $deadline) {
            $attempt++;
            $pass = $this->runCancelPass(
                $orderweb,
                $city,
                $application,
                $paymentType,
                $legs,
                $attempt,
                $resolveUidMapping
            );
            $snapshots = $pass['snapshots'];
            if ($pass['cancel_result_code'] !== null) {
                $cancelResultCode = $pass['cancel_result_code'];
            }

            if ($pass['all_settled']) {
                $this->stopCampaignWithoutMapping($primaryUid);
                if ($rawPrimaryUid !== $primaryUid) {
                    $this->stopCampaignWithoutMapping($rawPrimaryUid);
                }

                Log::info('DispatchOrderCancelService: add-cost recreation cancel ready', [
                    'primary_uid' => $primaryUid,
                    'attempt' => $attempt,
                ]);

                return [
                    'ready' => true,
                    'snapshots' => $snapshots,
                    'cancel_result_code' => $cancelResultCode,
                ];
            }

            if ($attempt === 1) {
                $this->touchCampaignState(
                    $primaryUid,
                    $city,
                    $application,
                    $paymentType,
                    $legs,
                    $resolveUidMapping
                );
            }

            sleep(1);
        }

        Log::warning('DispatchOrderCancelService: add-cost recreation cancel timeout', [
            'primary_uid' => $primaryUid,
            'wait_seconds' => $waitSeconds,
        ]);

        return [
            'ready' => false,
            'snapshots' => $snapshots,
            'cancel_result_code' => $cancelResultCode,
        ];
    }

    private function stopCampaignWithoutMapping(string $primaryUid): void
    {
        Cache::forget(self::CACHE_PREFIX . $primaryUid);
        $this->removeFromCampaignIndex($primaryUid);
    }

    public function runBackgroundAttempt(string $primaryUid): void
    {
        $primaryUid = (new MemoryOrderChangeController)->show($primaryUid);
        $lock = Cache::lock(self::CACHE_PREFIX . 'lock:' . $primaryUid, 90);
        if (!$lock->get()) {
            Log::info('DispatchOrderCancelService: background attempt skipped, lock held', [
                'uid' => $primaryUid,
            ]);

            return;
        }

        try {
            $this->runBackgroundAttemptLocked($primaryUid);
        } finally {
            $lock->release();
        }
    }

    public function processDueCampaigns(): int
    {
        $index = Cache::get(self::CACHE_INDEX_KEY, []);
        if (!is_array($index) || $index === []) {
            return 0;
        }

        $processed = 0;
        foreach (array_unique($index) as $uid) {
            if (!is_string($uid) || $uid === '') {
                continue;
            }

            $state = Cache::get(self::CACHE_PREFIX . $uid);
            if (!is_array($state)) {
                $this->removeFromCampaignIndex($uid);
                continue;
            }

            $nextAttempt = (int) ($state['attempt_number'] ?? 0) + 1;
            $startedAt = (int) ($state['started_at'] ?? 0);
            if ($startedAt <= 0) {
                continue;
            }

            $dueAt = $startedAt + DispatchOrderCancelSchedule::offsetSecondsForAttempt($nextAttempt);
            if (time() < $dueAt) {
                continue;
            }

            $this->runBackgroundAttempt($uid);
            $processed++;
        }

        return $processed;
    }

    private function runBackgroundAttemptLocked(string $primaryUid): void
    {
        $cacheKey = self::CACHE_PREFIX . $primaryUid;
        $state = Cache::get($cacheKey);

        if (!is_array($state)) {
            Log::info('DispatchOrderCancelService: no active campaign', ['uid' => $primaryUid]);
            $this->removeFromCampaignIndex($primaryUid);

            return;
        }

        $resolveUidMapping = (bool) ($state['resolve_uid_mapping'] ?? true);
        $orderwebLookupUid = $resolveUidMapping
            ? $primaryUid
            : (new MemoryOrderChangeController)->show($primaryUid);
        $orderweb = Orderweb::where('dispatching_order_uid', $orderwebLookupUid)->first();
        if (!$orderweb) {
            $this->stopCampaign($primaryUid);

            return;
        }

        if ($this->isOrderwebAlreadyFinished($orderweb)) {
            $this->stopCampaign($primaryUid);

            return;
        }

        $state['attempt_number'] = (int) ($state['attempt_number'] ?? 0) + 1;
        $attempt = $state['attempt_number'];

        $pass = $this->runCancelPass(
            $orderweb,
            $state['city'],
            $state['application'],
            $state['payment_type'] ?? $orderweb->pay_system,
            $state['legs'] ?? [['uid' => $primaryUid, 'auth_role' => 'default']],
            $attempt,
            $resolveUidMapping
        );
        $state['attempt_number'] = $pass['attempt_number'];

        if ($pass['all_settled']) {
            if ($this->canFinalizeClientCancel($orderweb, $primaryUid)) {
                $this->finalizeIfAllowed($orderweb, $primaryUid, $pass['cancel_result_code']);
                $this->stopCampaign($primaryUid);
                Log::info('DispatchOrderCancelService: background cancel finalized', ['uid' => $primaryUid]);

                return;
            }

            Log::info('DispatchOrderCancelService: dispatch settled but fork still live, continue campaign', [
                'uid' => $primaryUid,
            ]);
        }

        $elapsed = time() - (int) $state['started_at'];
        if (!$state['problem_telegram_sent'] && $elapsed >= DispatchOrderCancelSchedule::PROBLEM_TELEGRAM_AFTER_SECONDS) {
            $this->sendProblemTelegram($orderweb, $primaryUid, $attempt);
            $state['problem_telegram_sent'] = true;
        }

        Cache::put($cacheKey, $state, now()->addHours(24));
        $this->addToCampaignIndex($primaryUid);

        $this->enqueueCancelRetryJob($primaryUid, $state, false);

        Log::info('DispatchOrderCancelService: scheduled next background attempt', [
            'uid' => $primaryUid,
            'attempt' => $attempt,
            'next_attempt' => $attempt + 1,
        ]);
    }

    public function isDispatchCancelSettled(?array $status, ?string $primaryUid = null): bool
    {
        if (!is_array($status)) {
            return false;
        }

        if (!empty($status['order_is_archive'])) {
            return true;
        }

        if ((int) ($status['close_reason'] ?? -1) === 1) {
            return true;
        }

        return false;
    }

    public function stopCampaign(string $primaryUid): void
    {
        $primaryUid = (new MemoryOrderChangeController)->show($primaryUid);
        Cache::forget(self::CACHE_PREFIX . $primaryUid);
        $this->removeFromCampaignIndex($primaryUid);
    }

    /** Client pressed cancel; background retries still running. */
    public static function hasActiveCampaign(string $primaryUid): bool
    {
        $primaryUid = (new MemoryOrderChangeController)->show($primaryUid);

        return Cache::has(self::CACHE_PREFIX . $primaryUid);
    }

    /**
     * @param array<int, array{uid: string, auth_role?: string, authorization?: string}> $legs
     *
     * @return array{all_settled: bool, cancel_result_code: int|null, attempt_number: int, snapshots: array}
     */
    private function resolveCancelLegUid(string $uid, bool $resolveUidMapping): string
    {
        if (!$resolveUidMapping) {
            return $uid;
        }

        return (new MemoryOrderChangeController)->show($uid);
    }

    private function runCancelPass(
        Orderweb $orderweb,
        string $city,
        string $application,
        ?string $paymentType,
        array $legs,
        int $attemptNumber = 1,
        bool $resolveUidMapping = true
    ): array {
        $connectAPI = $orderweb->server;
        $controller = new AndroidTestOSMController();
        $apiVersion = (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application);
        $appId = $controller->identificationId($application);
        $authChoice = $controller->authorizationChoiceApp($paymentType, $city, $connectAPI, $application);

        $allSettled = true;
        $snapshots = [];
        $cancelResultCode = null;

        foreach ($legs as $leg) {
            $legUid = $this->resolveCancelLegUid($leg['uid'], $resolveUidMapping);
            $authorization = $this->resolveLegAuthorization($authChoice, $leg);

            if ($authorization === null || $authorization === '') {
                Log::warning('DispatchOrderCancelService: missing authorization for leg', [
                    'uid' => $legUid,
                ]);
                $allSettled = false;
                continue;
            }

            $header = [
                'Authorization' => $authorization,
                'X-WO-API-APP-ID' => $appId,
                'X-API-VERSION' => $apiVersion,
            ];

            $cancelUrl = $connectAPI . '/api/weborders/cancel/' . $legUid;
            try {
                $cancelResponse = Http::timeout(20)->withHeaders($header)->put($cancelUrl);
                $parsed = $this->parseDispatchClientCancelResult($cancelResponse);
                if ($parsed !== null) {
                    $cancelResultCode = $parsed;
                }
                Log::info('DispatchOrderCancelService: cancel PUT', [
                    'uid' => $legUid,
                    'attempt' => $attemptNumber,
                    'status' => $cancelResponse->status(),
                ]);
            } catch (\Throwable $e) {
                Log::error('DispatchOrderCancelService: cancel PUT failed', [
                    'uid' => $legUid,
                    'attempt' => $attemptNumber,
                    'error' => $e->getMessage(),
                ]);
                $allSettled = false;
                continue;
            }

            $statusUrl = $connectAPI . '/api/weborders/' . $legUid;
            try {
                $statusArr = (new UniversalAndroidFunctionController)->getStatus($header, $statusUrl);
            } catch (\Throwable $e) {
                Log::error('DispatchOrderCancelService: status GET failed', [
                    'uid' => $legUid,
                    'attempt' => $attemptNumber,
                    'error' => $e->getMessage(),
                ]);
                $allSettled = false;
                continue;
            }

            $snapshots[$legUid] = $statusArr;
            if (!$this->isDispatchCancelSettled($statusArr, $legUid)) {
                $allSettled = false;
            }
        }

        return [
            'all_settled' => $allSettled,
            'cancel_result_code' => $cancelResultCode,
            'attempt_number' => $attemptNumber,
            'snapshots' => $snapshots,
        ];
    }

    /**
     * Вторая попытка в том же HTTP-запросе — ровно на 5-й секунде кампании (спека).
     *
     * @param array<int, array{uid: string, auth_role?: string, authorization?: string}> $legs
     * @param array{all_settled: bool, cancel_result_code: int|null, attempt_number: int, snapshots: array} $firstPass
     *
     * @return array{all_settled: bool, cancel_result_code: int|null, attempt_number: int, snapshots: array}
     */
    private function runSyncSecondAttempt(
        Orderweb $orderweb,
        string $primaryUid,
        string $city,
        string $application,
        ?string $paymentType,
        array $legs,
        array $firstPass,
        bool $resolveUidMapping = true
    ): array {
        if (!empty($firstPass['all_settled'])) {
            return $firstPass;
        }

        $state = Cache::get(self::CACHE_PREFIX . $primaryUid);
        $startedAt = is_array($state) ? (int) ($state['started_at'] ?? time()) : time();
        $waitSeconds = DispatchOrderCancelSchedule::delayUntilNextAttempt($startedAt, 2);
        if ($waitSeconds > 0) {
            sleep($waitSeconds);
        }

        return $this->runCancelPass(
            $orderweb,
            $city,
            $application,
            $paymentType,
            $legs,
            2,
            $resolveUidMapping
        );
    }

    /**
     * @param array<int, array{uid: string, auth_role?: string, authorization?: string}> $legs
     */
    private function scheduleBackgroundCampaign(
        string $primaryUid,
        string $city,
        string $application,
        ?string $paymentType,
        array $legs,
        int $attemptNumber,
        bool $resolveUidMapping = true
    ): void {
        $cacheKey = self::CACHE_PREFIX . $primaryUid;
        $now = time();

        $state = [
            'primary_uid' => $primaryUid,
            'city' => $city,
            'application' => $application,
            'payment_type' => $paymentType,
            'legs' => $legs,
            'resolve_uid_mapping' => $resolveUidMapping,
            'started_at' => $now,
            'attempt_number' => $attemptNumber,
            'problem_telegram_sent' => false,
        ];

        if (Cache::has($cacheKey)) {
            $existing = Cache::get($cacheKey);
            if (is_array($existing)) {
                $state['started_at'] = (int) ($existing['started_at'] ?? $now);
                $state['problem_telegram_sent'] = (bool) ($existing['problem_telegram_sent'] ?? false);
                $state['legs'] = $this->mergeLegs($existing['legs'] ?? [], $legs);
                $state['resolve_uid_mapping'] = (bool) ($existing['resolve_uid_mapping'] ?? $resolveUidMapping);
            }
        }

        Cache::put($cacheKey, $state, now()->addHours(24));
        $this->addToCampaignIndex($primaryUid);

        $this->enqueueCancelRetryJob($primaryUid, $state, false);

        Log::info('DispatchOrderCancelService: background campaign scheduled', [
            'uid' => $primaryUid,
            'attempt_number' => $attemptNumber,
            'next_attempt' => $attemptNumber + 1,
        ]);
    }

    /**
     * @param array<int, array{uid: string, auth_role?: string, authorization?: string}> $legs
     */
    private function touchCampaignState(
        string $primaryUid,
        string $city,
        string $application,
        ?string $paymentType,
        array $legs,
        bool $resolveUidMapping = true
    ): void {
        $cacheKey = self::CACHE_PREFIX . $primaryUid;
        if (Cache::has($cacheKey)) {
            $existing = Cache::get($cacheKey);
            if (is_array($existing)) {
                $existing['city'] = $city;
                $existing['application'] = $application;
                $existing['payment_type'] = $paymentType;
                $existing['legs'] = $this->mergeLegs($existing['legs'] ?? [], $legs);
                $existing['resolve_uid_mapping'] = $resolveUidMapping;
                Cache::put($cacheKey, $existing, now()->addHours(24));
                $this->addToCampaignIndex($primaryUid);
                $this->enqueueCancelRetryJob($primaryUid, $existing, true);

                return;
            }
        }

        Cache::put($cacheKey, [
            'primary_uid' => $primaryUid,
            'city' => $city,
            'application' => $application,
            'payment_type' => $paymentType,
            'legs' => $legs,
            'resolve_uid_mapping' => $resolveUidMapping,
            'started_at' => time(),
            'attempt_number' => 0,
            'problem_telegram_sent' => false,
        ], now()->addHours(24));
        $this->addToCampaignIndex($primaryUid);
    }

    /**
     * @param array<string, mixed> $state
     */
    private function enqueueCancelRetryJob(string $primaryUid, array $state, bool $resumeExisting = false): void
    {
        $startedAt = (int) ($state['started_at'] ?? time());
        $attemptNumber = (int) ($state['attempt_number'] ?? 0);
        $nextAttempt = $attemptNumber + 1;
        $delay = DispatchOrderCancelSchedule::delayUntilNextAttempt($startedAt, $nextAttempt);
        if ($resumeExisting) {
            $delay = 0;
        }

        try {
            DispatchOrderCancelRetryJob::dispatch($primaryUid)
                ->onQueue(self::RETRY_JOB_QUEUE)
                ->delay(now()->addSeconds($delay));
        } catch (\Throwable $e) {
            Log::error('DispatchOrderCancelService: failed to enqueue retry job', [
                'uid' => $primaryUid,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('DispatchOrderCancelService: ensured cancel retry job queued', [
            'uid' => $primaryUid,
            'next_attempt' => $nextAttempt,
            'delay_seconds' => $delay,
            'queue' => self::RETRY_JOB_QUEUE,
            'resume_existing' => $resumeExisting,
        ]);
    }

    private function addToCampaignIndex(string $primaryUid): void
    {
        $primaryUid = (new MemoryOrderChangeController)->show($primaryUid);
        $index = Cache::get(self::CACHE_INDEX_KEY, []);
        if (!is_array($index)) {
            $index = [];
        }
        if (!in_array($primaryUid, $index, true)) {
            $index[] = $primaryUid;
            Cache::put(self::CACHE_INDEX_KEY, $index, now()->addHours(24));
        }
    }

    private function removeFromCampaignIndex(string $primaryUid): void
    {
        $primaryUid = (new MemoryOrderChangeController)->show($primaryUid);
        $index = Cache::get(self::CACHE_INDEX_KEY, []);
        if (!is_array($index) || $index === []) {
            return;
        }

        $index = array_values(array_filter($index, static function ($uid) use ($primaryUid) {
            return $uid !== $primaryUid;
        }));
        Cache::put(self::CACHE_INDEX_KEY, $index, now()->addHours(24));
    }

    private function finalizeIfAllowed(Orderweb $orderweb, string $primaryUid, ?int $cancelResultCode): void
    {
        if (!$this->canFinalizeClientCancel($orderweb, $primaryUid)) {
            Log::info('DispatchOrderCancelService: skip finalize, fork still live', [
                'uid' => $primaryUid,
                'hold_uid' => (new MemoryOrderChangeController)->findLatestOrderUid($primaryUid) ?: $primaryUid,
            ]);

            return;
        }

        (new AndroidTestOSMController())->finalizeDispatchClientCancel($orderweb, $primaryUid);
    }

    /**
     * @param array<int, array{uid: string, auth_role?: string, authorization?: string}> $legs
     * @param array{all_settled: bool, cancel_result_code: int|null, attempt_number: int, snapshots: array} $pass
     *
     * @return array{dispatch_cancelled: bool, client_message: string, cancel_result_code: int|null, background_retry: bool}
     */
    private function handleSettledPass(
        Orderweb $orderweb,
        string $primaryUid,
        string $city,
        string $application,
        ?string $paymentType,
        array $legs,
        array $pass
    ): array {
        if (!$this->canFinalizeClientCancel($orderweb, $primaryUid)) {
            $this->scheduleBackgroundCampaign(
                $primaryUid,
                $city,
                $application,
                $paymentType,
                $legs,
                $pass['attempt_number']
            );

            return [
                'dispatch_cancelled' => false,
                'client_message' => self::CLIENT_MESSAGE_PENDING,
                'cancel_result_code' => $pass['cancel_result_code'],
                'background_retry' => true,
            ];
        }

        $this->finalizeIfAllowed($orderweb, $primaryUid, $pass['cancel_result_code']);
        $this->stopCampaign($primaryUid);

        return [
            'dispatch_cancelled' => true,
            'client_message' => $this->buildConfirmedMessage($pass['cancel_result_code']),
            'cancel_result_code' => $pass['cancel_result_code'],
            'background_retry' => false,
        ];
    }

    private function canFinalizeClientCancel(Orderweb $orderweb, string $primaryUid): bool
    {
        $holdUid = (new MemoryOrderChangeController)->findLatestOrderUid($primaryUid) ?: $primaryUid;
        $orderwebForFinalize = Orderweb::where('dispatching_order_uid', $holdUid)->first() ?? $orderweb;

        return OrderStatusController::shouldFinalizeDispatchClientCancel($orderwebForFinalize);
    }

    private function buildConfirmedMessage(?int $cancelResultCode): string
    {
        return (new AndroidTestOSMController())->buildClientCancelConfirmedMessage($cancelResultCode);
    }

    /**
     * @return array{dispatch_cancelled: bool, client_message: string, cancel_result_code: int|null, background_retry: bool}
     */
    private function failureResult(?int $cancelResultCode, string $message): array
    {
        return [
            'dispatch_cancelled' => false,
            'client_message' => $message,
            'cancel_result_code' => $cancelResultCode,
            'background_retry' => false,
        ];
    }

    private function parseDispatchClientCancelResult($httpResponse): ?int
    {
        if ($httpResponse === null || !$httpResponse->successful()) {
            return null;
        }
        $body = $httpResponse->json();
        if (!is_array($body) || !array_key_exists('order_client_cancel_result', $body)) {
            return null;
        }

        return (int) $body['order_client_cancel_result'];
    }

    private function sendProblemTelegram(Orderweb $orderweb, string $primaryUid, int $attempt): void
    {
        $createdLocal = $orderweb->created_at
            ? Carbon::parse($orderweb->created_at)->setTimezone('Europe/Kiev')->format('d.m.Y H:i:s')
            : 'n/a';
        $server = trim((string) ($orderweb->server ?? ''));
        $from = trim((string) ($orderweb->routefrom ?? ''));

        $message = sprintf(
            '%s %s %s — проблема отмены',
            $createdLocal,
            $server,
            $from !== '' ? $from : $primaryUid
        );

        try {
            (new MessageSentController)->sentMessageMeCancel($primaryUid . ' ' . $message);
            Log::warning('DispatchOrderCancelService: problem telegram sent', [
                'uid' => $primaryUid,
                'attempt' => $attempt,
                'elapsed_seconds' => DispatchOrderCancelSchedule::PROBLEM_TELEGRAM_AFTER_SECONDS,
            ]);
        } catch (\Throwable $e) {
            Log::error('DispatchOrderCancelService: problem telegram failed', [
                'uid' => $primaryUid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Заказ уже снят с диспетчера (closeReason=1).
     * Статусы 101–104 — поездка с нашим водителем; отмену на внешнем API всё равно нужно отправить при взятии.
     */
    private function isOrderwebAlreadyFinished(Orderweb $orderweb): bool
    {
        return (string) ($orderweb->closeReason ?? '') === '1';
    }

    private function normalizeCity(string $city): string
    {
        $city = trim($city);
        $regional = [
            'Lviv', 'Ivano_frankivsk', 'Vinnytsia', 'Poltava', 'Sumy', 'Kharkiv',
            'Chernihiv', 'Rivne', 'Ternopil', 'Khmelnytskyi', 'Zakarpattya', 'Zhytomyr',
            'Kropyvnytskyi', 'Mykolaiv', 'Chernivtsi', 'Lutsk',
        ];
        if (in_array($city, $regional, true)) {
            return 'OdessaTest';
        }
        if ($city === 'foreign countries') {
            return 'Kyiv City';
        }

        return $city;
    }

    /**
     * @param array<int, array{uid: string, auth_role?: string, authorization?: string}> $existing
     * @param array<int, array{uid: string, auth_role?: string, authorization?: string}> $incoming
     *
     * @return array<int, array{uid: string, auth_role?: string, authorization?: string}>
     */
    private function mergeLegs(array $existing, array $incoming): array
    {
        $byUid = [];
        foreach ($existing as $leg) {
            $uid = (new MemoryOrderChangeController)->show($leg['uid']);
            $byUid[$uid] = $leg;
        }
        foreach ($incoming as $leg) {
            $uid = (new MemoryOrderChangeController)->show($leg['uid']);
            $byUid[$uid] = array_merge($byUid[$uid] ?? [], $leg);
        }

        return array_values($byUid);
    }

    /**
     * @param array<string, mixed> $authChoice
     * @param array{uid: string, auth_role?: string, authorization?: string} $leg
     */
    private function resolveLegAuthorization(array $authChoice, array $leg): ?string
    {
        if (!empty($leg['authorization'])) {
            return $leg['authorization'];
        }

        $authRole = $leg['auth_role'] ?? 'default';
        switch ($authRole) {
            case 'bonus':
                return $authChoice['authorizationBonus'] ?? $authChoice['authorization'] ?? null;
            case 'double':
                return $authChoice['authorizationDouble'] ?? $authChoice['authorization'] ?? null;
            default:
                return $authChoice['authorization'] ?? null;
        }
    }
}

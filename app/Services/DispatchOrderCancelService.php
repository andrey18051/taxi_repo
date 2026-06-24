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

class DispatchOrderCancelService
{
    public const CACHE_PREFIX = 'dispatch_cancel_campaign:';

    public const CLIENT_MESSAGE_PENDING = 'Запит на скасування замовлення надіслано. Очікуємо підтвердження диспетчера.';

    /** Короткий синхронный дожим в HTTP после первого PUT (сек). */
    public const SYNC_BURST_SECONDS = 12;

    /**
     * @param array{
     *     primary_uid: string,
     *     city: string,
     *     application: string,
     *     payment_type?: string|null,
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
        $primaryUid = (new MemoryOrderChangeController)->show($options['primary_uid']);
        $orderweb = Orderweb::where('dispatching_order_uid', $primaryUid)->first();
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
            ['uid' => $primaryUid, 'auth_role' => 'default'],
        ];

        $pass = $this->runCancelPass($orderweb, $city, $application, $paymentType, $legs);
        if ($pass['all_settled']) {
            return $this->handleSettledPass($orderweb, $primaryUid, $city, $application, $paymentType, $legs, $pass);
        }

        $pass = $this->runSyncBurst($orderweb, $city, $application, $paymentType, $legs, $pass);
        if ($pass['all_settled']) {
            return $this->handleSettledPass($orderweb, $primaryUid, $city, $application, $paymentType, $legs, $pass);
        }

        $this->scheduleBackgroundCampaign($primaryUid, $city, $application, $paymentType, $legs, $pass['attempt_number']);

        return [
            'dispatch_cancelled' => false,
            'client_message' => self::CLIENT_MESSAGE_PENDING,
            'cancel_result_code' => $pass['cancel_result_code'],
            'background_retry' => true,
        ];
    }

    public function runBackgroundAttempt(string $primaryUid): void
    {
        $primaryUid = (new MemoryOrderChangeController)->show($primaryUid);
        $cacheKey = self::CACHE_PREFIX . $primaryUid;
        $state = Cache::get($cacheKey);

        if (!is_array($state)) {
            Log::info('DispatchOrderCancelService: no active campaign', ['uid' => $primaryUid]);

            return;
        }

        $orderweb = Orderweb::where('dispatching_order_uid', $primaryUid)->first();
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
            $state['legs'] ?? [['uid' => $primaryUid, 'auth_role' => 'default']]
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

        $nextAttempt = $attempt + 1;
        $delay = DispatchOrderCancelSchedule::delayUntilNextAttempt((int) $state['started_at'], $nextAttempt);
        DispatchOrderCancelRetryJob::dispatch($primaryUid)
            ->delay(now()->addSeconds($delay));

        Log::info('DispatchOrderCancelService: scheduled next background attempt', [
            'uid' => $primaryUid,
            'attempt' => $attempt,
            'next_attempt' => $nextAttempt,
            'delay_seconds' => $delay,
        ]);
    }

    public function isDispatchCancelSettled(?array $status): bool
    {
        if (!is_array($status)) {
            return false;
        }

        if (!empty($status['order_is_archive'])) {
            return true;
        }

        return (int) ($status['close_reason'] ?? -1) === 1;
    }

    public function stopCampaign(string $primaryUid): void
    {
        Cache::forget(self::CACHE_PREFIX . (new MemoryOrderChangeController)->show($primaryUid));
    }

    /**
     * @param array<int, array{uid: string, auth_role?: string, authorization?: string}> $legs
     *
     * @return array{all_settled: bool, cancel_result_code: int|null, attempt_number: int, snapshots: array}
     */
    private function runCancelPass(
        Orderweb $orderweb,
        string $city,
        string $application,
        ?string $paymentType,
        array $legs,
        int $attemptNumber = 1
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
            $legUid = (new MemoryOrderChangeController)->show($leg['uid']);
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
            if (!$this->isDispatchCancelSettled($statusArr)) {
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
     * @param array<int, array{uid: string, auth_role?: string, authorization?: string}> $legs
     * @param array{all_settled: bool, cancel_result_code: int|null, attempt_number: int, snapshots: array} $firstPass
     *
     * @return array{all_settled: bool, cancel_result_code: int|null, attempt_number: int, snapshots: array}
     */
    private function runSyncBurst(
        Orderweb $orderweb,
        string $city,
        string $application,
        ?string $paymentType,
        array $legs,
        array $firstPass
    ): array {
        $deadline = time() + self::SYNC_BURST_SECONDS;
        $pass = $firstPass;
        $attempt = (int) ($pass['attempt_number'] ?? 1);

        while (time() < $deadline) {
            sleep(5);
            $attempt++;
            $pass = $this->runCancelPass($orderweb, $city, $application, $paymentType, $legs, $attempt);
            if ($pass['all_settled']) {
                return $pass;
            }
        }

        return $pass;
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
        int $attemptNumber
    ): void {
        $cacheKey = self::CACHE_PREFIX . $primaryUid;
        $now = time();

        $state = [
            'primary_uid' => $primaryUid,
            'city' => $city,
            'application' => $application,
            'payment_type' => $paymentType,
            'legs' => $legs,
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
            }
        }

        Cache::put($cacheKey, $state, now()->addHours(24));

        $nextAttempt = $attemptNumber + 1;
        $delay = DispatchOrderCancelSchedule::delayUntilNextAttempt((int) $state['started_at'], $nextAttempt);

        try {
            DispatchOrderCancelRetryJob::dispatch($primaryUid)
                ->delay(now()->addSeconds($delay));
        } catch (\Throwable $e) {
            Log::error('DispatchOrderCancelService: failed to enqueue retry job', [
                'uid' => $primaryUid,
                'error' => $e->getMessage(),
            ]);
            // Кампания в cache остаётся — повторная отмена или worker после hotfix подхватит.
        }

        Log::info('DispatchOrderCancelService: background campaign scheduled', [
            'uid' => $primaryUid,
            'attempt_number' => $attemptNumber,
            'next_attempt' => $nextAttempt,
            'delay_seconds' => $delay,
        ]);
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
        $orderwebForNotify = Orderweb::where('dispatching_order_uid', $holdUid)->first() ?? $orderweb;

        return OrderStatusController::shouldNotifyClientOrderCanceled($orderwebForNotify);
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
            '%s %s %s — проблема отмены (попытка %d)',
            $createdLocal,
            $server,
            $from !== '' ? $from : $primaryUid,
            $attempt
        );

        try {
            (new MessageSentController)->sentMessageMeCancel($primaryUid . ' ' . $message);
        } catch (\Throwable $e) {
            Log::error('DispatchOrderCancelService: problem telegram failed', [
                'uid' => $primaryUid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isOrderwebAlreadyFinished(Orderweb $orderweb): bool
    {
        $closeReason = (string) ($orderweb->closeReason ?? '');

        return $closeReason === '1'
            || in_array($closeReason, ['101', '102', '103', '104'], true);
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

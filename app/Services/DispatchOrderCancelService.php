<?php

namespace App\Services;

use App\Http\Controllers\AndroidTestOSMController;
use App\Http\Controllers\MemoryOrderChangeController;
use App\Http\Controllers\MessageSentController;
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

    public const CLIENT_MESSAGE_PENDING = 'Запит на скасування замовлення надіслано.';

    /**
     * @param array{
     *     primary_uid: string,
     *     city: string,
     *     application: string,
     *     payment_type?: string|null,
     *     legs?: array<int, array{uid: string, auth_role?: string, authorization?: string}>|null
     * } $options
     *
     * @return 'finalized'|'campaign_started'|'already_running'|'skipped'
     */
    public function requestClientCancel(array $options): string
    {
        $primaryUid = (new MemoryOrderChangeController)->show($options['primary_uid']);
        $orderweb = Orderweb::where('dispatching_order_uid', $primaryUid)->first();
        if (!$orderweb) {
            return 'skipped';
        }

        if ($this->isOrderwebAlreadyFinished($orderweb)) {
            return 'skipped';
        }

        if ($orderweb->server === 'my_server_api') {
            $this->finalizeLocalOnly($orderweb, $primaryUid);

            return 'finalized';
        }

        $city = $this->normalizeCity($options['city']);
        $application = $options['application'];
        $paymentType = $options['payment_type'] ?? $orderweb->pay_system;
        $legs = $options['legs'] ?? [
            ['uid' => $primaryUid, 'auth_role' => 'default'],
        ];

        if ($this->areAllLegsSettledOnDispatch($orderweb, $city, $application, $paymentType, $legs)) {
            (new AndroidTestOSMController())->finalizeDispatchClientCancel($orderweb, $primaryUid);
            $this->stopCampaign($primaryUid);

            return 'finalized';
        }

        return $this->startCampaign([
            'primary_uid' => $primaryUid,
            'city' => $city,
            'application' => $application,
            'payment_type' => $paymentType,
            'legs' => $legs,
        ]) ? 'campaign_started' : 'already_running';
    }

    /**
     * @param array{
     *     primary_uid: string,
     *     city: string,
     *     application: string,
     *     payment_type?: string|null,
     *     legs?: array<int, array{uid: string, auth_role?: string, authorization?: string}>|null
     * } $options
     */
    public function startCampaign(array $options): bool
    {
        $primaryUid = (new MemoryOrderChangeController)->show($options['primary_uid']);
        $cacheKey = self::CACHE_PREFIX . $primaryUid;

        $orderweb = Orderweb::where('dispatching_order_uid', $primaryUid)->first();
        if (!$orderweb) {
            Log::warning('DispatchOrderCancelService: orderweb not found', ['uid' => $primaryUid]);

            return false;
        }

        if ($this->isOrderwebAlreadyFinished($orderweb)) {
            Log::info('DispatchOrderCancelService: order already finished locally', ['uid' => $primaryUid]);

            return false;
        }

        $city = $this->normalizeCity($options['city']);
        $application = $options['application'];
        $paymentType = $options['payment_type'] ?? $orderweb->pay_system;

        $legs = $options['legs'] ?? [
            ['uid' => $primaryUid, 'auth_role' => 'default'],
        ];

        if (Cache::has($cacheKey)) {
            $state = Cache::get($cacheKey);
            if (is_array($state)) {
                $merged = $this->mergeLegs($state['legs'] ?? [], $legs);
                if ($merged !== ($state['legs'] ?? [])) {
                    $state['legs'] = $merged;
                    Cache::put($cacheKey, $state, now()->addHours(24));
                    Log::info('DispatchOrderCancelService: merged legs into active campaign', [
                        'uid' => $primaryUid,
                        'legs' => array_column($merged, 'uid'),
                    ]);
                }
            }

            $this->ensureCancelRetryJobQueued($primaryUid, 'resumed existing campaign');

            return false;
        }

        $state = [
            'primary_uid' => $primaryUid,
            'city' => $city,
            'application' => $application,
            'payment_type' => $paymentType,
            'legs' => $legs,
            'started_at' => time(),
            'attempt_number' => 0,
            'problem_telegram_sent' => false,
        ];

        Cache::put($cacheKey, $state, now()->addHours(24));

        try {
            $this->ensureCancelRetryJobQueued($primaryUid, 'campaign started');
        } catch (\Throwable $e) {
            Cache::forget($cacheKey);
            Log::error('DispatchOrderCancelService: failed to queue retry job, cleared campaign cache', [
                'uid' => $primaryUid,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return true;
    }

    private function ensureCancelRetryJobQueued(string $primaryUid, string $reason): void
    {
        DispatchOrderCancelRetryJob::dispatch($primaryUid);

        Log::info('DispatchOrderCancelService: ensured cancel retry job queued', [
            'uid' => $primaryUid,
            'reason' => $reason,
        ]);
    }

    public function runAttempt(string $primaryUid): void
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
            Log::warning('DispatchOrderCancelService: orderweb missing, stop campaign', ['uid' => $primaryUid]);

            return;
        }

        if ($this->isOrderwebAlreadyFinished($orderweb)) {
            $this->stopCampaign($primaryUid);
            Log::info('DispatchOrderCancelService: order finished locally, stop campaign', ['uid' => $primaryUid]);

            return;
        }

        $state['attempt_number'] = (int) ($state['attempt_number'] ?? 0) + 1;
        $attempt = $state['attempt_number'];

        $connectAPI = $orderweb->server;
        if ($connectAPI === 'my_server_api') {
            $this->finalizeLocalOnly($orderweb, $primaryUid);
            $this->stopCampaign($primaryUid);

            return;
        }

        $city = $state['city'];
        $application = $state['application'];
        $paymentType = $state['payment_type'] ?? $orderweb->pay_system;
        $controller = new AndroidTestOSMController();
        $apiVersion = (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application);
        $appId = $controller->identificationId($application);
        $authChoice = $controller->authorizationChoiceApp($paymentType, $city, $connectAPI, $application);

        $allSettled = true;
        $statusSnapshots = [];

        foreach ($state['legs'] as $leg) {
            $legUid = (new MemoryOrderChangeController)->show($leg['uid']);
            $authorization = $this->resolveLegAuthorization($authChoice, $leg);

            if ($authorization === null || $authorization === '') {
                Log::warning('DispatchOrderCancelService: missing authorization for leg', [
                    'uid' => $legUid,
                    'leg' => $leg,
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
                $cancelResponse = Http::timeout(25)->withHeaders($header)->put($cancelUrl);
                Log::info('DispatchOrderCancelService: cancel PUT', [
                    'uid' => $legUid,
                    'attempt' => $attempt,
                    'status' => $cancelResponse->status(),
                    'body' => $cancelResponse->body(),
                ]);
            } catch (\Throwable $e) {
                Log::error('DispatchOrderCancelService: cancel PUT failed', [
                    'uid' => $legUid,
                    'attempt' => $attempt,
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
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
                $allSettled = false;
                continue;
            }

            $statusSnapshots[$legUid] = $statusArr;
            if (!$this->isDispatchCancelSettled($statusArr)) {
                $allSettled = false;
            }
        }

        if ($allSettled) {
            (new AndroidTestOSMController())->finalizeDispatchClientCancel($orderweb, $primaryUid);
            $this->stopCampaign($primaryUid);
            Log::info('DispatchOrderCancelService: cancel finalized', [
                'uid' => $primaryUid,
                'snapshots' => $statusSnapshots,
            ]);

            return;
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

        Log::info('DispatchOrderCancelService: scheduled next attempt', [
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
     */
    private function areAllLegsSettledOnDispatch(
        Orderweb $orderweb,
        string $city,
        string $application,
        ?string $paymentType,
        array $legs
    ): bool {
        $connectAPI = $orderweb->server;
        if ($connectAPI === 'my_server_api') {
            return true;
        }

        $controller = new AndroidTestOSMController();
        $apiVersion = (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application);
        $appId = $controller->identificationId($application);
        $authChoice = $controller->authorizationChoiceApp($paymentType, $city, $connectAPI, $application);

        foreach ($legs as $leg) {
            $legUid = (new MemoryOrderChangeController)->show($leg['uid']);
            $authorization = $this->resolveLegAuthorization($authChoice, $leg);
            if ($authorization === null || $authorization === '') {
                return false;
            }

            $header = [
                'Authorization' => $authorization,
                'X-WO-API-APP-ID' => $appId,
                'X-API-VERSION' => $apiVersion,
            ];
            $statusUrl = $connectAPI . '/api/weborders/' . $legUid;

            try {
                $statusArr = (new UniversalAndroidFunctionController)->getStatus($header, $statusUrl);
            } catch (\Throwable $e) {
                return false;
            }

            if (!$this->isDispatchCancelSettled($statusArr)) {
                return false;
            }
        }

        return true;
    }

    private function finalizeLocalOnly(Orderweb $orderweb, string $primaryUid): void
    {
        $controller = new AndroidTestOSMController();
        $controller->finalizeDispatchClientCancel(
            $orderweb,
            $primaryUid,
            'Отмена my_server_api (без диспетчера)'
        );
    }

    private function sendProblemTelegram(Orderweb $orderweb, string $primaryUid, int $attempt): void
    {
        $createdLocal = $orderweb->created_at
            ? Carbon::parse($orderweb->created_at)->setTimezone('Europe/Kiev')->format('d.m.Y H:i:s')
            : 'n/a';
        $server = trim((string) ($orderweb->server ?? ''));
        $from = trim((string) ($orderweb->routefrom ?? ''));

        $message = sprintf(
            '%s %s %s — проблема отмены (попытка %d, %s мин без архива)',
            $createdLocal,
            $server,
            $from !== '' ? $from : $primaryUid,
            $attempt,
            (int) (DispatchOrderCancelSchedule::PROBLEM_TELEGRAM_AFTER_SECONDS / 60)
        );

        try {
            (new MessageSentController)->sentMessageMeCancel($primaryUid . ' ' . $message);
        } catch (\Throwable $e) {
            Log::error('DispatchOrderCancelService: problem telegram failed', [
                'uid' => $primaryUid,
                'error' => $e->getMessage(),
            ]);
        }

        Log::warning('DispatchOrderCancelService: problem telegram sent', [
            'uid' => $primaryUid,
            'attempt' => $attempt,
        ]);
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

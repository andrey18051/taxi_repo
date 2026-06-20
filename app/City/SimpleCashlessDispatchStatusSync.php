<?php

namespace App\City;

use App\Http\Controllers\UIDController;
use App\Models\Orderweb;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Live dispatch snapshot for payment_flow=SIMPLE (single cashless order, no fork).
 */
final class SimpleCashlessDispatchStatusSync
{
    /** @var list<string> Active dispatch legs — search through trip completion (excludes 104). */
    private const ACTIVE_CLOSE_REASONS = ['-1', '0', '100', '101', '102', '103'];

    /** @var list<string> Same set for client "orders in progress" list and background poll. */
    public const IN_PROGRESS_CLOSE_REASONS = ['-1', '0', '100', '101', '102', '103'];

    /** @var list<string> */
    public const CASHLESS_PAY_SYSTEMS = ['wfp_payment', 'google_pay_payment'];

    public static function isCashlessPaySystem(?string $paySystem): bool
    {
        return in_array((string) $paySystem, self::CASHLESS_PAY_SYSTEMS, true);
    }

    public static function shouldLiveSync(Orderweb $orderweb): bool
    {
        if (PaymentFlow::normalize($orderweb->payment_flow_mode ?? 0) !== PaymentFlow::SIMPLE) {
            return false;
        }

        $paySystem = (string) ($orderweb->pay_system ?? '');
        if (!self::isCashlessPaySystem($paySystem)) {
            return false;
        }

        $server = (string) ($orderweb->server ?? '');
        if ($server === '' || $server === 'my_server_api') {
            return false;
        }

        return in_array((string) ($orderweb->closeReason ?? ''), self::ACTIVE_CLOSE_REASONS, true);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function fetchDispatchSnapshot(Orderweb $orderweb, string $uid): ?array
    {
        $connectApi = (string) ($orderweb->server ?? '');
        $identificationId = (string) ($orderweb->comment ?? '');
        if ($connectApi === '' || $identificationId === '') {
            return null;
        }

        $auth = (new UIDController())->autorization($connectApi);
        if ($auth === null) {
            return null;
        }

        $url = rtrim($connectApi, '/') . '/api/weborders/' . $uid;

        try {
            $response = Http::withHeaders([
                'Authorization' => $auth,
                'X-WO-API-APP-ID' => $identificationId,
            ])->timeout(5)->get($url);

            if (!$response->successful()) {
                Log::warning('SimpleCashlessDispatchStatusSync: dispatch HTTP error', [
                    'uid' => $uid,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = json_decode($response->body(), true);

            return is_array($data) ? $data : null;
        } catch (\Throwable $e) {
            Log::warning('SimpleCashlessDispatchStatusSync: dispatch fetch failed', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    public static function applySnapshotToOrderweb(Orderweb $orderweb, array $snapshot): void
    {
        if (array_key_exists('close_reason', $snapshot)) {
            $newClose = $snapshot['close_reason'];
            if ((string) $orderweb->closeReason === (string) $newClose) {
                $orderweb->closeReasonI = (int) ($orderweb->closeReasonI ?? 0) + 1;
            } else {
                $orderweb->closeReason = $newClose;
                $orderweb->closeReasonI = 1;
            }
        }

        if (!empty($snapshot['order_car_info'])) {
            $carInfo = $snapshot['order_car_info'];
            $orderweb->auto = is_array($carInfo)
                ? json_encode($carInfo, JSON_UNESCAPED_UNICODE)
                : (string) $carInfo;
        } elseif (array_key_exists('order_car_info', $snapshot) && $snapshot['order_car_info'] === null) {
            $orderweb->auto = null;
        }

        if (!empty($snapshot['required_time'])) {
            $orderweb->time_to_start_point = $snapshot['required_time'];
        }
    }
}

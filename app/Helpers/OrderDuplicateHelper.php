<?php

namespace App\Helpers;

use App\Http\Controllers\AndroidTestOSMController;
use App\Models\Orderweb;
use Illuminate\Support\Facades\Log;

/**
 * Дубль заказа на внешнем API (7222): не создавать параллельный заказ на my_server_api.
 */
class OrderDuplicateHelper
{
    /** Активные closeReason (заказ ещё в поиске / в работе). */
    private const ACTIVE_CLOSE_REASONS = ['-1', '100', '101', '102', '103'];

    public static function isDuplicateOrderMessage(?string $message): bool
    {
        if ($message === null || $message === '') {
            return false;
        }
        return stripos($message, 'Дублирование') !== false
            || stripos($message, 'дублир') !== false;
    }

    /**
     * Последний активный заказ на внешнем диспетчере (не my_server_api).
     */
    public static function findActiveExternalOrder(string $email, string $application): ?Orderweb
    {
        $application = strtoupper(trim($application));
        if ($email === '' || $application === '') {
            return null;
        }

        $identificationId = (new AndroidTestOSMController)->identificationId($application);
        if ($identificationId === null || $identificationId === '') {
            return null;
        }

        return Orderweb::query()
            ->where('email', $email)
            ->where('comment', $identificationId)
            ->where('server', '!=', 'my_server_api')
            ->whereIn('closeReason', self::ACTIVE_CLOSE_REASONS)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Ответ для Android в формате успешного orderSearchMarkersVisicom / orderMyApiTaxi.
     */
    public static function buildAndroidOrderResponse(Orderweb $order, $fallbackCost = null): array
    {
        $cost = $order->client_cost;
        if ($cost === null || $cost === '' || (is_numeric($cost) && (float) $cost == 0)) {
            $cost = $order->web_cost;
        }
        if (($cost === null || $cost === '' || (float) $cost == 0) && $fallbackCost !== null && $fallbackCost !== '') {
            $cost = $fallbackCost;
        }

        $costStr = (string) (is_numeric($cost) ? (int) round((float) $cost) : $cost);

        Log::info('OrderDuplicateHelper: return existing order to app', [
            'order_id' => $order->id,
            'uid' => $order->dispatching_order_uid,
            'order_cost' => $costStr,
        ]);

        return [
            'from_lat' => (string) ($order->startLat ?? '0'),
            'from_lng' => (string) ($order->startLan ?? '0'),
            'lat' => (string) ($order->to_lat ?? '0'),
            'lng' => (string) ($order->to_lng ?? '0'),
            'dispatching_order_uid' => $order->dispatching_order_uid,
            'order_cost' => $costStr,
            'currency' => 'грн',
            'routefrom' => $order->routefrom ?? 'Точка на карте',
            'routefromnumber' => $order->routefromnumber ?? ' ',
            'routeto' => $order->routeto ?? 'Точка на карте',
            'to_number' => $order->routetonumber ?? ' ',
            'doubleOrder' => '0',
            'dispatching_order_uid_Double' => null,
            'Message' => 'DuplicateActiveOrder',
            'required_time' => $order->required_time,
            'flexible_tariff_name' => $order->flexible_tariff_name,
            'comment_info' => $order->comment_info,
            'extra_charge_codes' => $order->extra_charge_codes,
        ];
    }
}

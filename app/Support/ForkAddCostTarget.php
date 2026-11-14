<?php

namespace App\Support;

use App\Http\Controllers\MemoryOrderChangeController;
use App\Models\DriverMemoryOrder;
use App\Models\Orderweb;
use App\Models\Uid_history;

/**
 * Доплата по вилке, когда PAS прислал уже пересозданную ногу, а orderweb лежит на холде.
 */
final class ForkAddCostTarget
{
    /**
     * @return array{order: Orderweb, history: ?Uid_history, bonusUid: string, doubleUid: string}|null
     */
    public static function resolve(string $requestedUid, string $fallbackDouble = ''): ?array
    {
        $requestedUid = trim($requestedUid);
        $fallbackDouble = trim($fallbackDouble);
        if ($requestedUid === '' && $fallbackDouble === '') {
            return null;
        }

        $history = ForkOrderCancelLegResolver::findHistory($requestedUid, $fallbackDouble);
        $order = ForkOrderCancelLegResolver::findOrderweb($requestedUid, $fallbackDouble);
        if ($order === null) {
            return null;
        }

        $bonusUid = $requestedUid !== '' ? $requestedUid : (string) $order->dispatching_order_uid;
        $doubleUid = $fallbackDouble;
        if ($history !== null) {
            $liveBonus = trim((string) ($history->uid_bonusOrder ?? ''));
            $liveDouble = trim((string) ($history->uid_doubleOrder ?? ''));
            if ($liveBonus !== '') {
                $bonusUid = $liveBonus;
            }
            if ($liveDouble !== '') {
                $doubleUid = $liveDouble;
            }
        } else {
            $mapped = (new MemoryOrderChangeController())->show($bonusUid);
            if (is_string($mapped) && $mapped !== '') {
                $bonusUid = $mapped;
            }
        }

        return [
            'order' => $order,
            'history' => $history,
            'bonusUid' => $bonusUid,
            'doubleUid' => $doubleUid,
        ];
    }

    public static function findMemory(string ...$seeds): ?DriverMemoryOrder
    {
        $memory = new MemoryOrderChangeController();
        $seen = [];

        foreach ($seeds as $seed) {
            $seed = trim($seed);
            if ($seed === '') {
                continue;
            }

            $candidates = [$seed];
            foreach ($memory->collectPredecessorUids($seed) as $previous) {
                $previous = trim((string) $previous);
                if ($previous !== '') {
                    $candidates[] = $previous;
                }
            }

            foreach ($candidates as $candidate) {
                if (isset($seen[$candidate])) {
                    continue;
                }
                $seen[$candidate] = true;
                $row = DriverMemoryOrder::where('dispatching_order_uid', $candidate)->first();
                if ($row !== null) {
                    return $row;
                }
            }
        }

        return null;
    }
}

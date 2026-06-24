<?php

namespace App\Support;

use App\Http\Controllers\MemoryOrderChangeController;
use App\Models\Orderweb;
use App\Models\Uid_history;

/**
 * Карта ног вилки (карта + нал) для PUT /api/weborders/cancel.
 */
class ForkOrderCancelLegResolver
{
    /**
     * @return array<int, array{uid: string, auth_role: string}>
     */
    public static function resolve(string $uidA, string $uidB, ?Uid_history $history = null): array
    {
        $uidA = (new MemoryOrderChangeController)->show(trim($uidA));
        $uidB = (new MemoryOrderChangeController)->show(trim($uidB));

        if ($history === null) {
            $history = self::findHistory($uidA, $uidB);
        }

        if ($history && !empty($history->uid_bonusOrder) && !empty($history->uid_doubleOrder)) {
            return [
                [
                    'uid' => (new MemoryOrderChangeController)->show($history->uid_bonusOrder),
                    'auth_role' => 'bonus',
                ],
                [
                    'uid' => (new MemoryOrderChangeController)->show($history->uid_doubleOrder),
                    'auth_role' => 'double',
                ],
            ];
        }

        return [
            ['uid' => $uidA, 'auth_role' => 'bonus'],
            ['uid' => $uidB, 'auth_role' => 'double'],
        ];
    }

    /**
     * UIDs that exist on the dispatcher API (not internal memory-chain ids).
     *
     * @param array<string, mixed> $authChoice
     *
     * @return array<int, array{uid: string, auth_role: string}>
     */
    public static function resolveDispatchCancelLegs(
        Orderweb $orderweb,
        ?Uid_history $history,
        array $authChoice
    ): array {
        $dispatchUid = trim((string) $orderweb->dispatching_order_uid);
        if ($dispatchUid === '') {
            return [];
        }
        $legs = [
            ['uid' => $dispatchUid, 'auth_role' => 'default'],
        ];

        if ($history === null) {
            return $legs;
        }

        if (!empty($authChoice['authorizationBonus']) && !empty($history->uid_bonusOrder)) {
            $bonusUid = (new MemoryOrderChangeController)->show($history->uid_bonusOrder);
            if ($bonusUid !== $dispatchUid) {
                $legs[] = ['uid' => $bonusUid, 'auth_role' => 'bonus'];
            }
        }

        if (!empty($authChoice['authorizationDouble']) && !empty($history->uid_doubleOrder)) {
            $doubleUid = (new MemoryOrderChangeController)->show($history->uid_doubleOrder);
            $existingUids = array_column($legs, 'uid');
            if (!in_array($doubleUid, $existingUids, true)) {
                $legs[] = ['uid' => $doubleUid, 'auth_role' => 'double'];
            }
        }

        return $legs;
    }

    public static function findHistory(string $uidA, string $uidB): ?Uid_history
    {
        foreach ([$uidA, $uidB] as $candidate) {
            $history = Uid_history::where('uid_bonusOrderHold', $candidate)->first();
            if ($history) {
                return $history;
            }
        }

        foreach ([$uidA, $uidB] as $candidate) {
            $history = Uid_history::where('uid_bonusOrder', $candidate)->first();
            if ($history) {
                return $history;
            }
        }

        foreach ([$uidA, $uidB] as $candidate) {
            $history = Uid_history::where('uid_doubleOrder', $candidate)->first();
            if ($history) {
                return $history;
            }
        }

        return null;
    }
}

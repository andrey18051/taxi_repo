<?php

namespace App\Support;

use App\Http\Controllers\MemoryOrderChangeController;
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

<?php

namespace App\Services;

use App\Http\Controllers\MemoryOrderChangeController;
use App\Models\MemoryOrderChange;
use App\Models\Uid_history;
use Illuminate\Support\Facades\Log;

/**
 * Цепочка UID вилки: каждая пересозданная нога помнит предыдущие заказы.
 */
final class ForkLegUidChain
{
    public function rememberReplacement(?string $oldUid, ?string $newUid): void
    {
        $oldUid = trim((string) $oldUid);
        $newUid = trim((string) $newUid);
        if ($newUid === '' || $oldUid === '' || $oldUid === $newUid) {
            return;
        }

        try {
            $exists = MemoryOrderChange::where('order_old', $oldUid)
                ->where('order_new', $newUid)
                ->exists();
            if ($exists) {
                return;
            }

            (new MemoryOrderChangeController())->store($oldUid, $newUid);
        } catch (\Throwable $e) {
            Log::warning('ForkLegUidChain: rememberReplacement failed', [
                'old' => $oldUid,
                'new' => $newUid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Текущие UID и все предыдущие заказы той же вилки.
     *
     * @return list<string>
     */
    public function allUids(?string $bonusUid, ?string $doubleUid, ?string $holdUid): array
    {
        $memory = new MemoryOrderChangeController();
        $collected = [];

        foreach ([$bonusUid, $doubleUid, $holdUid] as $seed) {
            $seed = trim((string) $seed);
            if ($seed === '') {
                continue;
            }

            $latest = trim((string) $memory->findLatestOrderUid($seed));
            if ($latest === '') {
                $latest = $seed;
            }

            $collected[] = $latest;
            foreach ($memory->collectPredecessorUids($latest) as $previous) {
                $previous = trim((string) $previous);
                if ($previous !== '') {
                    $collected[] = $previous;
                }
            }
        }

        return array_values(array_unique($collected));
    }

    public function publishLeg(Uid_history $history, string $leg, ?string $newUid): ?string
    {
        $newUid = trim((string) $newUid);
        if ($newUid === '') {
            return null;
        }

        $field = $leg === 'double' ? 'uid_doubleOrder' : 'uid_bonusOrder';
        $this->rememberReplacement((string) ($history->{$field} ?? ''), $newUid);
        $history->{$field} = $newUid;
        $history->save();

        return $newUid;
    }
}

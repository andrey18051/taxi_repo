<?php

namespace App\Support;

use App\Http\Controllers\MemoryOrderChangeController;
use App\Models\Orderweb;
use App\Models\Uid_history;
use App\Services\ForkLegUidChain;

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
            $bonusUid = self::mappedUid((string) $history->uid_bonusOrder);
            if ($bonusUid !== $dispatchUid) {
                $legs[] = ['uid' => $bonusUid, 'auth_role' => 'bonus'];
            }
        }

        if (!empty($authChoice['authorizationDouble']) && !empty($history->uid_doubleOrder)) {
            $doubleUid = self::mappedUid((string) $history->uid_doubleOrder);
            $existingUids = array_column($legs, 'uid');
            if (!in_array($doubleUid, $existingUids, true)) {
                $legs[] = ['uid' => $doubleUid, 'auth_role' => 'double'];
            }
        }

        return self::appendChainCancelLegs($legs, $history, $authChoice, $dispatchUid);
    }

    /**
     * Текущая нога вилки после пересоздания пары, не устаревший UID из цикла опроса.
     */
    public static function liveSiblingUid(?Uid_history $history, string $siblingLeg, $fallback = null): string
    {
        $fallbackUid = trim((string) $fallback);
        if ($history === null) {
            return $fallbackUid;
        }

        $fromHistory = $siblingLeg === 'double'
            ? trim((string) ($history->uid_doubleOrder ?? ''))
            : trim((string) ($history->uid_bonusOrder ?? ''));

        return $fromHistory !== '' ? $fromHistory : $fallbackUid;
    }

    /**
     * orderweb вилки всегда keyed по холду. PAS может прислать новый bonus/double UID.
     */
    public static function findOrderweb(string $uidA, string $uidB): ?Orderweb
    {
        foreach (self::lookupCandidates($uidA, $uidB) as $candidate) {
            $orderweb = Orderweb::where('dispatching_order_uid', $candidate)->first();
            if ($orderweb) {
                return $orderweb;
            }
        }

        $history = self::findHistory($uidA, $uidB);
        if ($history === null) {
            return null;
        }

        $hold = trim((string) ($history->uid_bonusOrderHold ?? ''));
        if ($hold === '') {
            return null;
        }

        return Orderweb::where('dispatching_order_uid', $hold)->first();
    }

    /**
     * В истории есть живая пара карта+нал. Одиночный cancel по одной ноге
     * должен закрыть обе, а не ждать orderweb с ключом этой ноги.
     */
    public static function hasLivePair(?Uid_history $history): bool
    {
        if ($history === null) {
            return false;
        }

        return trim((string) ($history->uid_bonusOrder ?? '')) !== ''
            && trim((string) ($history->uid_doubleOrder ?? '')) !== '';
    }

    public static function findHistory(string $uidA, string $uidB): ?Uid_history
    {
        $candidates = self::lookupCandidates($uidA, $uidB);

        foreach ($candidates as $candidate) {
            $history = Uid_history::where('uid_bonusOrderHold', $candidate)->first();
            if ($history) {
                return $history;
            }
        }

        foreach ($candidates as $candidate) {
            $history = Uid_history::where('uid_bonusOrder', $candidate)->first();
            if ($history) {
                return $history;
            }
        }

        foreach ($candidates as $candidate) {
            $history = Uid_history::where('uid_doubleOrder', $candidate)->first();
            if ($history) {
                return $history;
            }
        }

        return null;
    }

    /**
     * @param array<int, array{uid: string, auth_role: string}> $legs
     * @param array<string, mixed> $authChoice
     *
     * @return array<int, array{uid: string, auth_role: string}>
     */
    private static function appendChainCancelLegs(
        array $legs,
        Uid_history $history,
        array $authChoice,
        string $dispatchUid
    ): array {
        $existingUids = array_column($legs, 'uid');
        try {
            $chain = (new ForkLegUidChain())->allUids(
                $history->uid_bonusOrder ?? null,
                $history->uid_doubleOrder ?? null,
                $history->uid_bonusOrderHold ?? $dispatchUid
            );
            $memory = new MemoryOrderChangeController();
            $bonusSeed = trim((string) ($history->uid_bonusOrder ?? ''));
            $doubleSeed = trim((string) ($history->uid_doubleOrder ?? ''));
            $bonusLatest = $bonusSeed !== '' ? trim((string) $memory->findLatestOrderUid($bonusSeed)) : '';
            $doubleLatest = $doubleSeed !== '' ? trim((string) $memory->findLatestOrderUid($doubleSeed)) : '';
            $bonusFamily = array_values(array_unique(array_filter(array_merge(
                [$bonusSeed, $bonusLatest, $dispatchUid, trim((string) ($history->uid_bonusOrderHold ?? ''))],
                $bonusLatest !== '' ? $memory->collectPredecessorUids($bonusLatest) : []
            ))));
            $doubleFamily = array_values(array_unique(array_filter(array_merge(
                [$doubleSeed, $doubleLatest],
                $doubleLatest !== '' ? $memory->collectPredecessorUids($doubleLatest) : []
            ))));

            foreach ($chain as $chainUid) {
                $chainUid = trim((string) $chainUid);
                if ($chainUid === '' || in_array($chainUid, $existingUids, true)) {
                    continue;
                }
                if (!empty($authChoice['authorizationBonus']) && in_array($chainUid, $bonusFamily, true)) {
                    $legs[] = ['uid' => $chainUid, 'auth_role' => 'bonus'];
                    $existingUids[] = $chainUid;
                    continue;
                }
                if (!empty($authChoice['authorizationDouble']) && in_array($chainUid, $doubleFamily, true)) {
                    $legs[] = ['uid' => $chainUid, 'auth_role' => 'double'];
                    $existingUids[] = $chainUid;
                }
            }
        } catch (\Throwable $e) {
            return $legs;
        }

        return $legs;
    }

    /**
     * @return list<string>
     */
    private static function lookupCandidates(string $uidA, string $uidB): array
    {
        $memory = new MemoryOrderChangeController();
        $candidates = [];
        foreach ([$uidA, $uidB] as $raw) {
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }
            $candidates[] = $raw;
            try {
                $latest = trim((string) $memory->findLatestOrderUid($raw));
                if ($latest !== '') {
                    $candidates[] = $latest;
                    foreach ($memory->collectPredecessorUids($latest) as $previous) {
                        $previous = trim((string) $previous);
                        if ($previous !== '') {
                            $candidates[] = $previous;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // таблица memory_order_changes может отсутствовать в узких unit-тестах
            }
        }

        return array_values(array_unique($candidates));
    }

    private static function mappedUid(string $uid): string
    {
        $uid = trim($uid);
        if ($uid === '') {
            return '';
        }

        try {
            $mapped = trim((string) (new MemoryOrderChangeController())->show($uid));

            return $mapped !== '' ? $mapped : $uid;
        } catch (\Throwable $e) {
            return $uid;
        }
    }
}

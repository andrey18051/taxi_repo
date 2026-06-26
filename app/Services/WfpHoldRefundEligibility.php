<?php

namespace App\Services;

use App\City\SimpleCashlessDispatchStatusSync;
use App\Models\Orderweb;
use App\Models\Uid_history;
use Illuminate\Support\Facades\Log;

/**
 * Gate WFP hold void/refund on live dispatch cancel confirmation, not orderweb cache alone.
 */
final class WfpHoldRefundEligibility
{
    private DispatchOrderCancelService $cancelService;

    public function __construct(?DispatchOrderCancelService $cancelService = null)
    {
        $this->cancelService = $cancelService ?? new DispatchOrderCancelService();
    }

    /**
     * @param list<array<string, mixed>> $snapshots
     */
    public function allSnapshotsSettledForRefund(array $snapshots): bool
    {
        if ($snapshots === []) {
            return false;
        }

        foreach ($snapshots as $snapshot) {
            if (!$this->cancelService->isDispatchCancelSettled($snapshot)) {
                return false;
            }
        }

        return true;
    }

    public function mayRefundCanceledHold(Orderweb $orderweb, string $uid): bool
    {
        if ((string) $orderweb->closeReason !== '1') {
            return false;
        }

        if ($orderweb->server === 'my_server_api') {
            return true;
        }

        if (DispatchOrderCancelService::hasActiveCampaign($uid)) {
            Log::info('WfpHoldRefundEligibility: skip refund — active cancel campaign', ['uid' => $uid]);

            return false;
        }

        $snapshots = $this->collectDispatchSnapshots($orderweb, $uid);
        if ($snapshots === null) {
            Log::warning('WfpHoldRefundEligibility: skip refund — dispatch status unavailable', ['uid' => $uid]);

            return false;
        }

        if (!$this->allSnapshotsSettledForRefund($snapshots)) {
            $this->healOrderwebIfFalseCancel($orderweb, $snapshots, $uid);

            Log::info('WfpHoldRefundEligibility: skip refund — dispatch cancel not settled', [
                'uid' => $uid,
                'dispatch_close_reasons' => array_map(
                    static fn (array $s): int => (int) ($s['close_reason'] ?? -1),
                    $snapshots
                ),
            ]);

            return false;
        }

        return true;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function collectDispatchSnapshots(Orderweb $orderweb, string $uid): ?array
    {
        $uidHistory = Uid_history::where('uid_bonusOrderHold', $uid)->first()
            ?? Uid_history::where('uid_bonusOrder', $uid)->first();

        if ($uidHistory !== null) {
            $snapshots = [];
            $bonusUid = (string) ($uidHistory->uid_bonusOrder ?: $uid);
            $bonusSnapshot = SimpleCashlessDispatchStatusSync::fetchDispatchSnapshot($orderweb, $bonusUid);
            if ($bonusSnapshot === null) {
                return null;
            }
            $snapshots[] = $bonusSnapshot;

            $doubleUid = (string) ($uidHistory->uid_doubleOrder ?? '');
            if ($doubleUid !== '') {
                $doubleSnapshot = SimpleCashlessDispatchStatusSync::fetchDispatchSnapshot($orderweb, $doubleUid);
                if ($doubleSnapshot === null) {
                    return null;
                }
                $snapshots[] = $doubleSnapshot;
            }

            return $snapshots;
        }

        $snapshot = SimpleCashlessDispatchStatusSync::fetchDispatchSnapshot($orderweb, $uid);

        return $snapshot === null ? null : [$snapshot];
    }

    /**
     * @param list<array<string, mixed>> $snapshots
     */
    private function healOrderwebIfFalseCancel(Orderweb $orderweb, array $snapshots, string $uid): void
    {
        $primary = $snapshots[0] ?? null;
        if ($primary === null) {
            return;
        }

        if ($this->cancelService->isDispatchCancelSettled($primary)) {
            return;
        }

        $dispatchCloseReason = (int) ($primary['close_reason'] ?? -1);
        if (!in_array($dispatchCloseReason, [-1, 0], true)) {
            return;
        }

        if ((string) $orderweb->closeReason !== '1') {
            return;
        }

        $orderweb->closeReason = (string) $dispatchCloseReason;
        $orderweb->save();

        Log::info('WfpHoldRefundEligibility: healed orderweb closeReason after dispatch mismatch', [
            'uid' => $uid,
            'dispatch_close_reason' => $dispatchCloseReason,
        ]);
    }
}

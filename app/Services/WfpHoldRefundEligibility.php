<?php

namespace App\Services;

use App\City\SimpleCashlessDispatchStatusSync;
use App\Http\Controllers\MemoryOrderChangeController;
use App\Models\Orderweb;
use App\Models\Uid_history;
use App\Models\WfpInvoice;
use Illuminate\Support\Facades\Log;

/**
 * Gate WFP hold void/refund on live dispatch cancel confirmation, not orderweb cache alone.
 */
final class WfpHoldRefundEligibility
{
    /** @var DispatchOrderCancelService */
    private $cancelService;

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

    /**
     * Google Pay callback may replace wfp_order_id only when the invoice belongs to this order
     * and is not older than the hold already stored on orderweb.
     */
    public function mayRebindGooglePayHold(
        Orderweb $order,
        string $newOrderReference,
        ?string $currentOrderReference
    ): bool {
        if (!empty($order->cancel_timestamp)) {
            Log::info('WfpHoldRefundEligibility: skip GP rebind — order cancelled', [
                'uid' => (string) ($order->dispatching_order_uid ?? ''),
                'orderReference' => $newOrderReference,
            ]);

            return false;
        }

        $orderUid = (string) ($order->dispatching_order_uid ?? '');
        $newInvoice = WfpInvoice::where('orderReference', $newOrderReference)->first();

        if ($newInvoice !== null && $orderUid !== '') {
            $invoiceUid = (string) ($newInvoice->dispatching_order_uid ?? '');
            if ($invoiceUid !== '' && $invoiceUid !== $orderUid) {
                Log::info('WfpHoldRefundEligibility: skip GP rebind — invoice uid mismatch', [
                    'uid' => $orderUid,
                    'orderReference' => $newOrderReference,
                    'invoice_uid' => $invoiceUid,
                ]);

                return false;
            }
        }

        if ($newInvoice !== null
            && $order->created_at !== null
            && $newInvoice->created_at !== null
            && $newInvoice->created_at < $order->created_at->copy()->subMinute()) {
            Log::info('WfpHoldRefundEligibility: skip GP rebind — invoice older than orderweb', [
                'uid' => $orderUid,
                'orderReference' => $newOrderReference,
            ]);

            return false;
        }

        if ($currentOrderReference !== null
            && $currentOrderReference !== ''
            && $newInvoice !== null) {
            $currentInvoice = WfpInvoice::where('orderReference', $currentOrderReference)->first();
            if ($currentInvoice !== null
                && $currentInvoice->created_at !== null
                && $newInvoice->created_at !== null
                && $newInvoice->created_at < $currentInvoice->created_at) {
                Log::info('WfpHoldRefundEligibility: skip GP rebind — stale callback', [
                    'uid' => $orderUid,
                    'current' => $currentOrderReference,
                    'incoming' => $newOrderReference,
                ]);

                return false;
            }
        }

        return true;
    }

    /**
     * Void the previous hold only when it belonged to the same dispatch uid.
     */
    public function mayVoidSupersededGooglePayHold(Orderweb $order, string $oldOrderReference): bool
    {
        if ($oldOrderReference === '') {
            return false;
        }

        $orderUid = (string) ($order->dispatching_order_uid ?? '');
        $invoice = WfpInvoice::where('orderReference', $oldOrderReference)->first();
        if ($invoice === null) {
            return true;
        }

        $invoiceUid = (string) ($invoice->dispatching_order_uid ?? '');
        if ($invoiceUid !== '' && $orderUid !== '' && $invoiceUid !== $orderUid) {
            Log::info('WfpHoldRefundEligibility: skip void superseded hold — uid mismatch', [
                'uid' => $orderUid,
                'orderReference' => $oldOrderReference,
                'invoice_uid' => $invoiceUid,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Доплата картой: основной холд + add-cost — оба WaitingAuthComplete, не void'ить как superseded.
     */
    public function hasMultipleActiveHolds(string $dispatchingOrderUid): bool
    {
        $dispatchingOrderUid = trim($dispatchingOrderUid);
        if ($dispatchingOrderUid === '') {
            return false;
        }

        return WfpInvoice::where('dispatching_order_uid', $dispatchingOrderUid)
                ->where('transactionStatus', 'WaitingAuthComplete')
                ->count() > 1;
    }

    public function mayRefundSupersededMainHold(Orderweb $orderweb): bool
    {
        if ($orderweb->server === 'my_server_api') {
            return true;
        }

        $currentUid = (string) ($orderweb->dispatching_order_uid ?? '');
        if ($currentUid === '') {
            return true;
        }

        if ($this->hasMultipleActiveHolds($currentUid)) {
            Log::info('WfpHoldRefundEligibility: skip superseded hold refund — multiple active holds (add-cost)', [
                'uid' => $currentUid,
            ]);

            return false;
        }

        $predecessors = (new MemoryOrderChangeController)->collectPredecessorUids($currentUid);
        if ($predecessors === []) {
            return true;
        }

        foreach ($predecessors as $predecessorUid) {
            $snapshots = $this->collectDispatchSnapshots($orderweb, $predecessorUid);
            if ($snapshots === null || !$this->allSnapshotsSettledForRefund($snapshots)) {
                Log::info('WfpHoldRefundEligibility: skip superseded hold refund — predecessor not archived', [
                    'current_uid' => $currentUid,
                    'predecessor_uid' => $predecessorUid,
                ]);

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
                    static function (array $s): int {
                        return (int) ($s['close_reason'] ?? -1);
                    },
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

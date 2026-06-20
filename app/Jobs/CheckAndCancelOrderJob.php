<?php

namespace App\Jobs;

use App\City\PaymentFlow;
use App\City\SimpleCashlessPaymentWatch;
use App\Http\Controllers\AndroidTestOSMController;
use App\Http\Controllers\CentrifugoController;
use App\Http\Controllers\FCMController;
use App\Http\Controllers\MemoryOrderChangeController;
use App\Http\Controllers\PusherController;
use App\Http\Controllers\WfpController;
use App\Models\Orderweb;
use App\Models\WfpInvoice;
use App\Services\PaymentStatusNotifier;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckAndCancelOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uid;
    protected $app;
    protected $email;
    protected $city;

    public function __construct($uid, $app, $email, $city = 'OdessaTest')
    {
        $this->uid = $uid;
        $this->app = $app;
        $this->email = $email;
        $this->city = $city;
        Log::info("CheckAndCancelOrderJob created uid={$uid} app={$app} delay_check");
    }

    public function handle()
    {
        Log::info("CheckAndCancelOrderJob start uid={$this->uid}");

        try {
            $this->processOrderCancellation();
            Log::info("CheckAndCancelOrderJob done uid={$this->uid}");
        } catch (\Exception $e) {
            Log::error("CheckAndCancelOrderJob error uid={$this->uid}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function processOrderCancellation(): void
    {
        $uid = (new MemoryOrderChangeController)->show($this->uid);
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();

        if (!$order) {
            Log::error("CheckAndCancelOrderJob: order not found uid={$uid}");
            return;
        }

        if (!$this->isEligible($order)) {
            Log::info("CheckAndCancelOrderJob: skip ineligible uid={$uid} server={$order->server} flow={$order->payment_flow_mode}");
            return;
        }

        if ($order->required_time !== null) {
            Log::info("CheckAndCancelOrderJob: skip pre-order uid={$uid}");
            return;
        }

        if (!$this->isActiveForPaymentCancel($order)) {
            Log::info("CheckAndCancelOrderJob: closeReason={$order->closeReason}, skip uid={$uid}");
            return;
        }

        $paySystem = $order->pay_system ?? '';
        if (!in_array($paySystem, ['wfp_payment', 'google_pay_payment', 'card_payment', 'fondy_payment', 'mono_payment'], true)) {
            Log::info("CheckAndCancelOrderJob: not card pay_system={$paySystem}, skip uid={$uid}");
            return;
        }

        $merchantInfo = (new WfpController)->checkMerchantInfo($order);
        if (isset($merchantInfo['merchantAccount']) && $merchantInfo['merchantAccount'] === 'errorMerchantAccount') {
            $order->transactionStatus = 'errorMerchantAccount';
            $order->save();
            Log::warning("CheckAndCancelOrderJob: errorMerchantAccount uid={$uid}");
            $this->cancelUnpaidOrder($order, $uid);
            return;
        }

        $orderReference = $order->wfp_order_id;
        if ($orderReference === null || $orderReference === '') {
            Log::warning("CheckAndCancelOrderJob: no wfp_order_id uid={$uid}");
            $this->cancelUnpaidOrder($order, $uid);
            return;
        }

        $wfpCity = $this->resolveWfpCity($order);
        try {
            (new WfpController)->checkStatus($this->app, $wfpCity, $orderReference);
            Log::info("CheckAndCancelOrderJob: refreshed WFP status uid={$uid} ref={$orderReference}");
        } catch (\Throwable $e) {
            Log::warning("CheckAndCancelOrderJob: checkStatus failed uid={$uid}: " . $e->getMessage());
        }

        $invoice = WfpInvoice::where('orderReference', $orderReference)->first();
        if (!$invoice) {
            Log::warning("CheckAndCancelOrderJob: invoice missing ref={$orderReference}");
            $this->cancelUnpaidOrder($order, $uid);
            return;
        }

        $transactionStatus = $invoice->transactionStatus;
        Log::info("CheckAndCancelOrderJob: status={$transactionStatus} uid={$uid}");
        if ((new WfpController)->hasPendingAddCostPayment($uid, $orderReference)) {
            Log::info("CheckAndCancelOrderJob: pending add-cost payment, keep order uid={$uid}");
            return;
        }

        $allowedStatuses = ['WaitingAuthComplete', 'Approved'];
        if ($transactionStatus !== null && in_array($transactionStatus, $allowedStatuses, true)) {
            Log::info("CheckAndCancelOrderJob: payment ok, keep order uid={$uid}");
            return;
        }

        if ($transactionStatus === 'Declined') {
            PaymentStatusNotifier::notifyTransactionStatus(
                $transactionStatus,
                $uid,
                $this->app,
                $this->email
            );
        }

        $this->cancelUnpaidOrder($order, $uid, $transactionStatus === 'Declined');
    }

    private function cancelUnpaidOrder(Orderweb $order, string $uid, bool $paymentDeclined = false): void
    {
        $application = $this->resolveApplicationLabel($order);
        $city = $this->resolveCancelCity($order);

        Log::info("CheckAndCancelOrderJob: cancel unpaid uid={$uid} city={$city}");

        try {
            (new AndroidTestOSMController)->webordersCancel($uid, $city, $application, true);
        } catch (\Throwable $e) {
            Log::error("CheckAndCancelOrderJob: webordersCancel failed uid={$uid}: " . $e->getMessage());
        }

        $this->cleanupOrder($uid, $paymentDeclined);
    }

    private function cleanupOrder(string $uid, bool $paymentDeclined = false): void
    {
        Log::info("CheckAndCancelOrderJob: cleanup uid={$uid}");

        try {
            $fcmController = new FCMController();
            $fcmController->deleteDocumentFromFirestore($uid);
            $fcmController->deleteDocumentFromFirestoreOrdersTakingCancel($uid);
            $fcmController->deleteDocumentFromSectorFirestore($uid);
            $fcmController->writeDocumentToHistoryFirestore($uid, 'cancelled');

            $order = Orderweb::where('dispatching_order_uid', $uid)->first();
            if ($order) {
                $order->cancel_timestamp = Carbon::now();
                if ((string) $order->closeReason === '100' || $order->closeReason === '') {
                    $order->closeReason = '1';
                }
                $order->save();
            }

            $simpleCashless = $order !== null
                && PaymentFlow::normalize($order->payment_flow_mode ?? 0) === PaymentFlow::SIMPLE;

            if ($this->email && (!$paymentDeclined || $simpleCashless)) {
                $appLabel = $order !== null
                    ? $this->resolveApplicationLabel($order)
                    : ($this->app ?? 'PAS4');
                if ($simpleCashless && $order !== null) {
                    SimpleCashlessPaymentWatch::notifyClientOrderCanceled($order, $appLabel);
                } else {
                    (new PusherController)->sentCanceledStatus($appLabel, $this->email, $uid);
                    (new CentrifugoController)->sentCanceledStatus($appLabel, $this->email, $uid);
                }
            }
        } catch (\Exception $e) {
            Log::error("CheckAndCancelOrderJob: cleanup error uid={$uid}: " . $e->getMessage());
        }
    }

    private function resolveWfpCity(Orderweb $order): string
    {
        if (!empty($this->city) && $this->city !== 'all') {
            return $this->city;
        }

        return $this->resolveCancelCity($order);
    }

    private function resolveCancelCity(Orderweb $order): string
    {
        if ($order->city === 'all' || $order->city === 'city_kiev') {
            return 'Kyiv City';
        }

        $cityMap = [
            'city_odessa' => 'OdessaTest',
            'city_cherkassy' => 'Cherkasy Oblast',
            'city_zaporizhzhia' => 'Zaporizhzhia',
            'city_dnipro' => 'DniproTest',
        ];

        return $cityMap[$order->city] ?? 'OdessaTest';
    }

    private function isEligible(Orderweb $order): bool
    {
        if (PaymentFlow::normalize($order->payment_flow_mode ?? 0) === PaymentFlow::SIMPLE) {
            return true;
        }

        return $order->server === 'my_server_api';
    }

    private function isActiveForPaymentCancel(Orderweb $order): bool
    {
        return !in_array((string) $order->closeReason, ['1'], true);
    }

    private function resolveApplicationLabel(Orderweb $order): string
    {
        $fromOrder = SimpleCashlessPaymentWatch::resolveApplicationLabel($order);
        if ($fromOrder !== null) {
            return $fromOrder;
        }

        if (is_string($this->app) && $this->app !== '') {
            return $this->app;
        }

        return 'PAS4';
    }
}


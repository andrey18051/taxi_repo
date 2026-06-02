<?php

namespace App\Jobs;

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

        if ($order->server !== 'my_server_api') {
            Log::info("CheckAndCancelOrderJob: skip non my_server_api uid={$uid}");
            return;
        }

        if ($order->required_time !== null) {
            Log::info("CheckAndCancelOrderJob: skip pre-order uid={$uid}");
            return;
        }

        if ($order->auto !== null) {
            Log::info("CheckAndCancelOrderJob: car assigned, skip uid={$uid}");
            return;
        }

        if (!in_array((string) $order->closeReason, ['100', ''], true)) {
            Log::info("CheckAndCancelOrderJob: closeReason={$order->closeReason}, skip uid={$uid}");
            return;
        }

        $paySystem = $order->pay_system ?? '';
        if (!in_array($paySystem, ['wfp_payment', 'card_payment', 'fondy_payment', 'mono_payment'], true)) {
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
        $application = $this->resolveApplicationConfig($order);
        $city = $this->resolveCancelCity($order);

        Log::info("CheckAndCancelOrderJob: cancel unpaid uid={$uid} city={$city}");

        try {
            (new AndroidTestOSMController)->webordersCancel($uid, $city, $application);
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

            if (!$paymentDeclined && $this->email) {
                (new PusherController)->sentCanceledStatus($this->app, $this->email, $uid);
                (new CentrifugoController)->sentCanceledStatus($this->app, $this->email, $uid);
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

    private function resolveApplicationConfig(Orderweb $order): string
    {
        switch ($order->comment) {
            case 'taxi_easy_ua_pas1':
                return config('app.X-WO-API-APP-ID-PAS1');
            case 'taxi_easy_ua_pas2':
                return config('app.X-WO-API-APP-ID-PAS2');
            case 'taxi_easy_ua_pas4':
                return config('app.X-WO-API-APP-ID-PAS4');
            default:
                return config('app.X-WO-API-APP-ID-PAS5');
        }
    }
}

<?php

namespace App\Jobs;

use App\Http\Controllers\WfpController;
use App\Models\Orderweb;
use App\Models\WfpInvoice;
use App\Services\PaymentStatusNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SimplePollStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderReference;
    protected $dispatching_order_uid;
    protected $application;
    protected $email;
    protected $city;
    protected $attempts = 0;
    protected $maxTime;
    protected $checkInterval;

    public function __construct(
        $orderReference,
        $dispatching_order_uid,
        $application,
        $email,
        $city = 'OdessaTest',
        $attempts = 0
    ) {
        $this->orderReference = $orderReference;
        $this->dispatching_order_uid = $dispatching_order_uid;
        $this->application = $application;
        $this->email = $email;
        $this->city = $city;
        $this->attempts = $attempts;
        $this->maxTime = (int) config('orders.my_server_api_payment_poll_max_seconds', 60);
        $this->checkInterval = (int) config('orders.my_server_api_payment_poll_interval_seconds', 3);

        Log::info("SimplePollStatusJob #{$this->attempts} ref={$orderReference} max={$this->maxTime}s");
    }

    /**
     * @throws \Pusher\PusherException
     * @throws \Pusher\ApiErrorException
     */
    public function handle()
    {
        $this->attempts++;
        $currentTime = $this->attempts * $this->checkInterval;

        Log::info("SimplePollStatusJob check #{$this->attempts} ({$currentTime}/{$this->maxTime}s) ref={$this->orderReference}");

        $order = Orderweb::where('dispatching_order_uid', $this->dispatching_order_uid)->first();
        if ($order) {
            $wfpCity = $this->resolveWfpCity($order);
            try {
                (new WfpController)->checkStatus($this->application, $wfpCity, $this->orderReference);
            } catch (\Throwable $e) {
                Log::warning('SimplePollStatusJob checkStatus: ' . $e->getMessage());
            }
        }

        $invoice = WfpInvoice::where('orderReference', $this->orderReference)->first();

        if (!$invoice) {
            Log::warning('SimplePollStatusJob: invoice not found');
            $this->rescheduleOrExit($currentTime);
            return;
        }

        $transactionStatus = $invoice->transactionStatus;
        if (!$transactionStatus) {
            Log::warning('SimplePollStatusJob: transactionStatus empty');
            $this->rescheduleOrExit($currentTime);
            return;
        }

        switch ($transactionStatus) {
            case 'Declined':
                Log::info('SimplePollStatusJob: Declined — notify only, 1-min job will cancel if unpaid');
                $this->sendPushNotification($transactionStatus);
                return;

            case 'WaitingAuthComplete':
            case 'Approved':
                Log::info("SimplePollStatusJob: paid status={$transactionStatus}");
                return;

            default:
                Log::debug("SimplePollStatusJob: pending status={$transactionStatus}");
                $this->rescheduleOrExit($currentTime);
        }
    }

    private function rescheduleOrExit(int $currentTime): void
    {
        if ($currentTime < $this->maxTime) {
            self::dispatch(
                $this->orderReference,
                $this->dispatching_order_uid,
                $this->application,
                $this->email,
                $this->city,
                $this->attempts
            )->delay(now()->addSeconds($this->checkInterval));
            return;
        }

        Log::warning("SimplePollStatusJob: poll timeout ({$this->maxTime}s), dispatch CheckAndCancelOrderJob");

        CheckAndCancelOrderJob::dispatch(
            $this->dispatching_order_uid,
            $this->application,
            $this->email,
            $this->city
        )->onQueue('high');
    }

    /**
     * @throws \Pusher\PusherException
     * @throws \Pusher\ApiErrorException
     */
    private function sendPushNotification(?string $transactionStatus = null): void
    {
        if ($transactionStatus === null) {
            $transactionStatus = 'Declined';
        }

        PaymentStatusNotifier::notifyTransactionStatus(
            $transactionStatus,
            $this->dispatching_order_uid,
            $this->application,
            $this->email
        );
    }

    private function resolveWfpCity(Orderweb $order): string
    {
        if (!empty($this->city) && $this->city !== 'all') {
            return $this->city;
        }

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
}


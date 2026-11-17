<?php

namespace App\Jobs;

use App\Http\Controllers\MessageSentController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Models\Orderweb;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StartStatusPaymentReview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const DELAY_SECONDS = 60;

    private $orderId;

    /**
     * @return void
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
        $this->delay(self::DELAY_SECONDS);
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        $order = Orderweb::where('dispatching_order_uid', $this->orderId)->first();
        if ($order !== null && ($order->pay_system ?? '') === 'google_pay_payment') {
            Log::info('StartStatusPaymentReview: skip google_pay (hold on client before order)', [
                'uid' => $this->orderId,
            ]);

            return;
        }

        (new UniversalAndroidFunctionController)->cancelOnlyCardPayUid($this->orderId);
//        Http::get('https://m.easy-order-taxi.site/cancelOnlyCardPayUid' .$this->orderId );

    }
}

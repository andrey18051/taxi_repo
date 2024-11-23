<?php

namespace App\Jobs;

use App\Http\Controllers\UniversalAndroidFunctionController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StartDoubleStatusPaymentReview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $orderId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::debug("StartDoubleStatusPaymentReview");
        try {
            (new UniversalAndroidFunctionController)->cancelOnlyDoubleUid($this->orderId);
            Log::debug("StartDoubleStatusPaymentReview job finished successfully for order ID: {$this->orderId}");
        } catch (\Exception $e) {
            Log::error("StartDoubleStatusPaymentReview job failed for order ID: {$this->orderId} with error: "
                    . $e->getMessage());
        }
    }
}

<?php

namespace App\Jobs;

use App\Http\Controllers\MessageSentController;
use App\Http\Controllers\WfpController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefundSettleCardPayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $params;
    protected $orderReference;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($params, $orderReference)
    {
        $this->params = $params;
        $this->orderReference = $orderReference;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $result = (new WfpController)->refundSettleJob($this->params, $this->orderReference);

        if ($result == null) {
            Log::info("Задача RefundSettleCardPayJob $this->orderReference завершена");
            $messageAdmin = "Задача RefundSettleCardPayJob $this->orderReference завершена";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);
            return;
        }
        Log::info("Задача RefundSettleCardPayJob $this->orderReference завершена");

    }
}

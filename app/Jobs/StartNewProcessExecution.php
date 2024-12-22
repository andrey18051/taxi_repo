<?php

namespace App\Jobs;

use App\Http\Controllers\MessageSentController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StartNewProcessExecution implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;

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
        $messageAdmin = "Запущена вилка для заказа $this->orderId";
        (new MessageSentController)->sentMessageAdmin($messageAdmin);
        $result = (new UniversalAndroidFunctionController)->startNewProcessExecutionStatusEmu($this->orderId);
        Log::debug("StartNewProcessExecution job finished successfully for order ID: {$this->orderId}");

        if ($result === null) {
            Log::info("Задача $messageAdmin завершена");
            $messageAdmin = "Задача $messageAdmin завершена";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);
            return;
        }

        Log::info("Задача $messageAdmin успешно завершена.");

    }
}


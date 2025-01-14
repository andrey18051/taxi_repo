<?php

namespace App\Jobs;

use App\Http\Controllers\MessageSentController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Models\DoubleOrder;
use App\Models\Uid_history;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class StartNewProcessExecution implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;
    protected $jobId; // Поле для сохранения ID задачи

    /**
     * Create a new job instance.
     *
     * @param int $orderId
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        // Получаем jobId для текущей задачи
        $this->jobId = $this->job->getJobId();


            // Отправляем сообщение администратору
        $messageAdmin = "+++ Запущена вилка для заказа $this->orderId Job ID: {$this->jobId} started for order ID: {$this->orderId}";
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        // Запускаем процесс
//        $result = (new UniversalAndroidFunctionController)->startNewProcessExecutionStatusEmu($this->orderId);
        $result = (new UniversalAndroidFunctionController)->startNewProcessExecutionStatusJob($this->orderId, $this->jobId);

        if ($result === "exit") {
            Log::info("Задача завершена. Job ID: {$this->jobId}");
            $messageAdmin = "Задача завершена для заказа $this->orderId (Job ID: {$this->jobId})";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);
            try {
                sleep(5);
                $doubleOrderRecord = DoubleOrder::find($this->orderId);
                if ($doubleOrderRecord) {
                    $doubleOrderRecord->delete();
                    $messageAdmin = "Вилка $this->orderId (Job ID: {$this->jobId}) удалена";
                    (new MessageSentController)->sentMessageAdmin($messageAdmin);
                }
            } catch (\Exception $e) {
                // Handle the exception (log it, rethrow it, etc.)
            }
            return;
        }


        try {
            sleep(5);
            $doubleOrderRecord = DoubleOrder::find($this->orderId);
            if ($doubleOrderRecord) {
                $doubleOrderRecord->delete();
                $messageAdmin = "Вилка $this->orderId (Job ID: {$this->jobId}) удалена";
                (new MessageSentController)->sentMessageAdmin($messageAdmin);
            }
        } catch (\Exception $e) {
            // Handle the exception (log it, rethrow it, etc.)
        }

    }



}


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
        $this->jobId = $this->job->getJobId();

        $messageAdmin = "!!!+++13032025 Запущена вилка для заказа $this->orderId Job ID: {$this->jobId} started for order ID: {$this->orderId}";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        try {
            Log::info("Запуск startNewProcessExecutionStatusJob для orderId: {$this->orderId}, jobId: {$this->jobId}");
            $result = (new UniversalAndroidFunctionController)->startNewProcessExecutionStatusJob($this->orderId, $this->jobId);
            Log::info("Результат startNewProcessExecutionStatusJob: " . ($result ?? 'null'));
        } catch (\Exception $e) {
            Log::error("Ошибка в startNewProcessExecutionStatusJob для orderId: {$this->orderId}, jobId: {$this->jobId}: " . $e->getMessage());
            throw $e; // Повторно выбросить исключение, чтобы задание пометилось как неудачное
        }

        if ($result === "exit") {
            $messageAdmin = "Задача завершена для заказа $this->orderId (Job ID: {$this->jobId})";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
            try {
                sleep(5);
                $doubleOrderRecord = DoubleOrder::find($this->orderId);
                if ($doubleOrderRecord) {
                    $doubleOrderRecord->delete();
                    $messageAdmin = "Вилка $this->orderId (Job ID: {$this->jobId}) удалена";
                    (new MessageSentController)->sentMessageAdminLog($messageAdmin);
                }
            } catch (\Exception $e) {
                Log::error("Ошибка при удалении DoubleOrder для orderId: {$this->orderId}: " . $e->getMessage());
            }
            return;
        }

        try {
            sleep(5);
            $doubleOrderRecord = DoubleOrder::find($this->orderId);
            if ($doubleOrderRecord) {
                $doubleOrderRecord->delete();
                $messageAdmin = "Вилка $this->orderId (Job ID: {$this->jobId}) удалена";
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
            }
        } catch (\Exception $e) {
            Log::error("Ошибка при удалении DoubleOrder (второй блок) для orderId: {$this->orderId}: " . $e->getMessage());
        }
    }



}


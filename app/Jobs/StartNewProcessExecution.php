<?php

namespace App\Jobs;

use App\Http\Controllers\MessageSentController;
use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Models\DoubleOrder;
use App\Models\Uid_history;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
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

        Log::info("Job ID: {$this->jobId} started for order ID: {$this->orderId}");

        // Сохраняем или обновляем запись в новой таблице job_order
        DB::beginTransaction(); // Начинаем транзакцию

        try {
            // Проверяем, существует ли уже запись с таким jobId в таблице job_order
//            $jobOrder = DB::table('job_order')
//                ->where('job_id', $this->jobId)
//                ->first();
//
//            if ($jobOrder) {
//                // Если запись существует, обновляем поле order_id
//                DB::table('job_order')
//                    ->where('job_id', $this->jobId)
//                    ->update(['order_id' => $this->orderId]);
//
//                Log::info("Записано поле orderId для Job ID: {$this->jobId} в таблицу job_order.");
//            } else {
//                // Если запись не найдена, добавляем новую запись в таблицу job_order
//                DB::table('job_order')->insert([
//                    'job_id' => $this->jobId,
//                    'order_id' => $this->orderId,
//                    'created_at' => now(),
//                    'updated_at' => now(),
//                ]);
//                Log::info("Добавлена новая запись с orderId для Job ID: {$this->jobId} в таблицу job_order.");
//            }
//
//            DB::commit(); // Подтверждаем транзакцию

            // Отправляем сообщение администратору
            $messageAdmin = "!!! Запущена вилка для заказа $this->orderId Job ID: {$this->jobId} started for order ID: {$this->orderId}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            // Запускаем процесс
            $result = (new UniversalAndroidFunctionController)->startNewProcessExecutionStatusEmu($this->orderId);
            Log::debug("StartNewProcessExecution job finished successfully for order ID: {$this->orderId}");

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

            Log::info("Задача успешно завершена. Job ID: {$this->jobId}");
        } catch (\Exception $e) {
            DB::rollBack(); // Откатываем транзакцию в случае ошибки
            Log::error("Ошибка при обработке Job ID: {$this->jobId} - " . $e->getMessage());
            throw $e; // Пробрасываем ошибку дальше
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


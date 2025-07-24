<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ClearFailedSendTelegramJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3; // Уменьшено до 3 попыток
    public $timeout = 30; // Таймаут 30 секунд

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('low'); // Устанавливаем очередь low по умолчанию
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Блокировка для предотвращения одновременной очистки
        $lock = Cache::lock('clear_failed_jobs', 10);
        if ($lock->get()) {
            try {
                $deleted = DB::table('failed_jobs')
                    ->where('payload', 'like', '%SendTelegramMessageJob%')
                    ->delete();

                Log::info("Cleared {$deleted} failed SendTelegramMessageJob records from failed_jobs.");
                $lock->release();
            } catch (Exception $e) {
                $lock->release();
                Log::error("Failed to clear failed_jobs: {$e->getMessage()}");
                $this->fail($e); // Пометить задачу как неуспешную
            }
        } else {
            Log::warning("Lock not acquired for clear_failed_jobs, retrying in 10 seconds");
            $this->release(10); // Повторная попытка через 10 секунд
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception)
    {
        Log::error("ClearFailedSendTelegramJobs failed: {$exception->getMessage()}");
        // Дополнительная логика, например, уведомление через Slack
    }
}

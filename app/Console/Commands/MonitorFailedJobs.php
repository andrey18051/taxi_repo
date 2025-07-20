<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Notifications\FailedJobsAlert;
use App\Http\Controllers\TelegramController;

class MonitorFailedJobs extends Command
{
    protected $signature = 'queue:monitor-failed';
    protected $description = 'Monitor failed jobs, send notifications, and clean up failed_jobs table';

    public function handle()
    {
        // Получаем провалившиеся задачи для SendTelegramMessageJob
        $failedJobs = DB::table('failed_jobs')
            ->where('queue', 'default')
            ->get();

        $failedJobsCount = $failedJobs->count();
        $cacheKey = 'last_failed_jobs_count';
        $lastFailedJobsCount = Cache::get($cacheKey, 0);

        Log::debug("Checking failed jobs", [
            'current_count' => $failedJobsCount,
            'last_count' => $lastFailedJobsCount,
            'failed_jobs' => $failedJobs->pluck('uuid')->toArray(),
        ]);

        // Проверяем, есть ли провалившиеся задачи
        if ($failedJobsCount > 0) {
            // Сохраняем количество в кэше, если изменилось
            if ($failedJobsCount !== $lastFailedJobsCount) {
                Cache::put($cacheKey, $failedJobsCount, now()->addHours(24));

                // Формирование сообщения
                $messageAdmin = "Внимание! Обнаружено {$failedJobsCount} провалившихся задач SendTelegramMessageJob в очереди.";

                // Отправка уведомления по email
                try {
                    Notification::route('mail', 'taxi.easy.ua.sup@gmail.com')
                        ->notify(new FailedJobsAlert($failedJobsCount));
                    Log::info("Email notification sent for {$failedJobsCount} failed jobs");
                } catch (\Exception $e) {
                    Log::error("Failed to send email notification: {$e->getMessage()}");
                }

                // Отправка сообщения в Telegram
                try {
                    (new TelegramController())->sendMeMessage($messageAdmin);
                    Log::debug("Sent message to Telegram: {$messageAdmin}");
                } catch (\Exception $e) {
                    Log::error("Failed to send Telegram message: {$e->getMessage()}");
                }
            }

        } else {
            Log::debug("No failed jobs detected for SendTelegramMessageJob");
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use App\Notifications\FailedJobsAlert;
use App\Http\Controllers\TelegramController; // Импорт TelegramController

class MonitorFailedJobs extends Command
{
    protected $signature = 'queue:monitor-failed';
    protected $description = 'Monitor failed jobs and send notifications';

    public function handle()
    {
        $failedJobs = DB::table('failed_jobs')->count();

        if ($failedJobs > 0) {
            // Отправка уведомления по email
            try {
                Notification::route('mail', 'taxi.easy.ua.sup@gmail.com')
                    ->notify(new FailedJobsAlert($failedJobs));
            } catch (\Exception $e) {
                Log::error("Failed to send email notification: {$e->getMessage()}");
            }

            // Формирование сообщения для Telegram
            $messageAdmin = "Внимание! Обнаружено {$failedJobs} провалившихся задач в очереди.";

            // Отправка сообщения в Telegram
            try {
                (new TelegramController())->sendMeMessage($messageAdmin);
                Log::debug("Sent message to Telegram: {$messageAdmin}");
            } catch (\Exception $e) {
                Log::error("Failed to send Telegram message: {$e->getMessage()}");
            }
        }
    }
}

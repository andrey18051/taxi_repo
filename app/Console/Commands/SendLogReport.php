<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\LogReportMail;

class SendLogReport extends Command
{
    protected $signature = 'logs:send';
    protected $description = 'Отправить файл логов на почту';

    public function handle()
    {
        $filePath = '/usr/share/nginx/html/laravel_logs/laravel.log';

        if (file_exists($filePath) && filesize($filePath) > 0) {
            $recipient = env('LOG_REPORT_EMAIL', 'taxi.easy.ua.sup@gmail.com');

            try {
                // Отправляем лог на почту
                Mail::to($recipient)->send(new LogReportMail($filePath));

                // Удаляем файл после успешной отправки
                if (unlink($filePath)) {
                    $this->info('Файл логов успешно отправлен и удалён.');
                } else {
                    $this->error('Файл логов отправлен, но не удалось удалить файл.');
                }

            } catch (\Exception $e) {
                $this->error('Ошибка при отправке файла логов: ' . $e->getMessage());
            }
        } else {
            $this->warn('Файл логов отсутствует или пуст!');
        }


        return 0;
    }
}

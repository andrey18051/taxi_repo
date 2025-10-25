<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\File;
use App\Mail\LogReportMail;

class SendLogReport extends Command
{
    protected $signature = 'logs:send';
    protected $description = 'Отправить ссылку на лог-файл на почту и удалить старые логи';

    public function handle()
    {
        $logsDir = '/usr/share/nginx/html/laravel_logs';
        $filePath = $logsDir . '/laravel.log';
        $publicUrlBase = config('app.url') . '/laravel_logs';

        if (file_exists($filePath) && filesize($filePath) > 0) {
            $recipient = env('LOG_REPORT_EMAIL', 'taxi.easy.ua.sup@gmail.com');

            try {
                // 🔹 Архивируем текущий лог
                $date = date('Y-m-d_H-i-s');
                $archiveName = "laravel_log_{$date}.log";
                $archivePath = $logsDir . '/' . $archiveName;

                rename($filePath, $archivePath);

                // 🔹 Формируем публичную ссылку
                $logUrl = "{$publicUrlBase}/{$archiveName}";

                // 🔹 Отправляем письмо со ссылкой
                Mail::to($recipient)->send(new LogReportMail($logUrl));

                $this->info("Ссылка на лог отправлена: {$logUrl}");

                // 🔹 После отправки — создаём новый пустой лог
                file_put_contents($filePath, '');

                // 🔹 Очищаем старые архивы (старше 7 дней)
                $this->clearOldArchives($logsDir);

            } catch (\Exception $e) {
                $this->error('Ошибка при отправке ссылки на лог: ' . $e->getMessage());
            }
        } else {
            $this->warn('Файл логов отсутствует или пуст!');
        }

        return 0;
    }

    /**
     * Удаляет архивные логи старше 7 дней.
     */
    private function clearOldArchives(string $logsDir, int $days = 7): void
    {
        $deletedCount = 0;
        $now = time();

        foreach (glob($logsDir . '/laravel_log_*.log') as $oldFile) {
            if (is_file($oldFile)) {
                $fileAge = $now - filemtime($oldFile);
                if ($fileAge > ($days * 86400)) {
                    unlink($oldFile);
                    $deletedCount++;
                }
            }
        }

        if ($deletedCount > 0) {
            $this->info("🧹 Удалено старых логов: {$deletedCount}");
        } else {
            $this->info("✅ Старых логов для удаления нет.");
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\LogReportMail;

class SendLogReport extends Command
{
    protected $signature = 'logs:send';
    protected $description = 'Отправить ссылку на лог-файл на почту и удалить старые логи';

    public function handle()
    {
        $logsDir = '/usr/share/nginx/html/laravel_logs';
        $filePath = $logsDir . '/laravel.log';

        if (file_exists($filePath) && filesize($filePath) > 0) {
            $recipient = env('LOG_REPORT_EMAIL', 'taxi.easy.ua.sup@gmail.com');

            try {
                // Архивируем текущий лог
                $date = date('Y-m-d_H-i-s');
                $archiveName = "laravel_log_{$date}.log";
                $archivePath = $logsDir . '/' . $archiveName;

                copy($filePath, $archivePath);
                file_put_contents($filePath, ''); // Очищаем текущий лог

                // 🔥 ИСПРАВЛЕНО: используем маршрут Laravel вместо прямой ссылки
                $logUrl = route('logs.download', ['filename' => $archiveName]);

                // Удаляем архивы старше 30 дней
                $deletedCount = $this->clearOldArchives($logsDir, 30);

                // Отправляем письмо
                Mail::to($recipient)->send(new LogReportMail($logUrl, $deletedCount));

                $this->info("✅ Ссылка на лог отправлена: {$logUrl}");

                if ($deletedCount > 0) {
                    $this->info("🧹 Удалено старых архивов: {$deletedCount}");
                }

            } catch (\Exception $e) {
                $this->error('❌ Ошибка: ' . $e->getMessage());
            }
        } else {
            $this->warn('⚠️ Файл логов отсутствует или пуст!');
        }

        return 0;
    }

    private function clearOldArchives(string $logsDir, int $days = 30): int
    {
        $deletedCount = 0;
        $now = time();
        $expireTime = $days * 86400;

        foreach (glob($logsDir . '/laravel_log_*.log') as $oldFile) {
            if (is_file($oldFile)) {
                $fileAge = $now - filemtime($oldFile);
                if ($fileAge > $expireTime) {
                    if (unlink($oldFile)) {
                        $deletedCount++;
                        $this->line("   Удалён: " . basename($oldFile));
                    }
                }
            }
        }

        return $deletedCount;
    }
}

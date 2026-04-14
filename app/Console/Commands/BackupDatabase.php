<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\BackupReportMail;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Создать дамп базы данных и отправить ссылку на почту';

    public function handle()
    {
        $dbName = env('DB_DATABASE', 'db_taxi');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '18And051971');
        $dbHost = env('DB_HOST', '127.0.0.1');

        $backupDir = '/usr/share/nginx/html/taxi/public/backups';
        $backupFile = $backupDir . '/db_taxi_current.sql';
        $publicUrl = config('app.url') . '/backups/db_taxi_current.sql';
        $recipient = env('LOG_REPORT_EMAIL', 'taxi.easy.ua.sup@gmail.com');

        // Создаем директорию если нет
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $this->info("Создание дампа базы данных: {$dbName}");

        // Создаем дамп
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s --no-tablespaces --skip-add-drop-table --insert-ignore --force %s 2>/dev/null > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($backupFile)
        );

        system($command, $returnCode);

        // Проверяем результат
        if ($returnCode === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
            $size = round(filesize($backupFile) / 1048576, 2); // В МБ

            // Отправляем письмо со ссылкой
            Mail::to($recipient)->send(new BackupReportMail($publicUrl, $size, $dbName));

            $this->info("✅ Дамп создан: {$publicUrl}");
            $this->info("📊 Размер: {$size} MB");

            // Очищаем старые бэкапы (старше 7 дней)
            $this->clearOldBackups($backupDir);

        } else {
            $this->error("❌ Ошибка создания дампа");
            return 1;
        }

        return 0;
    }

    /**
     * Удаляет старые бэкапы старше N дней
     */
    private function clearOldBackups(string $backupDir, int $days = 7): void
    {
        $deletedCount = 0;
        $now = time();

        foreach (glob($backupDir . '/db_taxi_*.sql') as $oldFile) {
            if (is_file($oldFile)) {
                $fileAge = $now - filemtime($oldFile);
                if ($fileAge > ($days * 86400)) {
                    unlink($oldFile);
                    $deletedCount++;
                }
            }
        }

        if ($deletedCount > 0) {
            $this->info("🧹 Удалено старых бэкапов: {$deletedCount}");
        }
    }
}

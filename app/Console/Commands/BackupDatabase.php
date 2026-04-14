<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\BackupReportMail;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Создать дамп базы данных (только таблицы) и отправить ссылку на почту';

    public function handle()
    {
        $dbName = env('DB_DATABASE', 'db_taxi');
        $dbUser = env('DB_USERNAME', 'admin');
        $dbPass = env('DB_PASSWORD', '18And051971');
        $dbHost = env('DB_HOST', '127.0.0.1');

        $backupDir = '/usr/share/nginx/html/taxi/public/backups';
        $backupFile = $backupDir . '/db_taxi_current.sql';
        $publicUrl = config('app.url') . '/backups/db_taxi_current.sql';

        // Массив получателей
        $recipients = [
            'taxi.easy.ua.sup@gmail.com',
            'cartaxi4@gmail.com'
        ];

        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $this->info("Создание дампа базы данных: {$dbName}");

        // Получаем список только таблиц (исключаем вьюхи)
        $tables = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tableNames = [];
        foreach ($tables as $table) {
            $tableNames[] = reset($table);
        }

        $this->info("Найдено таблиц: " . count($tableNames));

        if (empty($tableNames)) {
            $this->error("Таблицы не найдены");
            return 1;
        }

        // Создаем дамп только таблиц
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s --no-tablespaces --skip-add-drop-table --insert-ignore --force %s %s 2>/dev/null > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            implode(' ', array_map('escapeshellarg', $tableNames)),
            escapeshellarg($backupFile)
        );

        $this->info("Выполнение дампа...");
        exec($command, $output, $returnCode);

        if (file_exists($backupFile) && filesize($backupFile) > 0) {
            $size = round(filesize($backupFile) / 1048576, 2);

            $this->info("✅ Дамп создан: {$backupFile}");
            $this->info("📊 Размер: {$size} MB");

            // Отправляем письмо всем получателям
            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient)->send(new BackupReportMail($publicUrl, $size, $dbName));
                    $this->info("✅ Письмо отправлено на: {$recipient}");
                } catch (\Exception $e) {
                    $this->error("Ошибка отправки письма на {$recipient}: " . $e->getMessage());
                }
            }

            $this->clearOldBackups($backupDir);
        } else {
            $this->error("❌ Ошибка создания дампа");
            $this->error("Код возврата: " . $returnCode);
            return 1;
        }

        return 0;
    }

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

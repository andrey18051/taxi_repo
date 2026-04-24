<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class LogDownloadController extends Controller
{
    public function download($filename)
    {
        $logsDir = '/usr/share/nginx/html/laravel_logs';
        $filePath = $logsDir . '/' . $filename;

        // Защита: разрешаем скачивать только .log файлы с префиксом laravel_log_
        if (!preg_match('/^laravel_log_.+\.log$/', $filename)) {
            abort(404, 'Некорректное имя файла');
        }

        if (!file_exists($filePath)) {
            abort(404, 'Файл лога не найден или уже удалён (хранятся только за последние 30 дней)');
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}

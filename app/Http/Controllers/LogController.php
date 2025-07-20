<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Mail\LogReportMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class LogController extends Controller
{
    public function sendLogs(): \Illuminate\Http\JsonResponse
    {
        $filePath = '/usr/share/nginx/html/laravel_logs/laravel.log';

        if (!file_exists($filePath) || filesize($filePath) === 0) {
            return response()->json(['message' => 'Файл логов отсутствует или пуст.'], 404);
        }

        // Временный файл для последних 100 строк
        $tempLogFile = '/usr/share/nginx/html/laravel_logs/laravel_tail.log';

        // Получаем последние 100 строк (используем команду tail)
        $lines = 100;
        exec("tail -n {$lines} " . escapeshellarg($filePath) . " > " . escapeshellarg($tempLogFile));

        $recipient = env('LOG_REPORT_EMAIL', 'taxi.easy.ua.sup@gmail.com');

        try {
            Mail::to($recipient)->send(new LogReportMail($tempLogFile));

            // Удаляем временный файл после отправки
            if (file_exists($tempLogFile)) {
                unlink($tempLogFile);
            }

            return response()->json(['message' => 'Последние 100 строк логов успешно отправлены.']);
        } catch (\Exception $e) {
            Log::error('Ошибка при отправке логов: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка: ' . $e->getMessage()], 500);
        }
    }

    // Метод для загрузки файла по ссылке
    public function downloadLog()
    {
        $filePath = '/usr/share/nginx/html/laravel_logs/laravel.log';

        if (!file_exists($filePath) || filesize($filePath) === 0) {
            return response()->json(['message' => 'Файл логов отсутствует или пуст.'], 404);
        }

        // Задаем заголовки для браузера (скачивание)
        return Response::download($filePath, 'laravel.log', [
            'Content-Type' => 'text/plain',
        ]);
    }

    // Или, если хочешь показывать содержимое прямо в браузере:
    public function viewLog()
    {
        $filePath = '/usr/share/nginx/html/laravel_logs/laravel.log';

        if (!file_exists($filePath) || filesize($filePath) === 0) {
            return response('Файл логов отсутствует или пуст.', 404);
        }

        return response()->file($filePath, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function clearLogs(): \Illuminate\Http\JsonResponse
    {
        $filePath = '/usr/share/nginx/html/laravel_logs/laravel.log';

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'Файл логов не найден.'], 404);
        }

        try {
            // Очищаем файл логов (оставляем пустой)
            file_put_contents($filePath, '');

            return response()->json(['message' => 'Логи успешно очищены.']);
        } catch (\Exception $e) {
            Log::error('Ошибка при очистке логов: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка: ' . $e->getMessage()], 500);
        }
    }

}

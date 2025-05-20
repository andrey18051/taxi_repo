<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\LogReportMail;

class LogController extends Controller
{
    public function sendLogs()
    {
        $filePath = '/usr/share/nginx/html/laravel_logs/laravel.log';

        if (file_exists($filePath) && filesize($filePath) > 0) {
            $recipient = env('LOG_REPORT_EMAIL', 'taxi.easy.ua.sup@gmail.com');

            Mail::to($recipient)->send(new LogReportMail($filePath));

            // Удаляем файл после отправки
            unlink($filePath);

            return response()->json(['message' => 'Логи успешно отправлены и удалены.']);
        } else {
            return response()->json(['message' => 'Файл логов отсутствует или пуст.'], 404);
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LogUploadController extends Controller
{
    public function upload(Request $request)
    {
        // Проверяем наличие файла
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $file = $request->file('file');

        // Проверяем валидность
        if (!$file->isValid()) {
            return response()->json(['error' => 'Upload error'], 400);
        }

        // Ограничиваем размер файла (например, 10 МБ)
//        $maxSize = 10 * 1024 * 1024; // 10 MB
//        if ($file->getSize() > $maxSize) {
//            return response()->json(['error' => 'File too large'], 400);
//        }

        // Ограничиваем тип файла (только текстовые логи)
        $allowedMimeTypes = ['text/plain', 'text/log'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return response()->json(['error' => 'Invalid file type'], 400);
        }

        // Генерируем уникальное имя файла
        $uniqueName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
            . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();

        // Сохраняем файл в storage/app/public/logs
        $path = $file->storeAs('public/logs', $uniqueName);

        // Удаляем старые логи (старше 30 дней)
        $this->cleanOldLogs(30);

        // Генерируем публичную ссылку
        $url = asset(str_replace('public/', 'storage/', $path));

        return response()->json(['url' => $url], 200);
    }

    /**
     * Удаляет файлы старше $days дней из папки storage/app/public/logs
     */
    private function cleanOldLogs($days = 30)
    {
        $files = Storage::files('public/logs');
        $now = Carbon::now();

        foreach ($files as $file) {
            $timestamp = Storage::lastModified($file);
            $fileDate = Carbon::createFromTimestamp($timestamp);

            if ($fileDate->diffInDays($now) > $days) {
                Storage::delete($file);
            }
        }
    }
}

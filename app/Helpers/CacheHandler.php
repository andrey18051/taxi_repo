<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheHandler
{
    public static function cacheEventPut(string $eventKey, bool $eventResult, int $expiration = 3600)
    {
        // Сохраняем результат события (true/false) в кеш
        try {
            // Сохранение данных в кеш
            Cache::put($eventKey, $eventResult, $expiration);
        } catch (\Exception $e) {
            // Логирование ошибки
            Log::error("Failed to store data in cache: " . $e->getMessage());

            // Опционально: резервный механизм, например, логирование в файл или возврат результата без кеширования
            // Например, можно просто вернуть $eventResult или записать данные в альтернативное хранилище
            // \File::put(storage_path('fallback_cache/' . $eventKey), serialize($eventResult));
        }
    }

    public static function cacheEventResult(string $eventKey): bool
    {
        try {
            return Cache::get($eventKey, false);
        } catch (\Exception $e) {
            // Логирование ошибки
            Log::error("Failed to retrieve data from cache for key {$eventKey}: " . $e->getMessage());

            // Возврат значения по умолчанию
            return false;
        }
    }
}

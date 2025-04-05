<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHandler
{
    public static function cacheEventPut(string $eventKey, bool $eventResult, int $expiration = 3600)
    {
        // Сохраняем результат события (true/false) в кеш
        Cache::put($eventKey, $eventResult, $expiration);
    }

    public static function cacheEventResult(string $eventKey): bool
    {
        return Cache::get($eventKey, false);
    }
}

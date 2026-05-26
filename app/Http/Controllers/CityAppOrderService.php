<?php

namespace App\Http\Controllers;

use App\Helpers\TimeHelper;
use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CityAppOrderService
{
    /**
     * Возвращает URL активного сервера для города и приложения или '400'
     */
    public function cityOnlineOrder(string $city, string $application): string
    {
        if (empty(trim($city)) || empty(trim($application))) {
            Log::error("✗ Некорректные входные данные: город = {$city}, приложение = {$application}");
            return '400';
        }

        Log::info("→ Старт cityOnlineOrder: город = {$city}, приложение = {$application}");

        $modelClass = $this->resolveModel($application);

        if (!class_exists($modelClass)) {
            Log::error("✗ Неизвестная модель для приложения: {$application}");
            return '400';
        }

        $server = $this->findOrUnlockServer($city, $modelClass);

        if ($server) {
            Log::info("✓ Сервер найден: {$server->address}");
            return 'http://' . $server->address;
        }

        Log::warning("✗ Доступный сервер не найден для города: {$city}");
        return '400';
    }

    /**
     * URL оператора или my_server_api при недоступности внешнего API.
     */
    public function resolveConnectApi(string $city, string $application): string
    {
        $connect = $this->cityOnlineOrder($city, $application);
        if ($connect !== '400') {
            return $connect;
        }

        if (TimeHelper::shouldForceFreshOperatorProbe($city)) {
            Log::info('🔴 Повторный поиск оператора (пограничное время комендантского часа)', [
                'city' => $city,
                'application' => $application,
            ]);
            $this->clearDomainCheckCacheForCity($city, $application);
            $connect = $this->cityOnlineOrder($city, $application);
            if ($connect !== '400') {
                return $connect;
            }
        }

        Log::info('Внешний сервер недоступен — переключение на my_server_api', [
            'city' => $city,
            'application' => $application,
        ]);

        return 'my_server_api';
    }

    public function clearDomainCheckCacheForCity(string $city, string $application): void
    {
        $modelClass = $this->resolveModel($application);
        if (!class_exists($modelClass)) {
            return;
        }

        foreach ($modelClass::where('name', $city)->pluck('address') as $address) {
            $address = trim((string) $address);
            if ($address !== '') {
                Cache::forget("domain_check_{$address}");
            }
        }
    }

    protected function findOrUnlockServer(string $city, string $modelClass): ?object
    {
        $forceFresh = TimeHelper::shouldForceFreshOperatorProbe($city);

        Log::info("🔓 findOrUnlockServer: {$city}");
        Log::info("🔓 findOrUnlockServer: {$modelClass}");
        if ($forceFresh) {
            Log::info("🔴 Пограничное время комендантского часа — принудительная проверка серверов для {$city}");
        }

        $lock = Cache::lock("server_check_{$city}", 10);
        if ($lock->get()) {
            try {
                Log::info("🔓 Блокировка получена для города: {$city}");

                $onlineServers = $modelClass::where('name', $city)
                    ->where('online', 'true')
                    ->orderBy('id', 'asc')
                    ->get();

                if ($onlineServers->isNotEmpty()) {
                    Log::info('📃 Проверка онлайн-серверов: ' . $onlineServers->count());

                    foreach ($onlineServers as $server) {
                        if ($this->checkDomain($server->address, $forceFresh)) {
                            Log::info("✅ Найден доступный онлайн-сервер: {$server->address}");
                            return $server;
                        }

                        $server->online = 'false';
                        $server->save();
                        Log::warning("❌ Онлайн-сервер недоступен (заблокирован): {$server->address}");
                    }
                }

                $offlineServers = $modelClass::where('name', $city)
                    ->where('online', 'false')
                    ->orderBy('id', 'asc')
                    ->get();

                if ($offlineServers->isNotEmpty()) {
                    Log::info('📃 Проверка оффлайн-серверов: ' . $offlineServers->count());

                    foreach ($offlineServers as $server) {
                        Log::debug("🕓 Проверка времени обновления сервера: {$server->address} (updated_at: {$server->updated_at})");

                        if ($forceFresh || $this->hasPassedOfflineRecheckInterval($server->updated_at)) {
                            if ($forceFresh) {
                                Log::info("🔴 Принудительная проверка оффлайн-сервера: {$server->address}");
                            } else {
                                Log::info("⏱ Прошло достаточно времени с обновления: {$server->address}, проверка доступности...");
                            }

                            if ($this->checkDomain($server->address, $forceFresh)) {
                                $server->online = 'true';
                                $server->save();
                                Log::info("🔓 Сервер разблокирован и установлен в online=true: {$server->address}");
                                return $server;
                            }

                            Log::warning("❌ Оффлайн-сервер недоступен: {$server->address}");
                        } else {
                            Log::info("⏳ Слишком рано для повторной проверки: {$server->address} - пропускаем");
                        }
                    }
                }

                Log::warning("🚫 Не найден ни один доступный сервер для города: {$city}");
                return null;
            } catch (\Throwable $e) {
                Log::error("🔥 Исключение при поиске сервера: {$e->getMessage()}");
                return null;
            } finally {
                $lock->release();
                Log::info("🔒 Блокировка снята для города: {$city}");
            }
        }

        Log::warning("🔐 Не удалось получить блокировку для города: {$city} (уже используется)");
        return null;
    }

    protected function hasPassedOfflineRecheckInterval($updatedAt): bool
    {
        $seconds = (int) config('services.city_app_order.offline_recheck_seconds', 300);

        try {
            $last = new DateTimeImmutable((string) $updatedAt);
            $now = new DateTimeImmutable();

            return ($now->getTimestamp() - $last->getTimestamp()) >= $seconds;
        } catch (\Exception $e) {
            Log::error("✗ Ошибка проверки времени: {$e->getMessage()}");
            return false;
        }
    }

    protected function checkDomain(string $domain, bool $forceFresh = false): bool
    {
        $startTime = microtime(true);
        $cacheKey = "domain_check_{$domain}";
        $failCacheTTL = (int) config('services.city_app_order.fail_cache_ttl', 30);
        $failCacheTTLBoundary = (int) config('services.city_app_order.fail_cache_ttl_boundary', 0);
        $successCacheTTL = (int) config('services.city_app_order.cache_ttl', 5);
        $connectTimeout = (int) config('services.city_app_order.curl_timeout', 3);
        $maxRetries = 2;

        if ($forceFresh) {
            Cache::forget($cacheKey);
            Log::info("🔴 HTTP-проверка без кэша: {$domain}");
        } elseif (Cache::has($cacheKey)) {
            $cached = (bool) Cache::get($cacheKey);
            Log::debug('📦 checkDomain cache hit: ' . $domain . ' => ' . ($cached ? 'ok' : 'fail'));

            return $cached;
        } else {
            Log::info("🚀 Начало проверки домена: {$domain}");
        }

        $url = "http://{$domain}/api/version";
        $ok = false;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            Log::debug("🔄 Попытка #$attempt из $maxRetries для {$url}");

            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                CURLOPT_TIMEOUT => $connectTimeout + 2,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FAILONERROR => true,
            ]);

            $attemptStartTime = microtime(true);
            curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_errno($curl);
            $errorMessage = curl_error($curl);
            $attemptElapsedTime = (microtime(true) - $attemptStartTime) * 1000;
            curl_close($curl);

            Log::debug("📶 Попытка #$attempt: HTTP {$httpCode}, curl {$error}: {$errorMessage}, {$attemptElapsedTime} мс");

            if ($error === 0 && $httpCode >= 200 && $httpCode < 300) {
                $ok = true;
                break;
            }

            if (in_array($error, [7, 28], true)) {
                Log::warning("⚡ Быстрый отказ checkDomain {$domain}: {$errorMessage}");
                break;
            }
        }

        if ($ok) {
            Cache::put($cacheKey, true, $successCacheTTL);
        } elseif ($forceFresh) {
            if ($failCacheTTLBoundary > 0) {
                Cache::put($cacheKey, false, $failCacheTTLBoundary);
            }
        } else {
            Cache::put($cacheKey, false, $failCacheTTL);
        }

        $elapsedTime = (microtime(true) - $startTime) * 1000;
        Log::info('⏱ Проверка домена ' . $domain . ' завершена за ' . round($elapsedTime) . ' мс, результат: ' . ($ok ? 'успех' : 'неудача'));

        return $ok;
    }

    protected function resolveModel(string $application): string
    {
        $models = [
            'PAS1' => \App\Models\City_PAS1::class,
            'PAS2' => \App\Models\City_PAS2::class,
            'PAS4' => \App\Models\City_PAS4::class,
            'PAS5' => \App\Models\City_PAS5::class,
        ];

        return $models[$application] ?? \App\Models\City_PAS5::class;
    }
}

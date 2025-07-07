<?php

namespace App\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CityAppOrderService
{
    /**
     * Возвращает URL активного сервера для города и приложения или '400'
     *
     * @param string $city Название города
     * @param string $application Код приложения
     * @return string URL сервера или '400' в случае ошибки
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
     * Поиск активного сервера или разморозка оффлайн-серверов
     *
     * @param string $city Название города
     * @param string $modelClass Класс модели
     * @return object|null Найденный сервер или null
     */
    protected function findOrUnlockServer(string $city, string $modelClass): ?object
    {
        Log::info("🔓 findOrUnlockServer: {$city}");
        Log::info("🔓 findOrUnlockServer: {$modelClass}");

        $lock = Cache::lock("server_check_{$city}", 10);
        if ($lock->get()) {
            try {
                Log::info("🔓 Блокировка получена для города: {$city}");



                // Поиск offline-серверов
                $servers = $modelClass::where('name', $city)
                    ->where('online', 'false')
                    ->get();

                Log::info("📃 Найдено offline-серверов: " . $servers->count());

                foreach ($servers as $server) {
                    Log::debug("🕓 Проверка времени обновления сервера: {$server->address} (updated_at: {$server->updated_at})");

                    if ($this->hasPassedFiveMinutes($server->updated_at)) {
                        Log::info("⏱ Прошло 5+ минут с обновления сервера: {$server->address}, проверка доступности...");

                        if ($this->checkDomain($server->address)) {
                            $server->online = 'true';
                            $server->save();
                            Log::info("🔓 Сервер разблокирован и установлен в online=true: {$server->address}");
                            return $server;
                        } else {
                            Log::warning("❌ Сервер недоступен (не разблокирован): {$server->address}");
                        }
                    } else {
                        Log::info("⏳ Менее 5 минут с обновления сервера: {$server->address}");
                    }
                }
                // Пытаемся найти активный сервер
                $server = $modelClass::where('name', $city)
                    ->where('online', 'true')
                    ->first();

                if ($server) {
                    Log::info("📡 Найден активный сервер: {$server->address}, проверка доступности...");

                    if ($this->checkDomain($server->address)) {
                        Log::info("✅ Сервер {$server->address} доступен (online=true)");
                        return $server;
                    } else {
                        Log::warning("❌ Сервер {$server->address} недоступен, продолжаем искать среди offline");
                    }
                } else {
                    Log::info("ℹ️ Нет активных серверов с online=true");
                }
                Log::info("🚫 Не найден доступный сервер ни в online, ни в offline списках.");
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


    /**
     * Проверка, прошло ли 5 минут с момента обновления
     *
     * @param mixed $updatedAt Время последнего обновления
     * @return bool true, если прошло >= 5 минут
     */
    protected function hasPassedFiveMinutes($updatedAt): bool
    {
        try {
            $last = new DateTimeImmutable((string) $updatedAt);
            $now = new DateTimeImmutable();
            return ($now->getTimestamp() - $last->getTimestamp()) >= 300;
        } catch (\Exception $e) {
            Log::error("✗ Ошибка проверки времени: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Проверка доступности домена с кэшированием
     *
     * @param string $domain Доменное имя для проверки
     * @return bool true, если домен доступен (HTTP 200-399 и нет ошибок cURL)
     */
    protected function checkDomain(string $domain): bool
    {
        $startTime = microtime(true);
        $cacheKey = "domain_check_{$domain}";
        $cacheTTL = config('services.city_app_order.cache_ttl', 300);

        $result = Cache::remember($cacheKey, $cacheTTL, function () use ($domain) {
            $url = "http://{$domain}/api/version";
            Log::debug("🔍 Проверка домена: {$url}");

            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_CONNECTTIMEOUT => config('services.city_app_order.curl_timeout', 60),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => false,
            ]);

            curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_errno($curl);
            $errorMessage = curl_error($curl);
            curl_close($curl);

            Log::debug("📶 HTTP код: {$httpCode}, ошибка: {$error}, сообщение: {$errorMessage}");
            return $error === 0 && $httpCode >= 200 && $httpCode < 400;
        });

        $elapsedTime = (microtime(true) - $startTime) * 1000;
        Log::info("⏱ Проверка домена {$domain} выполнена за {$elapsedTime} мс");
        return $result;
    }

    /**
     * Определение модели по коду приложения
     *
     * @param string $application Код приложения
     * @return string Класс модели
     */
    protected function resolveModel(string $application): string
    {
        $models = [
            'PAS1' => \App\Models\City_PAS1::class,
            'PAS2' => \App\Models\City_PAS2::class,
        ];

        return $models[$application] ?? \App\Models\City_PAS4::class;
    }
}

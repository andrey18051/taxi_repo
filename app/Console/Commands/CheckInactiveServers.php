<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckInactiveServers extends Command
{
    protected $signature = 'check-inactive:run';
    protected $description = 'Проверка неактивных серверов по всем городам и приложениям';

    // Модели приложений
    protected $applications = [
        'PAS1' => 'App\Models\City_PAS1',
        'PAS2' => 'App\Models\City_PAS2',
        'PAS4' => 'App\Models\City_PAS4',
    ];

    public function handle()
    {
        $this->info('🔄 Старт проверки неактивных серверов...');
        Log::info('🔄 Старт задачи проверки неактивных серверов');

        $totalChecked = 0;
        $totalReactivated = 0;
        $totalDeactivated = 0;

        foreach ($this->applications as $appName => $modelClass) {
            $this->info("🔍 Проверка приложения: {$appName}");

            if (!class_exists($modelClass)) {
                $this->error("✗ Модель не существует: {$modelClass}");
                Log::error("Модель не существует для приложения: {$appName} ({$modelClass})");
                continue;
            }

            // Получаем все уникальные города из базы данных
            $cities = $modelClass::distinct()->pluck('name');

            foreach ($cities as $city) {
                $result = $this->checkCityServers($city, $modelClass, $appName);
                $totalChecked += $result['checked'];
                $totalReactivated += $result['reactivated'];
                $totalDeactivated += $result['deactivated'];
            }
        }

        $this->info("✅ Проверка завершена!");
        $this->info("📊 Всего проверено серверов: {$totalChecked}");
        $this->info("🔄 Реактивировано серверов: {$totalReactivated}");
        $this->info("🚫 Деактивировано серверов: {$totalDeactivated}");

        Log::info("✅ Задача проверки неактивных серверов завершена", [
            'total_checked' => $totalChecked,
            'total_reactivated' => $totalReactivated,
            'total_deactivated' => $totalDeactivated
        ]);

        return Command::SUCCESS;
    }

    protected function checkCityServers(string $city, string $modelClass, string $appName): array
    {
        $lock = Cache::lock("inactive_check_{$appName}_{$city}", 30);

        if (!$lock->get()) {
            $this->warn("🔐 Не удалось получить блокировку для {$appName}/{$city} (уже проверяется)");
            return ['checked' => 0, 'reactivated' => 0, 'deactivated' => 0];
        }

        try {
            $this->info("🏙️  Проверка города: {$city} (приложение: {$appName})");

            $checked = 0;
            $reactivated = 0;
            $deactivated = 0;

            // 1. Проверяем оффлайн серверы - пытаемся реактивировать
            $offlineServers = $modelClass::where('name', $city)
                ->where('online', 'false')
                ->get();

            $this->info("📃 Найдено оффлайн серверов: " . $offlineServers->count());

            foreach ($offlineServers as $server) {
                $checked++;

                $this->info("🔄 Проверка оффлайн сервера: {$server->address}");

                if ($this->checkDomain($server->address)) {
                    // Сервер снова доступен - реактивируем
                    $server->online = 'true';
                    $server->save();
                    $reactivated++;

                    $this->info("✅ Сервер реактивирован: {$server->address}");
                    Log::info("Сервер реактивирован", [
                        'application' => $appName,
                        'city' => $city,
                        'address' => $server->address
                    ]);
                } else {
                    $this->warn("❌ Оффлайн сервер все еще недоступен: {$server->address}");
                }
            }

            // 2. Проверяем онлайн серверы на доступность
            $onlineServers = $modelClass::where('name', $city)
                ->where('online', 'true')
                ->get();

            $this->info("📃 Найдено онлайн серверов: " . $onlineServers->count());

            foreach ($onlineServers as $server) {
                $checked++;

                $this->info("🔍 Проверка онлайн сервера: {$server->address}");

                if (!$this->checkDomain($server->address)) {
                    // Онлайн сервер стал недоступен - деактивируем
                    $server->online = 'false';
                    $server->save();
                    $deactivated++;

                    $this->error("🚨 Онлайн сервер стал недоступен: {$server->address}");
                    Log::warning("Онлайн сервер стал недоступен", [
                        'application' => $appName,
                        'city' => $city,
                        'address' => $server->address
                    ]);
                } else {
                    $this->info("✅ Онлайн сервер доступен: {$server->address}");
                }
            }

            return [
                'checked' => $checked,
                'reactivated' => $reactivated,
                'deactivated' => $deactivated
            ];

        } catch (\Throwable $e) {
            $this->error("🔥 Ошибка при проверке {$appName}/{$city}: {$e->getMessage()}");
            Log::error("Ошибка проверки серверов", [
                'application' => $appName,
                'city' => $city,
                'error' => $e->getMessage()
            ]);

            return ['checked' => 0, 'reactivated' => 0, 'deactivated' => 0];
        } finally {
            $lock->release();
        }
    }

    protected function checkDomain(string $domain): bool
    {
        $startTime = microtime(true);
        $cacheKey = "domain_check_{$domain}";
        $cacheTTL = config('services.city_app_order.cache_ttl', 5);
        $maxRetries = 3;
        $retryDelay = 1;

        Log::info("🚀 Начало проверки домена: {$domain}, ключ кэша: {$cacheKey}, TTL: {$cacheTTL} сек");
        $result = Cache::remember($cacheKey, $cacheTTL, function () use ($domain, $maxRetries, $retryDelay) {
            $url = "http://{$domain}/api/version";
            Log::debug("🔍 Проверка домена: {$url}");

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                Log::debug("🔄 Попытка #$attempt из $maxRetries для {$url}");

                $curl = curl_init($url);
                curl_setopt_array($curl, [
                    CURLOPT_CONNECTTIMEOUT => config('services.city_app_order.curl_timeout', 5),
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FAILONERROR => true,
                ]);

                $attemptStartTime = microtime(true);
                $response = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $error = curl_errno($curl);
                $errorMessage = curl_error($curl);
                $attemptElapsedTime = (microtime(true) - $attemptStartTime) * 1000;
                curl_close($curl);

                Log::debug("📶 Результат попытки #$attempt: HTTP код: {$httpCode}, ошибка: {$error}, сообщение: {$errorMessage}, время: {$attemptElapsedTime} мс");
                if ($error === 0 && $httpCode >= 200 && $httpCode < 300) {
                    Log::debug("✅ Сервер ответил успешно (HTTP $httpCode). Ответ: " . substr($response, 0, 200) . "...");
                    Log::info("🎉 Успешная проверка домена {$url} на попытке #$attempt");
                    return true;
                }

                Log::warning("⚠️ Неуспешная попытка #$attempt: HTTP код {$httpCode}, ответ: " . substr($response, 0, 200) . "...");
                if ($attempt < $maxRetries) {
                    Log::debug("⏳ Задержка {$retryDelay} сек перед следующей попыткой");
                    sleep($retryDelay);
                }
            }

            Log::error("❌ Проверка домена {$url} завершилась неудачей после {$maxRetries} попыток");
            return false;
        });

        $elapsedTime = (microtime(true) - $startTime) * 1000;
        Log::info("⏱ Проверка домена {$domain} завершена за {$elapsedTime} мс, результат: " . ($result ? 'успех' : 'неудача'));
        if (!$result) {
            Log::warning("🗑 Очистка кэша для {$cacheKey} из-за неудачной проверки");
            Cache::forget($cacheKey);
        }

        return $result;
    }
}

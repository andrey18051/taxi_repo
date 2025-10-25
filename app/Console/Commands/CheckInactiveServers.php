<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\InactiveServersAlert;
use App\Http\Controllers\TelegramController;

class CheckInactiveServers extends Command
{
    protected $signature = 'check-inactive:run';
    protected $description = 'Проверка неактивных серверов по PAS1 и синхронизация PAS2/PAS4';

    protected $applications = [
        'PAS1' => 'App\Models\City_PAS1',
        'PAS2' => 'App\Models\City_PAS2',
        'PAS4' => 'App\Models\City_PAS4',
    ];

    public function handle()
    {
        $this->info('🔄 Старт проверки серверов (только PAS1)...');
        Log::info('🔄 Старт проверки серверов (только PAS1)');

        $baseApp = 'PAS1';
        $modelClass = $this->applications[$baseApp];

        if (!class_exists($modelClass)) {
            Log::error("Модель не существует: {$modelClass}");
            return Command::FAILURE;
        }

        $totalChecked = $totalReactivated = $totalDeactivated = 0;
        $offlineList = [];

        $cities = $modelClass::distinct()->pluck('name');
        Log::debug("📋 Города для {$baseApp}: ", $cities->toArray());

        foreach ($cities as $city) {
            $this->info("🏙 Проверка города: {$city}");
            $result = $this->checkCityServers($city, $modelClass, $baseApp);

            $totalChecked += $result['checked'];
            $totalReactivated += $result['reactivated'];
            $totalDeactivated += $result['deactivated'];

            $offlineList = array_merge($offlineList, $result['offline_list']);
        }

        // Убираем дубликаты
        $offlineList = array_values(array_unique(array_map('trim', $offlineList)));
        sort($offlineList);

        // Полная синхронизация всех приложений
        $this->syncOtherApplications($offlineList);

        Log::info("📊 Результаты проверки", [
            'checked' => $totalChecked,
            'reactivated' => $totalReactivated,
            'deactivated' => $totalDeactivated,
            'offline_count' => count($offlineList),
        ]);

        // Работа с кэшем Redis
        $cacheFinal = 'last_inactive_hash_final';
        $redis = Cache::getRedis();
        $hashFinal = Cache::get($cacheFinal);

        $ttlFinal = $redis->ttl(Cache::getPrefix() . $cacheFinal);
        $existsFinal = $redis->exists(Cache::getPrefix() . $cacheFinal);

        Log::debug("📦 Состояние Redis перед сравнением:", [
            'exists_final' => $existsFinal,
            'ttl_final' => $ttlFinal,
            'hash_final' => $hashFinal,
        ]);

        if (count($offlineList) > 0) {
            $offlineHash = md5(json_encode($offlineList));
            Log::debug("🔍 Текущий offlineHash: {$offlineHash}");

            // Проверяем: хэш изменился — значит новый состав offline
            if ($hashFinal !== $offlineHash) {
                // Новый оффлайн-набор — сохраняем и уведомляем
                Cache::put($cacheFinal, $offlineHash, now()->addMinutes(30));
                Log::info("💾 Новый оффлайн-хэш сохранён: {$offlineHash}");

                $message = "🚨 Обнаружено " . count($offlineList) . " неработающих серверов!\n\n"
                    . implode("\n", $offlineList);

                // Отправляем уведомления
                $this->notifyAdmins($message, $offlineList);

            } else {
                Log::debug("ℹ️ Оффлайн-хэш не изменился. Новых уведомлений не требуется.");
            }
        } else {
            // Все сервера активны — очищаем кэш
            Cache::forget($cacheFinal);
            Log::info("✅ Все сервера активны. Кэш очищен.");
        }


        return Command::SUCCESS;
    }


    protected function notifyAdmins(string $message, array $offlineList = [])
    {
        try {
            Notification::route('mail', 'taxi.easy.ua.sup@gmail.com')
                ->notify(new InactiveServersAlert($offlineList));
            Log::info("📧 Email notification sent successfully");
        } catch (\Throwable $e) {
            Log::error("❌ Email error: {$e->getMessage()}");
        }

        try {
            $telegram = new TelegramController();
            $telegram->sendMeMessage($message);
            $telegram->sendAlarmMessage($message);
            Log::info("📨 Telegram message sent successfully");
        } catch (\Throwable $e) {
            Log::error("❌ Telegram error: {$e->getMessage()}");
        }
    }


    protected function checkCityServers(string $city, string $modelClass, string $appName): array
    {
        $lockKey = "inactive_check_{$appName}_{$city}";
        $lock = Cache::lock($lockKey, 10); // 10 секунд на проверку города

        if (!$lock->get()) {
            Log::warning("Не удалось получить блокировку для {$appName}/{$city}. Пропускаем.");
            return [
                'checked' => 0,
                'reactivated' => 0,
                'deactivated' => 0,
                'offline_list' => [],
            ];
        }

        try {
            $checked = $reactivated = $deactivated = 0;
            $offlineList = [];

            // ГЛОБАЛЬНЫЙ КЭШ: статус всех серверов по address
            $globalCacheKey = 'server_status_global';
            $globalChecked = Cache::get($globalCacheKey, []);

            // Получаем все сервера города
            $servers = $modelClass::where('name', $city)->get();

            if ($servers->isEmpty()) {
                Log::debug("Нет серверов в городе: {$city}");
                return [
                    'checked' => 0,
                    'reactivated' => 0,
                    'deactivated' => 0,
                    'offline_list' => [],
                ];
            }

            foreach ($servers as $server) {
                $address = trim($server->address);

                if (empty($address)) {
                    Log::warning("Пустой address у сервера в городе {$city}, ID: {$server->id}");
                    continue;
                }

            // Используем ГЛОБАЛЬНЫЙ кэш
            if (isset($globalChecked[$address])) {
                $isOnline = $globalChecked[$address];
                Log::debug("КЭШ: {$address} → " . ($isOnline ? 'ONLINE' : 'OFFLINE') . " (город: {$city})");
            } else {
                // Первая проверка — делаем HTTP-запрос
                $isOnline = $this->checkDomain($address);
                $globalChecked[$address] = $isOnline;
                Log::info("ПРОВЕРКА: {$address} → " . ($isOnline ? 'ONLINE' : 'OFFLINE') . " (город: {$city})");
            }

            $checked++;

            // Обновляем статус в базе
            $currentStatus = $server->online;

            if ($isOnline && $currentStatus !== "true") {
                $server->online = "true";
                $server->save();
                $reactivated++;
                Log::info("ВКЛЮЧЁН: {$address} (город: {$city})");
            } elseif (!$isOnline && $currentStatus !== "false") {
                $server->online = "false";
                $server->save();
                $deactivated++;
                $offlineList[] = $address;
                Log::warning("ВЫКЛЮЧЕН: {$address} (город: {$city})");
            } elseif (!$isOnline && $currentStatus === "false") {
                $offlineList[] = $address;
            }
            // Если online и уже "true" — ничего не делаем
        }

            // Сохраняем обновлённый глобальный кэш (на 10 минут)
            Cache::put($globalCacheKey, $globalChecked, now()->addMinutes(10));

            Log::debug("Проверка города {$city} завершена", [
                'checked' => $checked,
                'reactivated' => $reactivated,
                'deactivated' => $deactivated,
                'offline' => $offlineList,
            ]);

            return [
                'checked' => $checked,
                'reactivated' => $reactivated,
                'deactivated' => $deactivated,
                'offline_list' => $offlineList,
            ];

        } catch (\Throwable $e) {
            Log::error("Ошибка при проверке города {$city}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'checked' => 0,
                'reactivated' => 0,
                'deactivated' => 0,
                'offline_list' => [],
            ];
        } finally {
            $lock->release();
        }
    }



    protected function checkDomain(string $domain): bool
    {
        $url = "http://{$domain}/api/version";
        $start = microtime(true);

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => false,
        ]);

        curl_exec($curl);
        $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err  = curl_errno($curl);
        $elapsed = round((microtime(true) - $start) * 1000, 2);
        curl_close($curl);

        if ($err === 0 && $http >= 200 && $http < 300) {
            Log::debug("✅ {$domain} OK HTTP {$http} за {$elapsed} мс");
            return true;
        }

        Log::warning("❌ {$domain} недоступен (HTTP {$http}), время {$elapsed} мс");
        return false;
    }

    protected function syncOtherApplications(array $offlineList)
    {
        // Получаем все адреса серверов из PAS1
        $pas1Model = $this->applications['PAS1'];
        if (!class_exists($pas1Model)) {
            Log::error("❌ Модель PAS1 не найдена, синхронизация невозможна");
            return;
        }

        $allServers = $pas1Model::pluck('address')->toArray();
        $onlineList = array_diff($allServers, $offlineList);

        Log::debug("🔁 Начало полной синхронизации", [
            'offline_count' => count($offlineList),
            'online_count' => count($onlineList)
        ]);

        foreach (['PAS2', 'PAS4'] as $app) {
            if (!isset($this->applications[$app])) continue;

            $model = $this->applications[$app];
            if (!class_exists($model)) {
                Log::warning("⚠️ Модель {$app} отсутствует, пропускаем");
                continue;
            }

            try {
                // 🔴 Обновляем оффлайн
                $offlineUpdated = 0;
                if (!empty($offlineList)) {
                    $offlineUpdated = $model::whereIn('address', $offlineList)
                        ->update(['online' => 'false']);
                }

                // 🟢 Обновляем онлайн
                $onlineUpdated = 0;
                if (!empty($onlineList)) {
                    $onlineUpdated = $model::whereIn('address', $onlineList)
                        ->update(['online' => 'true']);
                }

                // 🧹 (опционально) чистим лишние записи, если их нет в PAS1
                $deleted = $model::whereNotIn('address', $allServers)->delete();

                Log::info("🔄 {$app}: синхронизация завершена", [
                    'offline_updated' => $offlineUpdated,
                    'online_updated' => $onlineUpdated,
                    'deleted' => $deleted,
                ]);
            } catch (\Throwable $e) {
                Log::error("❌ Ошибка при синхронизации {$app}: {$e->getMessage()}");
            }
        }
    }


}

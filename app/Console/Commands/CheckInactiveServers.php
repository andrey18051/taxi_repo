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
            $this->error("✗ Модель не существует: {$modelClass}");
            Log::error("Модель не существует: {$modelClass}");
            return Command::FAILURE;
        }

        $totalChecked = $totalReactivated = $totalDeactivated = 0;
        $offlineList = [];

        $cities = $modelClass::distinct()->pluck('name');
        Log::debug("📋 Города для {$baseApp}: ", $cities->toArray());

        foreach ($cities as $city) {
            // Пропускаем тестовые города
            if (stripos($city, 'Test') !== false) {
                Log::debug("⏭ Пропуск тестового города: {$city}");
                continue;
            }

            $this->info("🏙 Проверка города: {$city}");
            $result = $this->checkCityServers($city, $modelClass, $baseApp);

            $totalChecked += $result['checked'];
            $totalReactivated += $result['reactivated'];
            $totalDeactivated += $result['deactivated'];

            // Собираем оффлайн IP без дублей
            foreach ($result['offline_list'] as $addr) {
                $offlineList[] = trim($addr);
            }
        }

        // Удаляем дубликаты и сортируем для красоты
        $offlineList = array_values(array_unique($offlineList));
        sort($offlineList);

        // Синхронизация PAS2 и PAS4
        $this->syncOtherApplications($offlineList);

        Log::info("📊 Результаты проверки", [
            'checked' => $totalChecked,
            'reactivated' => $totalReactivated,
            'deactivated' => $totalDeactivated,
            'offline_count' => count($offlineList),
        ]);

        // Уведомление, если есть оффлайн
        if (count($offlineList) > 0) {
            $cacheKey = 'last_inactive_servers';
            $cachedOffline = Cache::get($cacheKey, []);

            if ($cachedOffline !== $offlineList) {
                Cache::put($cacheKey, $offlineList, now()->addMinutes(30));

                $messageAdmin = "🚨 Обнаружено " . count($offlineList) .
                    " неработающих серверов!\n\n" . implode("\n", $offlineList);

                try {
                    Notification::route('mail', 'taxi.easy.ua.sup@gmail.com')
                        ->notify(new InactiveServersAlert($offlineList));
                    Log::info("📧 Email notification sent");
                } catch (\Exception $e) {
                    Log::error("❌ Email error: {$e->getMessage()}");
                }

                try {
                    (new TelegramController())->sendMeMessage($messageAdmin);
                    (new TelegramController())->sendAlarmMessage($messageAdmin);
                    Log::info("📨 Telegram message sent");
                } catch (\Exception $e) {
                    Log::error("❌ Telegram error: {$e->getMessage()}");
                }
            } else {
                Log::debug("ℹ️ Offline list unchanged, skip notifications");
            }
        } else {
            Log::info("✅ Все сервера активны");
        }

        return Command::SUCCESS;
    }

    protected function checkCityServers(string $city, string $modelClass, string $appName): array
    {
        $lock = Cache::lock("inactive_check_{$appName}_{$city}", 10);
        if (!$lock->get()) {
            Log::warning("🔐 Не удалось получить блокировку для {$appName}/{$city}");
            return ['checked' => 0, 'reactivated' => 0, 'deactivated' => 0, 'offline_list' => []];
        }

        try {
            $checked = $reactivated = $deactivated = 0;
            $offlineList = [];

            $offlineServers = $modelClass::where('name', $city)
                ->where('online', false)->get();

            foreach ($offlineServers as $server) {
                $checked++;
                if ($this->checkDomain($server->address)) {
                    $server->online = true;
                    $server->save();
                    $reactivated++;
                } else {
                    $offlineList[] = $server->address;
                }
            }

            $onlineServers = $modelClass::where('name', $city)
                ->where('online', true)->get();

            foreach ($onlineServers as $server) {
                $checked++;
                if (!$this->checkDomain($server->address)) {
                    $server->online = false;
                    $server->save();
                    $deactivated++;
                    $offlineList[] = $server->address;
                }
            }

            return [
                'checked' => $checked,
                'reactivated' => $reactivated,
                'deactivated' => $deactivated,
                'offline_list' => $offlineList
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
            CURLOPT_FAILONERROR => true,
        ]);

        $response = curl_exec($curl);
        $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_errno($curl);
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
        foreach (['PAS2', 'PAS4'] as $app) {
            if (!isset($this->applications[$app])) continue;
            $model = $this->applications[$app];
            if (!class_exists($model)) continue;

            foreach ($offlineList as $address) {
                $server = $model::where('address', $address)->first();
                if ($server) {
                    $server->online = false;
                    $server->save();
                    Log::warning("🔄 Синхронизирован оффлайн-сервер {$address} в {$app}");
                }
            }
        }
    }
}

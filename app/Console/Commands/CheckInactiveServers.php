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
            if (stripos($city, 'Test') !== false && $city !== 'OdessaTest') {
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

        // внутри handle()

        if (count($offlineList) > 0) {
            sort($offlineList);
            $offlineHash = md5(json_encode($offlineList));

            $cacheFinal = 'last_inactive_hash_final';
            $cacheTemp  = 'last_inactive_hash_temp';

            $hashFinal = Cache::get($cacheFinal);
            $hashTemp  = Cache::get($cacheTemp);

            // если текущий хэш совпадает с временным 2 раза подряд — подтверждаем оффлайн
            if ($hashTemp === $offlineHash && $hashFinal !== $offlineHash) {
                Cache::put($cacheFinal, $offlineHash, now()->addMinutes(30));
                Log::debug("💾 Подтверждён оффлайн и кэш обновлён: {$cacheFinal} = {$offlineHash}");

                Cache::forget($cacheTemp);

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

            } elseif ($hashTemp !== $offlineHash) {
                // сохраняем первый раз как временный
                Cache::put($cacheTemp, $offlineHash, now()->addMinutes(10));
                Log::debug("🧠 Кэш сохранён: {$cacheTemp} = {$offlineHash}");
                Log::info("⏳ Первый оффлайн-результат сохранён, ждём подтверждения на следующей проверке.");
            } else {
                Log::debug("ℹ️ Оффлайн список без изменений — уведомления не отправлены.");
            }
        } else {
            Cache::forget('last_inactive_hash_temp');
            Cache::forget('last_inactive_hash_final');
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
        if (empty($offlineList)) {
            Log::debug("🟢 Нет оффлайн-серверов для синхронизации");
            return;
        }

        foreach (['PAS2', 'PAS4'] as $app) {
            if (!isset($this->applications[$app])) continue;
            $model = $this->applications[$app];
            if (!class_exists($model)) continue;

            try {
                // Массовое обновление — быстрее, чем проход по каждому адресу
                $updatedCount = $model::whereIn('address', $offlineList)
                    ->update(['online' => false]);

                if ($updatedCount > 0) {
                    Log::warning("🔄 {$app}: синхронизировано оффлайн-серверов — {$updatedCount}");
                } else {
                    Log::debug("ℹ️ {$app}: оффлайн-сервера для синхронизации не найдены");
                }
            } catch (\Throwable $e) {
                Log::error("❌ Ошибка при синхронизации {$app}: {$e->getMessage()}");
            }
        }
    }

}

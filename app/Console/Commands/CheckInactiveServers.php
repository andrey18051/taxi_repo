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
    protected $signature = 'check-inactive:run {--force : Принудительная проверка всех серверов}';
    protected $description = 'Проверка неактивных серверов по PAS1 и синхронизация PAS2/PAS4';

    protected $applications = [
        'PAS1' => 'App\Models\City_PAS1',
        'PAS2' => 'App\Models\City_PAS2',
        'PAS4' => 'App\Models\City_PAS4',
    ];

    public function handle()
    {
        // ГЛОБАЛЬНЫЙ LOCK — ОДИН ЗАПУСК В ОДИН МОМЕНТ
        $globalLock = Cache::lock('check_inactive_global', 60); // 60 сек
        if (!$globalLock->get()) {
            Log::info('Команда уже выполняется. Пропускаем этот запуск.');
            return Command::SUCCESS;
        }

        try {
            $this->info('Старт проверки серверов (только PAS1)...');
            Log::info('Старт проверки серверов (только PAS1)');

            // Принудительная очистка кэша
            if ($this->option('force')) {
                Cache::forget('server_status_global');
                Log::info('Принудительная проверка: кэш сброшен');
            }

            $baseApp = 'PAS1';
            $modelClass = $this->applications[$baseApp];

            if (!class_exists($modelClass)) {
                Log::error("Модель не существует: {$modelClass}");
                return Command::FAILURE;
            }

            $totalChecked = $totalReactivated = $totalDeactivated = 0;
            $offlineList = [];
            $globalChecked = Cache::get('server_status_global', []); // Один кэш на всю команду

            $cities = $modelClass::distinct()->pluck('name');
            Log::debug("Города для {$baseApp}: ", $cities->toArray());

            foreach ($cities as $city) {
                $result = $this->checkCityServers($city, $modelClass, $baseApp, $globalChecked);
                $totalChecked += $result['checked'];
                $totalReactivated += $result['reactivated'];
                $totalDeactivated += $result['deactivated'];
                $offlineList = array_merge($offlineList, $result['offline_list']);
                $globalChecked = $result['global_checked']; // Обновляем кэш
            }

            // Убираем дубликаты
            $offlineList = array_values(array_unique(array_map('trim', $offlineList)));
            sort($offlineList);

            // Обновляем кэш ОДИН РАЗ в конце
            Cache::put('server_status_global', $globalChecked, now()->addMinutes(3));

            $this->syncOtherApplications($offlineList);

            Log::info("Результаты проверки", [
                'checked' => $totalChecked,
                'reactivated' => $totalReactivated,
                'deactivated' => $totalDeactivated,
                'offline_count' => count($offlineList),
            ]);

            $this->handleNotifications($offlineList);

            return Command::SUCCESS;
        } finally {
            $globalLock->release();
        }
    }

    protected function handleNotifications(array $offlineList)
    {
        $cacheFinal = 'last_inactive_hash_final';
        $hashFinal = Cache::get($cacheFinal);

        if (count($offlineList) > 0) {
            $offlineHash = md5(json_encode($offlineList));
            Log::debug("Текущий offlineHash: {$offlineHash}");

            if ($hashFinal !== $offlineHash) {
                Cache::put($cacheFinal, $offlineHash, now()->addMinutes(30));
                Log::info("Новый оффлайн-хэш сохранён: {$offlineHash}");

                $message = $this->buildGroupedMessage($offlineList);
                $this->notifyAdmins($message, $offlineList);
            } else {
                Log::debug("Оффлайн-хэш не изменился. Новых уведомлений не требуется.");
            }
        } else {
            Cache::forget($cacheFinal);
            Log::info("Все сервера активны. Кэш очищен.");
        }
    }

    protected function buildGroupedMessage(array $offlineList): string
    {
        $grouped = [];
        foreach ($offlineList as $addr) {
            [$ip, $port] = explode(':', $addr, 2);
            $grouped[$ip][] = $port;
        }

        $message = "Обнаружено " . count($offlineList) . " неработающих сервисов!\n\n";
        foreach ($grouped as $ip => $ports) {
            $message .= "{$ip} — порты: " . implode(', ', $ports) . "\n";
        }
        return $message;
    }

    protected function notifyAdmins(string $message, array $offlineList = [])
    {
        try {
            Notification::route('mail', 'taxi.easy.ua.sup@gmail.com')
                ->notify(new InactiveServersAlert($offlineList));
            Log::info("Email notification sent successfully");
        } catch (\Throwable $e) {
            Log::error("Email error: {$e->getMessage()}");
        }

        try {
            $telegram = new TelegramController();
            $telegram->sendMeMessage($message);
            $telegram->sendAlarmMessage($message);
            Log::info("Telegram message sent successfully");
        } catch (\Throwable $e) {
            Log::error("Telegram error: {$e->getMessage()}");
        }
    }

    protected function checkCityServers(string $city, string $modelClass, string $appName, array &$globalChecked): array
    {
        $lockKey = "inactive_check_{$appName}_{$city}";
        $lock = Cache::lock($lockKey, 10);

        if (!$lock->get()) {
            Log::warning("Не удалось получить блокировку для {$appName}/{$city}. Пропускаем.");
            return [
                'checked' => 0,
                'reactivated' => 0,
                'deactivated' => 0,
                'offline_list' => [],
                'global_checked' => $globalChecked,
            ];
        }

        try {
            $checked = $reactivated = $deactivated = 0;
            $offlineList = [];

            $servers = $modelClass::where('name', $city)->get();
            if ($servers->isEmpty()) {
                return [
                    'checked' => 0,
                    'reactivated' => 0,
                    'deactivated' => 0,
                    'offline_list' => [],
                    'global_checked' => $globalChecked,
                ];
            }

            foreach ($servers as $server) {
                $address = trim($server->address);
                if (empty($address)) continue;

                $isOnline = $globalChecked[$address] ?? null;

                if ($isOnline === null) {
                    $isOnline = $this->checkDomain($address);
                    $globalChecked[$address] = $isOnline;
                    Log::info("ПРОВЕРКА: {$address} → " . ($isOnline ? 'ONLINE' : 'OFFLINE') . " (город: {$city})");
                } else {
                    Log::debug("КЭШ: {$address} → " . ($isOnline ? 'ONLINE' : 'OFFLINE') . " (город: {$city})");
                }

                $checked++;

                $currentStatus = in_array($server->online, ['true', true, '1', 1]) ? 'true' : 'false';

                if ($isOnline && $currentStatus !== 'true') {
                    $server->online = 'true';
                    $server->save();
                    $reactivated++;
                    Log::info("ВКЛЮЧЁН: {$address} (город: {$city})");
                } elseif (!$isOnline && $currentStatus !== 'false') {
                    $server->online = 'false';
                    $server->save();
                    $deactivated++;
                    $offlineList[] = $address;
                    Log::warning("ВЫКЛЮЧЕН: {$address} (город: {$city})");
                } elseif (!$isOnline) {
                    $offlineList[] = $address;
                }
            }

            return [
                'checked' => $checked,
                'reactivated' => $reactivated,
                'deactivated' => $deactivated,
                'offline_list' => $offlineList,
                'global_checked' => $globalChecked,
            ];
        } catch (\Throwable $e) {
            Log::error("Ошибка при проверке города {$city}: " . $e->getMessage());
            return [
                'checked' => 0,
                'reactivated' => 0,
                'deactivated' => 0,
                'offline_list' => [],
                'global_checked' => $globalChecked,
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
            CURLOPT_CONNECTTIMEOUT => 10,  // увеличено
            CURLOPT_TIMEOUT => 15,         // увеличено
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_NOBODY => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        curl_exec($curl);
        $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_errno($curl);
        $errorMsg = curl_error($curl);
        $elapsed = round((microtime(true) - $start) * 1000, 2);
        curl_close($curl);

        if ($err === 0 && $http >= 200 && $http < 300) {
            Log::debug("{$domain} OK HTTP {$http} за {$elapsed} мс");
            return true;
        }

        if ($err !== 0) {
            Log::warning("{$domain} cURL ошибка {$err}: {$errorMsg} (время: {$elapsed} мс)");
        } else {
            Log::warning("{$domain} HTTP {$http} (время: {$elapsed} мс)");
        }

        return false;
    }

    protected function syncOtherApplications(array $offlineList)
    {
        $pas1Model = $this->applications['PAS1'];
        if (!class_exists($pas1Model)) {
            Log::error("Модель PAS1 не найдена");
            return;
        }

        $allServers = $pas1Model::pluck('address')->toArray();
        $onlineList = array_diff($allServers, $offlineList);

        Log::debug("Синхронизация", [
            'offline_count' => count($offlineList),
            'online_count' => count($onlineList),
        ]);

        foreach (['PAS2', 'PAS4'] as $app) {
            $model = $this->applications[$app] ?? null;
            if (!$model || !class_exists($model)) continue;

            try {
                $offlineUpdated = !empty($offlineList)
                    ? $model::whereIn('address', $offlineList)->update(['online' => 'false'])
                    : 0;

                $onlineUpdated = !empty($onlineList)
                    ? $model::whereIn('address', $onlineList)->update(['online' => 'true'])
                    : 0;

                $deleted = $model::whereNotIn('address', $allServers)->delete();

                Log::info("{$app}: синхронизация", [
                    'offline_updated' => $offlineUpdated,
                    'online_updated' => $onlineUpdated,
                    'deleted' => $deleted,
                ]);
            } catch (\Throwable $e) {
                Log::error("Ошибка синхронизации {$app}: {$e->getMessage()}");
            }
        }
    }
}

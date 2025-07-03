<?php

namespace App\Http\Controllers;

use DateTimeImmutable;
use Illuminate\Support\Facades\Log;

class CityAppOrderService
{
    /**
     * Основной метод: возвращает URL сервера или '400'
     */
    public function cityOnlineOrder(string $city, string $application): string
    {
        Log::info("→ Старт cityOnlineOrder: город = {$city}, приложение = {$application}");

        $modelClass = $this->resolveModel($application);

        if (!class_exists($modelClass)) {
            Log::error("✗ Неизвестная модель для приложения: {$application}");
            return '400';
        }

        $this->unlockFrozenServers($city, $modelClass);

        $server = $modelClass::where('name', $city)
            ->where('online', 'true')
            ->first();

        if ($server && $this->checkDomain($server->address)) {
            Log::info("✓ Сервер найден: {$server->address}");
            return 'http://' . $server->address;
        }

        Log::warning("✗ Доступный сервер не найден для города: {$city}");
        return '400';
    }

    /**
     * Разморозка устаревших оффлайн-серверов
     */
    protected function unlockFrozenServers(string $city, string $modelClass): void
    {
        $servers = $modelClass::where('name', $city)
            ->where('online', 'false')
            ->get();

        foreach ($servers as $server) {
            if ($this->hasPassedFiveMinutes($server->updated_at) &&
                $this->checkDomain($server->address)) {
                $server->online = 'true';
                $server->save();
                Log::info("↻ Сервер разблокирован: {$server->address}");
            }
        }
    }

    /**
     * Проверка, прошло ли 5 минут с момента обновления
     * @throws \Exception
     */
    protected function hasPassedFiveMinutes($updatedAt): bool
    {
        $last = new DateTimeImmutable((string) $updatedAt);
        $now = new DateTimeImmutable();
        return ($now->getTimestamp() - $last->getTimestamp()) >= 300;
    }

    /**
     * Проверка доступности домена
     */
    public function checkDomain(string $domain): bool
    {
        $url = "http://{$domain}/api/version";
        Log::debug("🔍 Проверка домена: {$url}");

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
        ]);

        curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_errno($curl);
        curl_close($curl);

        Log::debug("📶 HTTP код: {$httpCode}, ошибка: {$error}");

        return $error === 0 && $httpCode >= 200 && $httpCode < 400;
    }

    /**
     * Определение соответствующей модели по коду приложения
     */
    protected function resolveModel(string $application): ?string
    {
        switch ($application) {
            case 'PAS1':
                return \App\Models\City_PAS1::class;
            case 'PAS2':
                return \App\Models\City_PAS2::class;
            default:
                return \App\Models\City_PAS4::class;
        }
    }

}

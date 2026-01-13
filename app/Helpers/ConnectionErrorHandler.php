<?php


namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\UniversalAndroidFunctionController;
use App\Http\Controllers\TelegramController;
use App\Mail\Check;

class ConnectionErrorHandler
{
    /**
     * Обработка ошибки подключения к серверу с дедупликацией сообщений.
     *
     * @param object $city Объект города
     * @param array $value Массив с данными сервера (включая адрес)
     * @param string $client_ip IP-адрес клиента
     * @param bool $checking Флаг проверки
     * @param string $online Статус подключения ("true" или "false")
     * @param bool $timeFive Флаг времени (5 минут)
     * @return void
     */
    public function handleConnectionError($city, array $value, string $client_ip, bool $checking, string $online, bool $timeFive): void
    {
        // Проверка условий для обработки ошибки
        // Отправляем уведомление только когда:
        // 1. Сервер был онлайн ($online === "true")
        // 2. Не в режиме проверки (!$checking)
        // 3. Прошло более 5 минут с последней ошибки (!$timeFive)
        if (!($online === "true" && !$checking && !$timeFive)) {
            Log::debug("Условия для обработки ошибки подключения не выполнены: online={$online}, checking={$checking}, timeFive={$timeFive}");
            return;
        }

        // Проверяем наличие адреса в исходном массиве
        if (empty($value['address'])) {
            Log::debug("Адрес не найден в массиве value");
            return;
        }

        $originalAddress = trim($value['address']);
        Log::debug("Обрабатываем адрес: '{$originalAddress}'");

        // Список заблокированных IP (без портов)
        $blockedIPs = [
            '167.235.113.231:7307',
            // '134.249.181.173',
            // '91.205.17.153',
        ];

        // Список заблокированных полных адресов (с портами) - для точного совпадения
        $blockedAddresses = [
//            '188.40.143.61:7222',
            // '134.249.181.173:7208',
            // '91.205.17.153:7201',
        ];

        // Извлекаем IP из адреса (убираем порт если есть)
        $addressParts = explode(':', $originalAddress);
        $ipOnly = $addressParts[0];

        // Проверяем блокировку
        $isBlocked = false;

        // Проверка 1: Полный адрес с портом (точное совпадение)
        if (in_array($originalAddress, $blockedAddresses, true)) {
            Log::debug("Заблокированный полный адрес найден: {$originalAddress}");
            $isBlocked = true;
        }
        // Проверка 2: Только IP (все порты этого IP)
        elseif (in_array($ipOnly, $blockedIPs, true)) {
            Log::debug("Заблокированный IP найден: {$ipOnly} (полный адрес: {$originalAddress})");
            $isBlocked = true;
        }

        if ($isBlocked) {
            Log::debug("Уведомление заблокировано для адреса: {$originalAddress}");
            return;
        }

        // Установка статуса города как оффлайн
        $city->online = "false";
        $city->save();
        Log::debug("Статус города {$city->name} изменен на offline");

        // Нормализация имени города
        $cityName = $city->name ?? 'Unknown';
        $messageAdmin = "Нет подключения к серверу города {$cityName} http://{$originalAddress}. IP {$client_ip}";

        // Логирование сообщения
        Log::debug($messageAdmin);

        // Проверка времени для отправки уведомлений
        $isCurrentTimeInRange = (new UniversalAndroidFunctionController)->isCurrentTimeInRange();
        if (!$isCurrentTimeInRange) {
            Log::debug("Отправка уведомления разрешена: текущее время в допустимом диапазоне");
        } else {
            Log::debug("Отправка уведомления заблокирована: вне разрешенного временного диапазона");
            return;
        }

        // Формирование ключа кэша
        $cacheKey = 'alarm_message_' . md5($messageAdmin);

        // Проверка, не было ли сообщение уже отправлено
        if (!Cache::has($cacheKey)) {
            // Получение блокировки на 10 минут
            $lock = Cache::lock($cacheKey, 600);

            Log::debug("Попытка получить блокировку для ключа: {$cacheKey}");
            if ($lock->get()) {
                try {
                    // Двойная проверка кэша после получения блокировки
                    if (!Cache::has($cacheKey)) {
                        // Отправка сообщений
                        $alarmMessage = new TelegramController();
                        $alarmMessage->sendAlarmMessage($messageAdmin);
                        $alarmMessage->sendMeMessage($messageAdmin);

                        // Сохранение в кэш, чтобы избежать повторной отправки
                        Cache::put($cacheKey, true, 600);
                        Log::debug("Сообщение отправлено и закешировано: {$cacheKey}");
                    } else {
                        Log::debug("Сообщение уже было отправлено: {$cacheKey}");
                    }
                } catch (\Exception $e) {
                    // Отправка письма об ошибке
                    $paramsCheck = [
                        'subject' => 'Ошибка в телеграмм',
                        'message' => $e->getMessage(),
                    ];
                    Mail::to('taxi.easy.ua.sup@gmail.com')->send(new Check($paramsCheck));
                    Log::error("Ошибка отправки сообщения: {$e->getMessage()}");
                } finally {
                    // Освобождение блокировки
                    $lock->release();
                    Log::debug("Блокировка освобождена: {$cacheKey}");
                }
            } else {
                Log::debug("Не удалось получить блокировку: {$cacheKey}");
            }
        } else {
            Log::debug("Сообщение уже в кэше, пропускаем: {$cacheKey}");
        }
    }
}

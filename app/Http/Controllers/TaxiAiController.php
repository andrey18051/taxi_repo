<?php

namespace App\Http\Controllers;

use App\Helpers\OpenStreetMapHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaxiAiController extends Controller
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = "http://172.17.0.1:8001";
    }

    /**
     * Определяет язык текста на основе символов
     */
    public function detectLanguage(string $text): string
    {
        // Проверяем наличие украинских символов
        if (preg_match('/[їіґє]/u', $text)) {
            return 'uk';
        }
        // Проверяем наличие русских символов
        if (preg_match('/[ёйыэ]/u', $text)) {
            return 'ru';
        }
        // Проверяем, является ли текст ASCII (английский)
        if (mb_check_encoding($text, 'ASCII')) {
            return 'en';
        }
        // По умолчанию украинский
        return 'uk';
    }

    /**
     * Очищает адрес от лишних деталей такси (кондиционер, багаж и т.д.)
     */
    protected function cleanTaxiDetails(string $address): string
    {
        $patternsToRemove = [
            // Кондиционер
            '/\s*(з|с|with)\s+(кондиціонером|кондиционером|air conditioning).*/ui',
            // Водитель
            '/\s*(без|without)\s+(водія|водителя|driver).*/ui',
            // Время
            '/\s*(завтра|сьогодні|tomorrow|today|післязавтра|послезавтра).*/ui',
            '/\s*\d{1,2}:\d{2}.*/ui',
            // Багаж
            '/\s*(з|с|with)\s+(валізою|багажем|luggage).*/ui',
            // Животные
            '/\s*(з|с|with)\s+(твариною|собакою|animal|pet).*/ui',
            // Дети
            '/\s*(з|с|with)\s+(дитиною|child).*/ui',
            // Срочность
            '/\s*терміново.*/ui',
            '/\s*срочно.*/ui',
            '/\s*urgently.*/ui',
            // Курение
            '/\s*я буду курити.*/ui',
            '/\s*я буду курить.*/ui',
            '/\s*i will smoke.*/ui',
            // Чек
            '/\s*чек.*/ui',
            '/\s*check.*/ui'
        ];

        $cleaned = $address;
        foreach ($patternsToRemove as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned);
        }

        return trim($cleaned, " ,.");
    }

    /**
     * Обрабатывает запрос к Taxi AI
     */
    public function parse(Request $request)
    {
        $text = $request->input('text');
        if (empty($text)) {
            return response()->json(['error' => 'Text is required'], 400);
        }

        try {
            // Определяем язык: из запроса или автоматически
            $lang = $request->input('lang') ?: $this->detectLanguage($text);
            $defaultCity = $lang === 'uk' ? 'Київ' : ($lang === 'ru' ? 'Киев' : 'Kyiv');

            Log::info('[TaxiAi] Detected language', ['text' => $text, 'lang' => $lang]);

            // Запрос к AI модели
            $response = Http::timeout(30)->post("{$this->baseUrl}/parse", [
                'text' => $text,
                'lang' => $lang
            ]);

            if ($response->failed()) {
                Log::error('[TaxiAi] Failed to get response from Taxi AI service', [
                    'text' => $text,
                    'lang' => $lang,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json([
                    'error' => 'Failed to connect to Taxi AI service',
                    'details' => $response->body(),
                ], 500);
            }

            $aiResponse = $response->json();
            Log::info('[TaxiAi] Raw AI response', ['aiResponse' => $aiResponse]);

            // Проверяем структуру ответа - НОВАЯ СТРУКТУРА!
            if (!isset($aiResponse['response']) || !is_array($aiResponse['response'])) {
                Log::error('[TaxiAi] Invalid response structure', ['aiResponse' => $aiResponse]);
                return response()->json(['error' => 'Invalid AI response structure'], 500);
            }

            $responseData = $aiResponse['response'];
            $geoHelper = new OpenStreetMapHelper();

            // Обрабатываем адреса с новой логикой очистки
            foreach (['origin', 'destination'] as $key) {
                $address = $responseData[$key] ?? null;
                if (!empty($address)) {
                    // Python модель УЖЕ очистила адреса, поэтому просто используем как есть
                    $cleanedAddress = $address; // Убираем cleanTaxiDetails!

                    // Добавляем город если нужно
                    $hasCity = preg_match('/(Київ|Киев|Kyiv)/ui', $cleanedAddress);
                    $fullAddress = $hasCity ? $cleanedAddress : $defaultCity . ', ' . $cleanedAddress;

                    Log::info("[TaxiAi] Forming full address for {$key}", [
                        'original' => $address,
                        'full_address' => $fullAddress,
                    ]);

                    $coords = $geoHelper->getCoordinatesByPlaceName($fullAddress, $lang);

                    if ($coords) {
                        $responseData[$key . '_coordinates'] = $coords;
                    } else {
                        Log::warning("[TaxiAi] {$key} coordinates not found", ['address' => $fullAddress]);
                    }

                    $responseData[$key . '_cleaned'] = $fullAddress;
                }
            }

            // Проверка совпадения координат
            if (isset($responseData['origin_coordinates'], $responseData['destination_coordinates']) &&
                $responseData['origin_coordinates'] === $responseData['destination_coordinates']) {
                Log::warning("[TaxiAi] Origin and destination coordinates are identical", [
                    'origin' => $responseData['origin'],
                    'destination' => $responseData['destination'],
                    'coords' => $responseData['origin_coordinates'],
                ]);
            }

            // НОВАЯ ЛОГИКА: Детали теперь приходят как массив, а не строка с *
            $details = $responseData['details'] ?? [];
            if (!is_array($details)) {
                // На случай если все еще приходит строка (бэкап)
                $details = !empty($details) ? explode('*', $details) : [];
            }

            // Объединяем с временными деталями если нужно
            $timeDetails = $responseData['time_details'] ?? [];
            if (!empty($timeDetails) && is_array($timeDetails)) {
                $details = array_merge($details, $timeDetails);
            }

            if (isset(
                $responseData['origin_coordinates']['latitude'],
                $responseData['origin_coordinates']['longitude'],
                $responseData['destination_coordinates']['latitude'],
                $responseData['destination_coordinates']['longitude']
            )) {
                try {
                    $costParams = $this->prepareCostParameters($responseData);
// Логирование перед вызовом costValueExecute
                    Log::info('=== Параметры для costValueExecute ===', $costParams);

// Вызов метода с распакованными параметрами
                    $costResultArr = $this->costValueExecute(
                        $costParams['originLatitude'],
                        $costParams['originLongitude'],
                        $costParams['toLatitude'],
                        $costParams['toLongitude'],
                        $costParams['tariff'],
                        $costParams['phone'],
                        $costParams['user'],
                        $costParams['time'],
                        $costParams['date'],
                        $costParams['services'],
                        $costParams['city'],
                        $costParams['application']
                    );

                    // Логирование результата costValueExecute
                    Log::info('=== Результат costValueExecute ===', [
                        'costResultArr' => $costResultArr,
                        'type' => gettype($costResultArr),
                        'is_empty' => empty($costResultArr),
                        'is_array' => is_array($costResultArr)
                    ]);

// ИСПРАВЛЕНИЕ: Обрабатываем Response объект
                    if ($costResultArr instanceof \Illuminate\Http\Response) {
                        Log::info('Извлекаем данные из Response объекта');

                        $content = $costResultArr->getContent();
                        $responseArr = json_decode($content, true);

                        Log::info('Данные из Response', [
                            'content' => $content,
                            'decoded_data' => $responseArr
                        ]);

                        if (is_array($responseArr) && !empty($responseArr)) {
                            // Создаем массив с нужными данными для объединения
                            $costData = [
                                'order_cost' => $responseArr['order_cost'] ?? 0,
                                'dispatching_order_uid' => $responseArr['dispatching_order_uid'] ?? ''
                            ];

                            Log::info('costData', [
                                'costData' => $costData
                            ]);
                            $responseData['costData'] = $costData;

                        } else {
                            Log::warning('Не удалось извлечь данные из Response');
                        }
                    } elseif (!empty($costResultArr) && is_array($costResultArr)) {
                        Log::info('Объединяем details с costResultArr', [
                            'costResultArr' => $costResultArr
                        ]);
                        $costData = [
                            'order_cost' => $costResultArr['order_cost'] ?? 0,
                            'dispatching_order_uid' => $costResultArr['dispatching_order_uid'] ?? '',
                        ];
                        $responseData['costData'] = $costData;

                    } else {
                        Log::warning('costResultArr пустой или не массив, объединение не выполнено');
                    }

                } catch (\Exception $e) {
                    Log::error('Ошибка при расчете стоимости такси', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // Продолжаем выполнение, не прерывая весь метод
                    Log::info('Продолжаем выполнение после ошибки расчета стоимости');
                }
            }
// Логирование перед array_unique
            Log::info('=== Перед array_unique ===', [
                'current_details' => $details
            ]);

            $responseData['details'] = array_values(array_unique($details));

            // Обновляем основной ответ
            $aiResponse['response'] = $responseData;

            Log::info('[TaxiAi] Final AI response prepared', ['aiResponse' => $aiResponse]);

            return response()->json($aiResponse);

        } catch (\Exception $e) {
            Log::error('[TaxiAi] Cannot connect to Taxi AI service', [
                'text' => $text,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Cannot connect to Taxi AI service',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Подготавливает параметры для costValueExecute из данных ответа
     */
    private function prepareCostParameters(array $responseData): array
    {

        // Обязательные параметры из responseData
        $originLatitude = $responseData['origin_coordinates']['latitude'] ?? null;
        $originLongitude = $responseData['origin_coordinates']['longitude'] ?? null;
        $toLatitude = $responseData['destination_coordinates']['latitude'] ?? null;
        $toLongitude = $responseData['destination_coordinates']['longitude'] ?? null;

        // Проверяем обязательные поля
        if ($originLatitude === null || $originLongitude === null ||
            $toLatitude === null || $toLongitude === null) {
            Log::error("Missing required coordinates in response data");
            return [];
        }

        $tariff = $responseData['tariff'] ?? " ";
        $phone = $responseData['phone'] ?? ' ';
        $user = $responseData["user"] ?? "username (2.1756) *andrey18051@gmail.com*nal_payment";
        $time = $responseData["time"] ?? "no_time";
        $date = $responseData["date"] ?? "no_date";

        $city = $responseData["city"] ?? 'OdessaTest';
        $application = $responseData ["application"] ?? "PAS2";


        // Параметры со значениями по умолчанию
        $services = !empty($responseData["details"]) ? implode('*', $responseData["details"]) : 'no_extra_charge_codes';

        // Возвращаем все параметры в виде массива
        return [
            'originLatitude' => $originLatitude,
            'originLongitude' => $originLongitude,
            'toLatitude' => $toLatitude,
            'toLongitude' => $toLongitude,
            'tariff' => $tariff,
            'phone' => $phone,
            'user' => $user,
            'time' => $time,
            'date' => $date,
            'services' => $services,
            'city' => $city,
            'application' => $application
        ];
    }
    /**
     * @throws \Exception
     */
    public function costValueExecute(
        $originLatitude,
        $originLongitude,
        $toLatitude,
        $toLongitude,
        $tariff,
        $phone,
        $user,
        $time,
        $date,
        $services,
        $city,
        $application
    )
    {
        try {
            $controller = new AndroidTestOSMController();
            return $controller->costSearchMarkersTime(
                $originLatitude,
                $originLongitude,
                $toLatitude,
                $toLongitude,
                $tariff,
                $phone,
                $user,
                $time,
                $date,
                $services,
                $city,
                $application
            );
        } catch (\Exception $e) {
            Log::error('Ошибка в costValueExecute', [
                'error' => $e->getMessage(),
                'params' => compact('originLatitude', 'originLongitude', 'toLatitude', 'toLongitude', 'services')
            ]);
            return []; // Возвращаем пустой массив вместо ошибки
        }
    }
}

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
}

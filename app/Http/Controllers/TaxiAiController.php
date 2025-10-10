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
     * Очищает адрес от служебных слов и названий города для геокодирования
     */
    protected function cleanAddress(string $address, string $lang = 'uk', bool $isDestination = false, array &$details = []): string
    {
        $prepositions = [
            'uk' => ['з', 'без', 'для', 'до'],
            'ru' => ['с', 'без', 'для', 'до'],
            'en' => ['from', 'to', 'for', 'with'],
        ];

        $cityPrefixes = [
            'uk' => ['Києва', 'Київ', 'Києві'],
            'ru' => ['Киева', 'Киев', 'Киеве'],
            'en' => ['Kyiv'],
        ];

        $words = $prepositions[$lang] ?? $prepositions['uk'];
        $cityPattern = implode('|', array_map('preg_quote', $cityPrefixes[$lang] ?? $cityPrefixes['uk']));
        $prepositionPattern = implode('|', array_map('preg_quote', $words));

        // Убираем название города в любом месте строки
        $address = preg_replace("/\b($cityPattern)\b[,\s]*/iu", '', $address);

        if ($isDestination) {
            // Отделяем адрес до номера дома и детали
            if (preg_match('/(.+?\d+[,\s]*[а-яА-Яa-zA-Z0-9]*)(.*)/u', $address, $matches)) {
                $cleaned = trim($matches[1], " ,");
                $detailsText = trim($matches[2], " ,");
                if (!empty($detailsText)) {
                    // Удаляем предлоги из detailsText
                    $detailsText = preg_replace("/\b($prepositionPattern)\b\s*/iu", '', $detailsText);
                    if ($lang === 'en' && mb_stripos($detailsText, 'air conditioning') !== false) {
                        $details[] = 'air conditioning';
                    } else {
                        $parts = preg_split('/[\s,]+/u', $detailsText, -1, PREG_SPLIT_NO_EMPTY);
                        $existingLower = array_map('mb_strtolower', $details);
                        $baseForms = [
                            'uk' => ['кондиціонером' => 'кондиціонер'],
                            'ru' => ['кондиционером' => 'кондиционер', 'Кондиционером' => 'кондиционер'],
                            'en' => [],
                        ];
                        foreach ($parts as $part) {
                            $partLower = mb_strtolower($part);
                            $basePart = $baseForms[$lang][$part] ?? $partLower;
                            if (!in_array($basePart, $existingLower)) {
                                $details[] = $part;
                                $existingLower[] = $basePart;
                            }
                        }
                    }
                    // Добавляем предлоги в детали
                    foreach ($words as $word) {
                        if (mb_stripos($matches[2], $word) !== false && !in_array(mb_strtolower($word), array_map('mb_strtolower', $details))) {
                            $details[] = $word;
                        }
                    }
                }
            } else {
                $cleaned = preg_replace("/\b($prepositionPattern)\b\s*/iu", '', $address);
            }
        } else {
            // Для origin убираем всё после предлога
            $cleaned = preg_replace("/\b($prepositionPattern)\b.*$/iu", '', $address);
        }

        return trim($cleaned, " ,");
    }

    /**
     * Извлекает destination из текста запроса
     */
    protected function extractDestination(string $text, string $lang): ?array
    {
        $destination = null;
        $details = [];

        if ($lang === 'en') {
            // Для английского: извлекаем всё после "to" и до "with"
            if (preg_match('/to\s+(.+?)(?:\s+with\s+(.+))?$/iu', $text, $matches)) {
                $destination = trim($matches[1], " ,");
                if (!empty($matches[2])) {
                    $detailsText = trim($matches[2], " ,");
                    if (mb_stripos($detailsText, 'air conditioning') !== false) {
                        $details = ['air conditioning'];
                    } else {
                        $details = preg_split('/[\s,]+/u', $detailsText, -1, PREG_SPLIT_NO_EMPTY);
                    }
                }
            }
        } else {
            // Для uk и ru
            $pattern = $lang === 'uk'
                ? '/до\s+(.+?)(?:\s+з\s+(.+))?$/iu'
                : '/до\s+(.+?)(?:\s+с\s+(.+))?$/iu';
            if (preg_match($pattern, $text, $matches)) {
                $destination = trim($matches[1], " ,");
                if (!empty($matches[2])) {
                    $detailsText = trim($matches[2], " ,");
                    $details = preg_split('/[\s,]+/u', $detailsText, -1, PREG_SPLIT_NO_EMPTY);
                }
            }
        }

        // Нормализуем детали, удаляя дубликаты и склонения
        $detailsLower = [];
        $uniqueDetails = [];
        $baseForms = [
            'uk' => ['кондиціонером' => 'кондиціонер'],
            'ru' => ['кондиционером' => 'кондиционер', 'Кондиционером' => 'кондиционер'],
            'en' => [],
        ];
        foreach ($details as $detail) {
            $detailLower = mb_strtolower($detail);
            $baseDetail = $baseForms[$lang][$detail] ?? $detailLower;
            if (!in_array($baseDetail, $detailsLower)) {
                $uniqueDetails[] = $detail;
                $detailsLower[] = $baseDetail;
            }
        }

        return [
            'destination' => $destination,
            'details' => $uniqueDetails,
        ];
    }

    /**
     * Основной метод обработки запроса
     */
    public function parseRequest(Request $request)
    {
        $text = $request->input('text');

        Log::info('[TaxiAi] Incoming text request', [
            'text' => $text,
            'lang' => $request->input('lang', 'uk'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if (!$text) {
            return response()->json(['error' => 'Text is required'], 400);
        }

        try {
            // Всегда автоопределяем язык
            if (preg_match('/\b(from|to|with)\b/i', $text)) {
                $lang = 'en';
            } elseif (preg_match('/\b(з|до|із)\b/u', $text)) {
                $lang = 'uk';
            } elseif (preg_match('/\b(из|до|с)\b/u', $text)) {
                $lang = 'ru';
            } else {
                $lang = 'uk'; // Дефолтный язык
            }

            Log::info('[TaxiAi] Auto-detected language', ['lang' => $lang]);

            $response = Http::post("{$this->baseUrl}/parse", ['text' => $text]);
            $responseData = $response->json();

            $aiResponse = [
                'text' => $responseData['text'] ?? $text,
                'response' => [
                    'text' => $responseData['text'] ?? $text,
                    'entities_spacy' => $responseData['response']['entities_spacy'] ?? [],
                    'entities_hf' => $responseData['response']['entities_hf'] ?? [],
                    'origin' => $responseData['response']['origin'] ?? null,
                    'destination' => $responseData['response']['destination'] ?? null,
                    'details' => $responseData['response']['details'] ?? [],
                ],
            ];

            $geoHelper = new OpenStreetMapHelper();
            $defaultCity = $lang === 'en' ? 'Kyiv' : ($lang === 'ru' ? 'Киев' : 'Київ');

            // Извлекаем destination + details
            $extracted = $this->extractDestination($text, $lang);
            if (!empty($extracted['destination'])) {
                $aiResponse['response']['destination'] = $extracted['destination'];
            }
            if (!empty($extracted['details'])) {
                $existingDetailsLower = array_map('mb_strtolower', $aiResponse['response']['details']);
                $newDetails = [];
                $baseForms = [
                    'uk' => ['кондиціонером' => 'кондиціонер'],
                    'ru' => ['кондиционером' => 'кондиционер', 'Кондиционером' => 'кондиционер'],
                    'en' => [],
                ];
                foreach ($extracted['details'] as $detail) {
                    $detailLower = mb_strtolower($detail);
                    $baseDetail = $baseForms[$lang][$detail] ?? $detailLower;
                    if (!in_array($baseDetail, $existingDetailsLower)) {
                        $newDetails[] = $detail;
                        $existingDetailsLower[] = $baseDetail;
                    }
                }
                $aiResponse['response']['details'] = array_merge($aiResponse['response']['details'], $newDetails);
            }

            // Обработка координат
            foreach (['origin', 'destination'] as $key) {
                $address = $aiResponse['response'][$key] ?? null;
                if (!empty($address)) {
                    $details = $key === 'destination' ? $aiResponse['response']['details'] : [];
                    $cleanAddress = $this->cleanAddress($address, $lang, $key === 'destination', $details);

                    // Формируем full_address
                    $fullAddress = $defaultCity . ', ' . $cleanAddress;
                    Log::info("[TaxiAi] Forming full address for {$key}", [
                        'original' => $address,
                        'cleaned' => $cleanAddress,
                        'full_address' => $fullAddress,
                    ]);

                    $coords = $geoHelper->getCoordinatesByPlaceName($fullAddress, $lang);

                    if ($coords) {
                        $aiResponse['response'][$key . '_coordinates'] = $coords;
                        // Проверка на координаты центра города
                        $cityCenter = ['latitude' => '50.4500336', 'longitude' => '30.5241361'];
                        if (abs(floatval($coords['latitude']) - floatval($cityCenter['latitude'])) < 0.01 &&
                            abs(floatval($coords['longitude']) - floatval($cityCenter['longitude'])) < 0.01) {
                            Log::warning("[TaxiAi] Possible generic city center coordinates for {$key}", [
                                'address' => $fullAddress,
                                'coords' => $coords,
                            ]);
                        }
                    } else {
                        Log::warning("[TaxiAi] {$key} coordinates not found", ['address' => $fullAddress]);
                    }

                    $aiResponse['response'][$key . '_cleaned'] = $cleanAddress;
                    if ($key === 'destination') {
                        $aiResponse['response']['details'] = $details;
                    }
                }
            }

            // Проверка совпадения координат
            if (isset($aiResponse['response']['origin_coordinates'], $aiResponse['response']['destination_coordinates']) &&
                $aiResponse['response']['origin_coordinates'] === $aiResponse['response']['destination_coordinates']) {
                Log::warning("[TaxiAi] Origin and destination coordinates are identical", [
                    'origin' => $aiResponse['response']['origin'],
                    'destination' => $aiResponse['response']['destination'],
                    'coords' => $aiResponse['response']['origin_coordinates'],
                ]);
            }

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

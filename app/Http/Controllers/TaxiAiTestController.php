<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaxiAiTestController extends Controller
{
    /**
     * Тестовый метод для вызова parse из TaxiAiController
     */
    public function runTest()
    {
        $testRequests = [
            [
                "text" => "Замовити таксі з Києва, вул. Лесі Українки, 20 до Києва, вул. Вокзальна, 1 з кондиціонером",
                "lang" => "uk",
                "expected" => [
                    "origin" => "Києва, вул. Лесі Українки, 20",
                    "destination" => "Києва, вул. Вокзальна, 1",
                    "details" => ["CONDIT", "з кондиціонером"],
                    "time_details" => [],
                    "normalized_time_details" => []
                ]
            ],
            [
                "text" => "Замовити таксі з Києва, вул. Січових Стрільців, 15 до Києва, вул. Хрещатик, 10 без водія",
                "lang" => "uk",
                "expected" => [
                    "origin" => "Києва, вул. Січових Стрільців, 15",
                    "destination" => "Києва, вул. Хрещатик, 10",
                    "details" => [], // "без водія" не в KEYWORD_TO_CODE, ожидается пустой массив
                    "time_details" => [],
                    "normalized_time_details" => []
                ]
            ],
            [
                "text" => "Заказать такси из Киева, ул. Леси Украинки, 20 до Киева, ул. Вокзальная, 1 с кондиционером",
                "lang" => "ru",
                "expected" => [
                    "origin" => "Киева, ул. Леси Украинки, 20",
                    "destination" => "Киева, ул. Вокзальная, 1",
                    "details" => ["CONDIT", "с кондиционером"],
                    "time_details" => [],
                    "normalized_time_details" => []
                ]
            ],
            [
                "text" => "Заказать такси из Киева, ул. Сечевых Стрельцов, 15 до Киева, ул. Крещатик, 10 без водителя",
                "lang" => "ru",
                "expected" => [
                    "origin" => "Киева, ул. Сечевых Стрельцов, 15",
                    "destination" => "Киева, ул. Крещатик, 10",
                    "details" => [], // "без водителя" не в KEYWORD_TO_CODE
                    "time_details" => [],
                    "normalized_time_details" => []
                ]
            ],
            [
                "text" => "Order a taxi from Kyiv, Lesi Ukrainky St, 20 to Kyiv, Vokzalna St, 1 with air conditioning",
                "lang" => "en",
                "expected" => [
                    "origin" => "Kyiv, Lesi Ukrainky St, 20",
                    "destination" => "Kyiv, Vokzalna St, 1",
                    "details" => ["CONDIT", "with air conditioning"],
                    "time_details" => [],
                    "normalized_time_details" => []
                ]
            ],
            [
                "text" => "Order a taxi from Kyiv, Sichovykh Striltsiv St, 15 to Kyiv, Khreshchatyk St, 10 without driver",
                "lang" => "en",
                "expected" => [
                    "origin" => "Kyiv, Sichovykh Striltsiv St, 15",
                    "destination" => "Kyiv, Khreshchatyk St, 10",
                    "details" => [], // "without driver" не в KEYWORD_TO_CODE
                    "time_details" => [],
                    "normalized_time_details" => []
                ]
            ],
            // Тесты без lang для проверки автоматического определения
            [
                "text" => "Потрібно таксі з вул. Лесі Українки 1 до вул. Вокзальна 5 завтра о 17:30 з валізою і я буду курити",
                "expected" => [
                    "origin" => "вул. Лесі Українки 1",
                    "destination" => "вул. Вокзальна 5",
                    "details" => ["BAGGAGE", "SMOKE", "TIME", "завтра", "о 17:30"],
                    "time_details" => ["завтра", "о 17:30"],
                    "normalized_time_details" => ["2025-10-12 17:30:00"]
                ]
            ],
            [
                "text" => "Нужно такси из ул. Крещатик 10 до аэропорта срочно с багажом",
                "expected" => [
                    "origin" => "ул. Крещатик 10",
                    "destination" => "аэропорта",
                    "details" => ["BAGGAGE", "TIME", "срочно", "с багажом"],
                    "time_details" => ["срочно"],
                    "normalized_time_details" => ["2025-10-11 07:29:00"] // срочно = сейчас
                ]
            ],
            [
                "text" => "Need a taxi from Shevchenko St. 1 to Vokzalna St. 5 tomorrow at 17:30 with luggage",
                "expected" => [
                    "origin" => "Shevchenko St. 1",
                    "destination" => "Vokzalna St. 5",
                    "details" => ["BAGGAGE", "TIME", "tomorrow", "at 17:30"],
                    "time_details" => ["tomorrow", "at 17:30"],
                    "normalized_time_details" => ["2025-10-12 17:30:00"]
                ]
            ],
            [
                "text" => "Замовити таксі з Києва, вул. Богдана Хмельницького, 10 до Києва, вул. Грушевського, 5 з твариною і чек",
                "expected" => [
                    "origin" => "Києва, вул. Богдана Хмельницького, 10",
                    "destination" => "Києва, вул. Грушевського, 5",
                    "details" => ["ANIMAL", "CHECK_OUT", "з твариною", "чек"],
                    "time_details" => [],
                    "normalized_time_details" => []
                ]
            ],
            // Новые тесты с временными выражениями
            [
                "text" => "Потрібно таксі з вул. Інститутська 20 до пл. Майдан Незалежності 1 післязавтра о 12:00 з дитиною",
                "expected" => [
                    "origin" => "вул. Інститутська 20",
                    "destination" => "пл. Майдан Незалежності 1",
                    "details" => ["BABY_SEAT", "TIME", "післязавтра", "о 12:00", "з дитиною"],
                    "time_details" => ["післязавтра", "о 12:00"],
                    "normalized_time_details" => ["2025-10-13 12:00:00"]
                ]
            ],
            [
                "text" => "Замов таксі з бульв. Тараса Шевченка до вул. Сагайдачного срочно на 13 жовтня в 16 годин 30 хвилин",
                "expected" => [
                    "origin" => "бульв. Тараса Шевченка",
                    "destination" => "вул. Сагайдачного",
                    "details" => ["TIME", "срочно", "на 13 жовтня", "в 16 годин 30 хвилин"],
                    "time_details" => ["срочно", "на 13 жовтня", "в 16 годин 30 хвилин"],
                    "normalized_time_details" => ["2025-10-13 16:30:00"]
                ]
            ],
            [
                "text" => "Need a taxi from Khreshchatyk St. 5 to Boryspil Airport in 3 days at 14:45 with pet",
                "expected" => [
                    "origin" => "Khreshchatyk St. 5",
                    "destination" => "Boryspil Airport",
                    "details" => ["ANIMAL", "TIME", "in 3 days", "at 14:45", "with pet"],
                    "time_details" => ["in 3 days", "at 14:45"],
                    "normalized_time_details" => ["2025-10-14 14:45:00"]
                ]
            ]
        ];
        $controller = new TaxiAiController();

        $results = [];

        foreach ($testRequests as $testData) {
            // Создаем "виртуальный" Request с тестовыми данными
            $request = Request::create('/fake', 'POST', $testData);

            // Вызываем основной метод
            $response = $controller->parse($request);

            // Получаем JSON ответ
            $resultData = $response->getData(true);

            // Проверяем, есть ли ошибка
            if (isset($resultData['error'])) {
                $results[] = [
                    'test_input' => $testData,
                    'response' => $resultData,
                    'pass' => false,
                    'errors' => ['Service error: ' . $resultData['error']]
                ];
                continue;
            }

            $testResult = [
                'test_input' => $testData,
                'response' => $resultData,
                'pass' => true,
                'errors' => []
            ];

            // Сравниваем с ожидаемыми результатами, если есть
            if (isset($testData['expected'])) {
                $responseData = $resultData['response'] ?? [];

                // Проверяем origin
                if (isset($testData['expected']['origin']) &&
                    ($responseData['origin'] ?? '') !== $testData['expected']['origin']) {
                    $testResult['pass'] = false;
                    $testResult['errors'][] = "Origin mismatch: expected '{$testData['expected']['origin']}', got '{$responseData['origin']}'";
                }

                // Проверяем destination
                if (isset($testData['expected']['destination']) &&
                    ($responseData['destination'] ?? '') !== $testData['expected']['destination']) {
                    $testResult['pass'] = false;
                    $testResult['errors'][] = "Destination mismatch: expected '{$testData['expected']['destination']}', got '{$responseData['destination']}'";
                }

                // Проверяем details - ТЕПЕРЬ МАССИВ
                if (isset($testData['expected']['details'])) {
                    $responseDetails = $responseData['details'] ?? [];
                    $expectedDetails = $testData['expected']['details'];

                    // Сортируем для сравнения
                    $responseSorted = $responseDetails;
                    $expectedSorted = $expectedDetails;
                    sort($responseSorted);
                    sort($expectedSorted);

                    if ($responseSorted !== $expectedSorted) {
                        $testResult['pass'] = false;
                        $testResult['errors'][] = "Details mismatch: expected " . json_encode($expectedDetails) . ", got " . json_encode($responseDetails);
                    }
                }

                // Проверяем time_details
                if (isset($testData['expected']['time_details'])) {
                    $responseTimeDetails = $responseData['time_details'] ?? [];
                    $expectedTimeDetails = $testData['expected']['time_details'];
                    sort($responseTimeDetails);
                    sort($expectedTimeDetails);
                    if ($responseTimeDetails !== $expectedTimeDetails) {
                        $testResult['pass'] = false;
                        $testResult['errors'][] = "Time details mismatch: expected " . json_encode($expectedTimeDetails) . ", got " . json_encode($responseTimeDetails);
                    }
                }

                // Проверяем normalized_time_details
                if (isset($testData['expected']['normalized_time_details'])) {
                    $responseNormalized = $responseData['normalized_time_details'] ?? [];
                    $expectedNormalized = $testData['expected']['normalized_time_details'];
                    sort($responseNormalized);
                    sort($expectedNormalized);
                    if ($responseNormalized !== $expectedNormalized) {
                        $testResult['pass'] = false;
                        $testResult['errors'][] = "Normalized time details mismatch: expected " . json_encode($expectedNormalized) . ", got " . json_encode($responseNormalized);
                    }
                }
            }

            // Проверяем язык, если не указан в запросе
            if (!isset($testData['lang'])) {
                $detectedLang = $controller->detectLanguage($testData['text']);
                $expectedLang = $this->detectExpectedLanguage($testData['text']);
                if ($detectedLang !== $expectedLang) {
                    $testResult['pass'] = false;
                    $testResult['errors'][] = "Language detection mismatch: expected '$expectedLang', got '$detectedLang'";
                }
            }

            // Логируем результат теста
            Log::info('[TaxiAiTest] Test result', [
                'test_input' => $testData['text'],
                'pass' => $testResult['pass'],
                'errors' => $testResult['errors']
            ]);

            $results[] = $testResult;
        }

        return response()->json($results);
    }

    /**
     * Вспомогательный метод для определения ожидаемого языка
     */
    protected function detectExpectedLanguage(string $text): string
    {
        if (preg_match('/[їіґє]/u', $text)) {
            return 'uk';
        }
        if (preg_match('/[ёйыэ]/u', $text)) {
            return 'ru';
        }
        if (mb_check_encoding($text, 'ASCII')) {
            return 'en';
        }
        return 'uk';
    }
}

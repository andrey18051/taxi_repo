<?php

namespace App\Http\Controllers;

use App\Helpers\OpenStreetMapHelper;
use App\Models\Orderweb;
use App\Models\WfpInvoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;

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

            $displayName = $request->input('displayName');
            $userEmail = $request->input('userEmail');
            $userId = $request->input('userId');
            $selectedCity = $request->input('selectedCity');
            $payment_type = $request->input('payment_type');

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
            $responseData["city"] = $selectedCity;
            $responseData["payment_type"] = $payment_type;

            $geoHelper = new OpenStreetMapHelper();

            // Обрабатываем адреса с новой логикой очистки
            foreach (['origin', 'destination'] as $key) {
                $address = $responseData[$key] ?? null;
                if (!empty($address)) {



                    if($selectedCity == "OdessaTest") {
                        switch ($lang) {
                            case "ru":
                                $selectedCity = "Одесса";
                                break;
                            case "en":
                                $selectedCity = "Odessa";
                                break;
                            default:
                                $selectedCity = "Одеса";
                        }
                    }

                    Log::info("[TaxiAi] Forming full address for {$key}", [
                        'original' => $address,
                        'selectedCity' => $selectedCity,
                    ]);

                    $coords = $geoHelper->getCoordinatesByPlaceName(
                        $address,
                        $lang,
                        $selectedCity
                    );

                    if ($coords) {
                        $responseData[$key . '_coordinates'] = $coords;
                    } else {
                        Log::warning("[TaxiAi] {$key} coordinates not found", ['address' => $address]);
                    }

                    $responseData[$key . '_cleaned'] = $address;
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
        $payment_type = $responseData['payment_type'] ?? "nal_payment";

        // Проверяем обязательные поля
        if ($originLatitude === null || $originLongitude === null ||
            $toLatitude === null || $toLongitude === null) {
            Log::error("Missing required coordinates in response data");
            return [];
        }

        $tariff = $responseData['tariff'] ?? " ";
        $phone = $responseData['phone'] ?? ' ';
        $user = $responseData["user"] ?? "username (2.1756) *andrey18051@gmail.com*$payment_type";
        $time = $responseData["time"] ?? "no_time";
        $date = $responseData["date"] ?? "no_date";

        $city = $this->getCityCategory($responseData["city"]) ?? 'OdessaTest';

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
            return $controller->costSearchMarkersTimeMyApi(
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
        } catch (InvalidArgumentException $e) {
        }
    }



    public function getCityCategory(string $city): string
    {
        $normalizedCity = mb_strtolower(trim($city));

        Log::debug('[CityCategory] 🏙️ Начало определения категории города', [
            'input' => $city,
            'normalized' => $normalizedCity,
            'timestamp' => now()->toISOString()
        ]);


        $categories = [
            'Kyiv City' => ['київ', 'kyiv', 'kiev', 'киев'],
            'Dnipropetrovsk Oblast' => ['дніпро', 'dnipro', 'днепр'],
            'Zaporizhzhia' => ['запоріжжя', 'zaporizhzhia', 'запорожье'],
            'Cherkasy Oblast' => ['черкаси', 'cherkasy', 'черкассы'],
            'Odessa' => ['одеса', 'odessa', 'одесса'],
            'OdessaTest' => [
                'львів', 'lviv', 'львов',
                'івано-франківськ', 'ivano-frankivsk', 'ивано-франковск',
                'вінниця', 'vinnytsia', 'винница',
                'полтава', 'poltava',
                'суми', 'sumy', 'суммы',
                'харків', 'kharkiv', 'харьков',
                'чернігів', 'chernihiv', 'чернигов',
                'рівне', 'rivne', 'ровно',
                'тернопіль', 'ternopil', 'тернополь',
                'хмельницький', 'khmelnytskyi', 'хмельницкий',
                'ужгород', 'uzhgorod',
                'житомир', 'zhytomyr',
                'кропивницький', 'kropyvnytskyi', 'кропивницкий',
                'миколаїв', 'mykolaiv', 'николаев',
                'чернівці', 'chernivtsi', 'черновцы',
                'луцьк', 'lutsk', 'луцк'
            ]
        ];

        foreach ($categories as $category => $variants) {
            if (in_array($normalizedCity, $variants)) {
                Log::info("[CityCategory] ✅ Категория определена: $category", [
                    'city' => $city,
                    'normalized' => $normalizedCity,
                    'category' => $category
                ]);
                return $category;
            }
        }

        Log::warning('[CityCategory] ⚠️ Город не распознан, используется OdessaTest', [
            'city' => $city,
            'normalized' => $normalizedCity,
            'available_categories' => array_keys($categories)
        ]);

        return 'OdessaTest';
    }

    /**
     * @throws \Exception
     */
    public function createOrder(Request $request)
    {
         Log::info('Create Order Request:', [
            'headers' => $request->headers->all(),
            'all_data' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl()
        ]);

        // Логируем входящие данные запроса
        Log::info('📦 CREATE ORDER REQUEST DATA:', [
            'origin_coordinates' => [
                'latitude' => $request->input('originLatitude', '46.4311896709615'),
                'longitude' => $request->input('originLongitude', '30.7634880146577')
            ],
            'destination_coordinates' => [
                'latitude' => $request->input('toLatitude', '46.3890993667171'),
                'longitude' => $request->input('toLongitude', '30.7504999628167')
            ],
            'route' => [
                'start' => $request->input('routefrom', 'ул. Аркадийское плато (Гагаринское плато), д.5|2, город Одесса'),
                'finish' => $request->input('routeto', 'ул. 16-я станция Большого Фонтана пляж, д.27|24, город Одесса')
            ],
            'user_info' => [
                'display_name' => $request->input('displayName', 'username'),
                'email' => $request->input('userEmail', 'andrey18051@gmail.com'),
                'phone' => $request->input('phone', '+380936734488'),
                'version_app' => $request->input('versionApp', 'last_version')
            ],
            'order_details' => [
                'tariff' => $request->input('tariff', ' '),
                'payment_type' => $request->input('payment_type', 'nal_payment'),
                'client_cost' => $request->input('clientCost', '+380936734488'),
                'additional_cost' => $request->input('add_cost', '0'),
                'required_time' => $request->input('required_time', '01.01.1970 00:00'),
                'comment' => $request->input('comment', 'no_comment'),
                'date' => $request->input('date', 'no_date')
            ],
            'system_info' => [
                'city' => $request->input('city', 'OdessaTest'),
                'application' => $request->input('application', 'PAS2'),
                'wfp_invoice' => $request->input('wfpInvoice', ''),
                'services' => $request->input('services', '')
            ]
        ]);


        $originLatitude = $request->input('originLatitude', '46.4311896709615');
        $originLongitude =  $request->input('originLongitude', '30.7634880146577');
        $toLatitude = $request->input('toLatitude', '46.3890993667171');
        $toLongitude = $request->input('toLongitude', '30.7504999628167');
        $tariff = $request->input('tariff', ' ');
        $phone = $request->input('phone', '+380936734488');
        $clientCost = $request->input('clientCost', 100);
        $displayName = $request->input('displayName', 'username');
        $versionApp = $request->input('versionApp', 'last_version');
        $userEmail = $request->input('userEmail', 'andrey18051@gmail.com');
        $payment_type = $request->input('payment_type', 'nal_payment');
        $user = $displayName  . $versionApp . "*" . $userEmail . "*" . $payment_type;
//            $request->input('user', 'username (2.1758) *andrey18051@gmail.com*nal_payment');
        $add_cost = $request->input('add_cost', 0);
        $time = $request->input('required_time', 'no_time');
        $comment = $request->input('comment', 'no_comment');
        $date = $request->input('date', 'no_date');
        $start = $request->input('routefrom', 'ул. Аркадийское плато (Гагаринское плато), д.5|2, город Одесса');
        $finish = $request->input('routeto', 'ул. 16-я станция Большого Фонтана пляж, д.27|24, город Одесса');
        $wfpInvoice = $request->input('wfpInvoice', "*");
        $services = $request->input('services', 'no_extra_charge_codes');
        $city = $request->input('city', 'OdessaTest');
        $application =  $request->input('application', 'PAS2');

        // Логируем входящие данные запроса с результатами присваивания
        Log::info('📦 CREATE ORDER REQUEST DATA:', [
            'origin_coordinates' => [
                'latitude' => $originLatitude,
                'longitude' => $originLongitude
            ],
            'destination_coordinates' => [
                'latitude' => $toLatitude,
                'longitude' => $toLongitude
            ],
            'route' => [
                'start' => $start,
                'finish' => $finish
            ],
            'user_info' => [
                'display_name' => $displayName,
                'email' => $userEmail,
                'phone' => $phone,
                'version_app' => $versionApp,
                'user_string' => $user,
                'payment_type' => $payment_type
            ],
            'order_details' => [
                'tariff' => $tariff,
                'client_cost' => $clientCost,
                'additional_cost' => $add_cost,
                'required_time' => $time,
                'comment' => $comment,
                'date' => $date
            ],
            'system_info' => [
                'city' => $city,
                'application' => $application,
                'wfp_invoice' => $wfpInvoice,
                'services' => $services
            ]
        ]);

        $response = (new AndroidTestOSMController)->orderClientCostMyApi(
            $originLatitude,
            $originLongitude,
            $toLatitude,
            $toLongitude,
            $tariff,
            $phone,
            $clientCost,
            $user,
            $add_cost,
            $time,
            $comment,
            $date,
            $start,
            $finish,
            $wfpInvoice,
            $services,
            $city,
            $application
        );
        Log::info('📤 Ответ Android API: ' . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return  $response;
    }

    /**
     * @throws \Exception
     */
    public function cancelOrder(Request $request) {
        Log::info('Cancel Order Request:', [
            'headers' => $request->headers->all(),
            'all_data' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl()
        ]);

        $dispatching_order_uid = $request->input('dispatching_order_uid', '');
        $city = $request->input('city', 'ул. 16-я станция Большого Фонтана пляж, д.27|24, город Одесса');
        $application = $request->input('application', 'PAS2');

        $orderweb = Orderweb::where("dispatching_order_uid", $dispatching_order_uid)->first();
        $pay_system = $orderweb->pay_system;

        if($dispatching_order_uid != '') {
            if($pay_system == "nal_payment") {
                $response= (new AndroidTestOSMController)->webordersCancel(
                    $dispatching_order_uid,
                    $city,
                    $application
                );
            } else {
                $response= (new AndroidTestOSMController)->webordersCancelDouble(
                    $dispatching_order_uid,
                    "",
                    "",
                    $city,
                    $application
                );
            }

        } else {
            $response= [
                'response' => "Замовлення скасовано 111",
            ];
        }


        return response()->json($response);
    }

    public function historyOrdersAi ($email, $city, $app): array
    {
        return (new UIDController)->UIDStatusShowEmailCityApp($email, $city, $app);
    }

    /**
     * @throws \Exception
     */
    public function currentStatusOrderAi ($uid, $city, $application)
    {
        return (new AndroidTestOSMController())->historyUIDStatusNew(
            $uid,
            $city,
            $application
        );
    }

    /**
     * @throws \Pusher\PusherException
     * @throws \Pusher\ApiErrorException
     * @throws \Exception
     */
    public function addCostOrderAi ($uid, $addCost)
    {
        // Получаем UID из MemoryOrderChangeController
        $uid = (new MemoryOrderChangeController)->show($uid);
        Log::info("MemoryOrderChangeController возвращает UID: " . $uid);

        // Ищем заказ
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();
        Log::debug("Найден order с UID: " . ($order ? $order->dispatching_order_uid : 'null'));
        Log::info('Processing add cost payment', [
            'order_id' => $order->id ?? null,
            'user_id' => $order->user_id ?? null,
            'pay_system' => $order->pay_system?? null,
            'add_cost' => $addCost
        ]);
        if($order->pay_system == "nal_payment")  {
            return (new UniversalAndroidFunctionController)->startAddCostWithAddBottomUpdate($uid, $addCost);
        } else {

            switch ($order->comment) {
                case "taxi_easy_ua_pas1":
                    $application = "PAS1";
                    break;
                case "taxi_easy_ua_pas2":
                    $application = "PAS2";
                    break;
                case "taxi_easy_ua_pas4":
                    $application = "PAS4";
                    break;
                default:
                    $application = "PAS5";
                    break;
            }

            Log::info('Application determined', [
                'order_id' => $order->id ?? null,
                'comment' => $order->comment ?? null,
                'application' => $application
            ]);

            $originalCity = $order->city;
            switch ($originalCity) {
                case "city_kiev":
                    $city = "Kyiv City";
                    break;
                case "city_cherkassy":
                    $city = "Cherkasy Oblast";
                    break;
                case "city_odessa":
                    $city = "Odessa";
                    if($order->server == "http://188.190.245.102:7303"|| $order->server == "my_server_api") {
                        $city = "OdessaTest  ";
                    }
                    break;
                case "city_zaporizhzhia":
                    $city = "Zaporizhzhia";
                    break;
                case "city_dnipro":
                    $city = "Dnipropetrovsk Oblast";
                    break;
                case "city_lviv":
                case "city_ivano_frankivsk":
                case "city_vinnytsia":
                case "city_poltava":
                case "city_sumy":
                case "city_kharkiv":
                case "city_chernihiv":
                case "city_rivne":
                case "city_ternopil":
                case "city_khmelnytskyi":
                case "city_zakarpattya":
                case "city_zhytomyr":
                case "city_kropyvnytskyi":
                case "city_mykolaiv":
                case "city_chernivtsi":
                case "city_lutsk":
                    $city = "OdessaTest";
                    break;
                default:
                    $city = "OdessaTest";
            }

            Log::info('City determined', [
                'order_id' => $order->id ?? null,
                'original_city' => $originalCity ?? null,
                'server' => $order->server ?? null,
                'determined_city' => $city
            ]);

            $orderReference = self::generateInvoiceNumber();
            $amount = $addCost;
            $productName = "Інша допоміжна діяльність у сфері транспорту";
            $clientEmail = $order->email;
            $clientPhone = $order->user_phone;

            Log::info('Payment request prepared', [
                'order_id' => $order->id ?? null,
                'order_reference' => $orderReference,
                'amount' => $amount,
                'application' => $application,
                'city' => $city,
                'product_name' => $productName,
                'client_email' => $clientEmail,
                'client_phone' => $clientPhone
            ]);

            try {
                Log::info('Creating new WfpInvoice record', [
                    'order_reference' => $orderReference,
                    'reason' => 'Invoice not found, creating new record'
                ]);


                $wfpInvoices = new WfpInvoice();
                $wfpInvoices->orderReference = $orderReference;
                $wfpInvoices->amount = $amount;
                $wfpInvoices->dispatching_order_uid = $uid ?? null;
                $merchantInfo = (new WfpController)->checkMerchantInfo($order);
                if (
                    !empty($merchantInfo['merchantAccount'])
                    && $merchantInfo['merchantAccount'] !== 'errorMerchantAccount'
                ) {
                    $wfpInvoices->merchantAccount = $merchantInfo['merchantAccount'];
                }
                $wfpInvoices->save();

                Log::info('New WfpInvoice created successfully', [
                    'order_reference' => $orderReference,
                    'dispatching_order_uid' => $uid,
                    'invoice_id' => $wfpInvoices->id?? null,
                    'created_at' => $wfpInvoices->created_at ?? null,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to create WfpInvoice', [
                    'order_reference' => $orderReference,
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString(),
                ]);
                throw new \Exception("Failed to create invoice record: " . $e->getMessage());
            }

            try {
                $result = (new WfpController)->chargeActiveTokenAddCost(
                    $application,
                    $city,
                    $orderReference,
                    $amount,
                    $productName,
                    $clientEmail,
                    $clientPhone
                );

                Log::info('Payment request successful', [
                    'order_id' => $order->id ?? null,
                    'order_reference' => $orderReference,
                    'result' => $result
                ]);

                return $result;

            } catch (\Exception $e) {
                Log::error('Payment request failed', [
                    'order_id' => $order->id ?? null,
                    'order_reference' => $orderReference,
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString()
                ]);

                throw $e; // или обработайте ошибку по-другому
            }

        }

    }
    /**
     * Вариант с префиксом из конфига
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = config('app.invoice_prefix', 'INV');
        $timestamp = Carbon::now()->format('YmdHisv');
        $randomValue = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}_{$timestamp}_{$randomValue}";
    }
}

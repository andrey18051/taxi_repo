<?php

namespace App\Http\Controllers;

use App\Helpers\OpenStreetMapHelper;
use App\Mail\Driver;
use App\Mail\JobDriver;
use App\Mail\Server;
use App\Models\Autos;
use App\Models\DriverHistory;
use App\Models\DriverMemoryOrder;
use App\Models\Drivers;
use App\Models\Orderweb;
use App\Models\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Factory;

class DriverController extends Controller
{
    protected $osmHelper;


    public function __construct(OpenStreetMapHelper $osmHelper) {
        $this->osmHelper = $osmHelper;
    }

    public function index(): int
    {
        return 200;
    }

    public function driverAll()
    {
        return  (new FCMController)->driverAll();
    }

    public function auto(
        string $city,
        string $first_name,
        string $second_name,
        string $email,
        string $phone,
        string $brand,
        string $model,
        string $type,
        string $color,
        string $year,
        string $number,
        $services
    ) {
        $driver = new Drivers();
        $driver->city = $city;
        $driver->first_name = $first_name;
        $driver->second_name = $second_name;
        $driver->email = $email;
        $driver->phone = $phone;
        $driver->save();

        $auto =  new Autos();
        $auto->brand = $brand;
        $auto->model = $model;
        $auto->type  = $type;
        $auto->color = $color;
        $auto->year = $year;
        $auto->number = $number;
        $auto->driver_id  = $driver->id;
        $auto->save();


        $driverHistory =  new DriverHistory();
        $driverHistory->name = $driver->id . "*" . $auto->id . "*" . $services;
        $driverHistory->save();

        $keywords = preg_split("/[*]+/", $services);
        $serv_info = "службі таксі: ";
        foreach ($keywords as $value) {
            $serv_info = $serv_info . " " . $value;
        }

        $telegramMessage = new TelegramController();

        //*****
        $subject = "Прошу розглянути мою кандидатуру для роботи водієм в " . $serv_info . ".";
        $params = [
            'subject' => $subject,
            'city' => "Місто: " . $city,
            'first_name' => "Ім'я: " . $first_name,
            'second_name' => "Прізвище: " . $second_name,
            'email' => "Email: " . $email,
            'phone' => "Телефон: " . $phone,
            'brand' => "Марка авто: " . $brand,
            'model' => "Модель: " . $model,
            'type' => "Тип кузова: " . $type,
            'color' => "Колір: " . $color,
            'year' => "Рік випуску: " . $year,
            'number' => "Державний номер: " . $number
        ];

        $messageAboutDriver = $subject
            . " Місто: " . $city . ". "
            . "Ім'я: " . $first_name . ". "
            . "Прізвище: " . $second_name . ". "
            . "Email: " . $email . ". "
            . "Телефон: " . $phone . ". "
            . "Марка авто: " . $brand . ". "
            . "Модель: " . $model . ". "
            . "Тип кузова: " . $type . ". "
            . "Колір: " . $color . ". "
            . "Рік випуску: " . $year . ". "
            . "Державний номер: " . $number . ". ";

        $telegramMessage->sendAboutDriverMessage("1379298637", $messageAboutDriver);
//        $telegramMessage->sendAboutDriverMessage("120352595", $messageAboutDriver);
        Mail::to("taxi.easy.ua@gmail.com")->send(new JobDriver($params));
        Mail::to("takci2012@gmail.com")->send(new JobDriver($params));
//***

//        $services = Services::all()->toArray();
//        foreach ($keywords as $value_key) {
//            foreach ($services as $value_serv) {
//                if ($value_key == $value_serv['name']) {
//                    $subject = "Прошу розглянути мою кандидатуру для роботи водієм в службі таксі " . $value_serv['name'] . ".";
//                    $params = [
//                        'subject' => $subject,
//                        'city' => "Місто: " . $city,
//                        'first_name' => "Ім'я: " . $first_name,
//                        'second_name' => "Прізвище: " . $second_name,
//                        'email' => "Email: " . $email,
//                        'phone' => "Телефон: " . $phone,
//                        'brand' => "Марка авто: " . $brand,
//                        'model' => "Модель: " . $model,
//                        'type' => "Тип кузова: " . $type,
//                        'color' => "Колір: " . $color,
//                        'year' => "Рік випуску: " . $year,
//                        'number' => "Державний номер: " . $number
//                    ];
//
//                    $messageAboutDriver = $subject
//                        . " Місто: " . $city . ". "
//                        . "Ім'я: " . $first_name . ". "
//                        . "Прізвище: " . $second_name . ". "
//                        . "Email: " . $email . ". "
//                        . "Телефон: " . $phone . ". "
//                        . "Марка авто: " . $brand . ". "
//                        . "Модель: " . $model . ". "
//                        . "Тип кузова: " . $type . ". "
//                        . "Колір: " . $color . ". "
//                        . "Рік випуску: " . $year . ". "
//                        . "Державний номер: " . $number . ". ";
//
//                    $telegramMessage->sendAboutDriverMessage($value_serv['telegram_id'], $messageAboutDriver);
//                    Mail::to($value_serv['email'])->send(new JobDriver($params));
//                }
//            }
//        }
    }

    public function sendCode($phone): int
    {
        $connectAPI = WebOrderController::connectApi();

        $url = $connectAPI . '/api/approvedPhones/sendConfirmCode';
        $response = Http::post($url, [
            'phone' => substr($phone, 3), //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
            'taxiColumnId' => config('app.taxiColumnId') //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
        ]);
        return $response->status();
//        return 200;
    }

    public function approvedPhones($phone, $confirm_code): int
    {
        $connectAPI = WebOrderController::connectApi();

        $url = $connectAPI . '/api/approvedPhones/';
        $response = Http::post($url, [
            'phone' => substr($phone, 3), //Обязательный. Номер мобильного телефона
            'confirm_code' => $confirm_code //Обязательный. Код подтверждения.
        ]);
        return $response->status();
    }

    public function orderTaking($uid, $uidDriver)
    {
        $uid = (new MemoryOrderChangeController)->show($uid);
        Log::info("orderTaking" . $uid);
        (new FCMController)->deleteDocumentFromFirestore($uid);

        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
        if ($orderweb) {
            switch ($orderweb->server) {
                case 'http://31.43.107.151:7303':
                    $city = "OdessaTest";
                    break;
                case 'http://167.235.113.231:7307':
                case 'http://167.235.113.231:7306':
                case 'http://134.249.181.173:7208':
                case 'http://91.205.17.153:7208':
                    $city = "Kyiv City";
                    break;
                case 'http://142.132.213.111:8071':
                case 'http://167.235.113.231:7308':
                    $city = "Dnipropetrovsk Oblast";
                    break;
                case 'http://142.132.213.111:8072':
                    $city = "Odessa";
                    break;
                case 'http://142.132.213.111:8073':
                    $city = "Zaporizhzhia";
                    break;
                case 'http://134.249.181.173:7201':
                case 'http://91.205.17.153:7201':
                    $city = "Cherkasy Oblast";
                    break;
            }

            switch ($orderweb->comment) {
                case 'taxi_easy_ua_pas1':
                    $application = "PAS1";
                    break;
                case 'taxi_easy_ua_pas2':
                    $application = "PAS2";
                    break;
                default:
                    $application = "PAS4";
            }

            $connectAPI = $orderweb->server;

            $authorization = (new UniversalAndroidFunctionController)->authorizationApp($city, $connectAPI, $application);
            $url = $connectAPI . '/api/weborders/cancel/' . $uid;
            $response = Http::withHeaders([
                "Authorization" => $authorization,
                "X-WO-API-APP-ID" =>(new  AndroidTestOSMController)::identificationId($application),
                "X-API-VERSION" => (new UniversalAndroidFunctionController)->apiVersionApp($city, $connectAPI, $application)
            ])->put($url);

            $json_arrWeb = json_decode($response, true);

            Log::debug("json_arrWeb_bonus", $json_arrWeb);
            if ($json_arrWeb["order_client_cancel_result"] != 1) {
                AndroidTestOSMController::repeatCancel(
                    $url,
                    $authorization,
                    $authorization,
                    $application,
                    $city,
                    $connectAPI,
                    $uid
                );
            }

            $dataDriver = (new FCMController)->readDriverInfoFromFirestore($uidDriver);

            $orderweb->auto = json_encode($dataDriver);
            $orderweb->closeReason = "101";
            $orderweb->save();

            (new FCMController)->writeDocumentToBalanceFirestore($uid, $uidDriver, "hold");

            (new MessageSentController())->sentCarTakingInfo($orderweb);
            $status = "orderTaking";
            // Вернуть JSON с сообщением об успехе
            return response()->json([
                'status' => $status,
                'message' => 'orderTaking successfully'
            ], 200);
        } else {
            $status = "orderTaking";
            // Вернуть JSON с сообщением об успехе
            return response()->json([
                'status' => $status,
                'message' => "$uid  UnSuccessfully find"
            ], 200);
        }
    }

    /**
     * @throws \Exception
     */
    public function driverInStartPoint($uid, $uidDriver)
    {
        $uid = (new MemoryOrderChangeController)->show($uid);
        (new MessageSentController())->sentDriverInStartPoint($uid, $uidDriver);

        $status = "driverInStartPoint";
        // Вернуть JSON с сообщением об успехе
        return response()->json([
            'status' => $status,
            'message' => 'driverInStartPoint successfully'
        ], 200);
    }
    /**
     * @throws \Exception
     */
    public function driverCloseOrder($uid, $uidDriver)
    {
        $uid = (new MemoryOrderChangeController)->show($uid);

        (new FCMController)->waitForReturnAndSendDelete($uid, $uidDriver);


        $status = "closed";

        (new MessageSentController())->sentDriverCloseOrder($uid);
        (new FCMController)->deleteDocumentFromFirestoreOrdersTaking($uid);
        (new FCMController())->writeDocumentToHistoryFirestore($uid, $status);

        $status = "driverCloseOrder";
        // Вернуть JSON с сообщением об успехе
        return response()->json([
            'status' => $status,
            'message' => 'driverCloseOrder successfully'
        ], 200);
    }

    /**
     * @throws \Exception
     */
    public function driverUpdateInfo($uidDriver)
    {
        (new MessageSentController())->sentDriverUpdateAccount($uidDriver);

        $status = "driverUpdateInfo";
        // Вернуть JSON с сообщением об успехе
        return response()->json([
            'status' => $status,
            'message' => 'driverUpdateInfo successfully'
        ], 200);
    }

    /**
     * @throws \Exception
     */
    public function driverUpdateCarInfo($uidDriver, $carId)
    {
        (new MessageSentController())->sentDriverUpdateCar($uidDriver, $carId);

        $status = "driverUpdateInfo";
        // Вернуть JSON с сообщением об успехе
        return response()->json([
            'status' => $status,
            'message' => 'driverUpdateCarInfo successfully'
        ], 200);
    }

    public function verifyDriverUpdateInfo($uidDriver)
    {
        (new FCMController())->writeDocumentToVerifyUserFirestore($uidDriver);

        $status = "verifyDriverUpdateInfo";
        // Вернуть JSON с сообщением об успехе
        return response()->json([
            'status' => $status,
            'message' => 'Данные водителя подтверждены'
        ], 200);
    }


    /**
     * @throws \Exception
     */
    public function verifyDriverUpdateCarInfo($carId)
    {
        (new FCMController())->writeDocumentToVerifyCarFirestore($carId);

        $status = "verifyDriverUpdateCarInfo";
        // Вернуть JSON с сообщением об успехе
        return response()->json([
            'status' => $status,
            'message' => 'Данные автомобиля подтверждены'
        ], 200);
    }


    /**
     * @throws \Exception
     */
    public function orderUnTaking($uid, $uidDriver)
    {
        if ((new FCMController)->isHoldCompleted($uid, $uidDriver)) {
            Log::info("Return request completed for UID {$uidDriver}. Sending return request...");
            (new FCMController)->writeDocumentToBalanceFirestore($uid, $uidDriver, "return");
        }


        (new FCMController)->deleteOrderTakingDocumentFromFirestore($uid, $uidDriver);
        $uid = (new MemoryOrderChangeController)->show($uid);
        self::createNewOrder($uid);

        $status = "orderUnTaking";
        // Вернуть JSON с сообщением об успехе
        return response()->json([
            'status' => $status,
            'message' => 'orderUnTaking successfully'
        ], 200);
    }
    public function orderUnTakingPersonal($uid, $uidDriver)
    {
        $uid = (new MemoryOrderChangeController)->show($uid);
        (new FCMController)->deleteOrderPersonalDocumentFromFirestore($uid, $uidDriver);

        $status = "orderUnTakingPersonal";
        // Вернуть JSON с сообщением об успехе
        return response()->json([
            'status' => $status,
            'message' => 'orderUnTakingPersonal successfully'
        ], 200);
    }

    public function driverCardPayToBalance($uidDriver, $amount, $language)
    {
        try {
            $response = (new WfpController)->createInvoiceVod($uidDriver, $amount, $language);

            // Предполагается, что метод createInvoiceVod возвращает объект с необходимыми данными
            if ($response->successful()) {
                $responseData = $response->json(); // Преобразование ответа в массив
                return [
                    'reasonCode' => $responseData['reasonCode'] ?? null,
                    'invoiceUrl' => $responseData['invoiceUrl'] ?? null
                ];
            } else {
                Log::error("Error creating invoice: " . $response->body());
                return [
                    'reasonCode' => null,
                    'invoiceUrl' => null
                ];
            }
        } catch (\Exception $e) {
            Log::error("Exception in driverCardPayToBalance: " . $e->getMessage());
            return [
                'reasonCode' => null,
                'invoiceUrl' => null
            ];
        }
    }

    public function driverCardPayDownBalance($uidDriver, $amount, $comment, $selectedTypeCode)
    {
        (new FCMController())->driverCardPayDownBalance($uidDriver, $amount, $comment, $selectedTypeCode);


        $status = "Вывод с баланса";
        return response()->json([
            'status' => $status,
            'message' => 'driverCardPayDownBalance successfully'
        ], 200);
    }


    public function driverCardPayByTokenToBalance($uidDriver, $amount, $recToken)
    {
        try {
            $response = (new WfpController)->chargeVOD($uidDriver, $amount, $recToken);

            // Предполагается, что метод createInvoiceVod возвращает объект с необходимыми данными
            if ($response->successful()) {
                $responseData = $response->json(); // Преобразование ответа в массив
                if ($responseData['transactionStatus'] == "Approved") {
                    $status = "payment_card";
                    (new FCMController)->writeDocumentToBalanceAddFirestore($uidDriver, $amount, $status);
                    (new MessageSentController())->sentDriverPayToBalance($uidDriver, $amount);
                }
                return [
                    'reasonCode' => $responseData['reasonCode'] ?? null,
                    'transactionStatus' => $responseData['transactionStatus'] ?? null
                ];
            } else {
                Log::error("Error driverCardPayByTokenToBalance: " . $response->body());
                return [
                    'reasonCode' => null,
                    'transactionStatus' => null
                ];
            }
        } catch (\Exception $e) {
            Log::error("Exception in driverCardPayByTokenToBalance: " . $e->getMessage());
            return [
                'reasonCode' => null,
                'transactionStatus' => null
            ];
        }
    }

    /**
     * @throws \Exception
     */
    public function driverCurrentSectorLocationStoreFirestore(
        $uidDriver,
        $latitude,
        $longitude
    ) {
        Log::info("driverCurrentSectorLocationStoreFirestore! $uidDriver");
        (new FCMController)->writeDocumentCurrentSectorLocationFirestore(
            $uidDriver,
            $latitude,
            $longitude
        );

        $status = "Смена сектора";
        return response()->json([
            'status' => $status,
            'message' => 'driverCurrentSectorLocationStoreFirestore successfully'
        ], 200);
    }
//
//    public function findDriverInSectorFromFirestore(
//        $latitude,
//        $longitude
//    ) {
//
//        $nearestDriver = (new FCMController)->findDriverInSectorFromFirestore(
//            $latitude,
//            $longitude
//        );
//        return  $nearestDriver['driver_uid'];
//    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function createNewOrder($uid)
    {
        $order = Orderweb::where("dispatching_order_uid", $uid)->first();
        $orderMemory = DriverMemoryOrder::where("dispatching_order_uid", $uid)->first();

        $authorization = $orderMemory->authorization;
        $identificationId= $orderMemory->identificationId;
        $apiVersion = $orderMemory->apiVersion;


        $url = $orderMemory->connectAPI;
        $parameter = json_decode($orderMemory->response, true);

        $maxExecutionTime = 60; // Максимальное время выполнения - 3 часа

        $startTime = time();

        do {
            try {
                $response = Http::withHeaders([
                    "Authorization" => $authorization,
                    "X-WO-API-APP-ID" => $identificationId,
                    "X-API-VERSION" => $apiVersion
                ])->post($url, $parameter);


                // Проверяем успешность ответа
                if ($response->successful() && $response->status() == 200) {
                    //проверка статуса после отмены
                    $responseArr = json_decode($response, true);
                    Log::debug(" orderNewCreat responseArr: ", $responseArr);
                    $orderNew = $responseArr["dispatching_order_uid"];
                    Log::debug(" orderNewCreat: " . $url . $orderNew);

                    $order_old_uid = $order->dispatching_order_uid;
                    $order_new_uid = $orderNew;

                    (new MemoryOrderChangeController)->store($order_old_uid, $order_new_uid);

                    //Тело запроса привязываем к новом адресу
                    $orderMemory->dispatching_order_uid = $order_new_uid;
                    $orderMemory->save();

                    $order->dispatching_order_uid = $order_new_uid;
                    $order->auto = "";
                    $order->closeReason = "-1";
                    $order->closeReasonI = "0";
                    $order->save();


                    (new FCMController)->writeDocumentToFirestore($order_new_uid);
                    (new MessageSentController())->sentCarRestoreOrder($order);

                    return $order;
                } else {
                    // Логируем ошибки в случае неудачного запроса
                    Log::error("Request failed with status: " . $response->status());
                    Log::error("Response: " . $response->body());
                    $result = null;
                }
            } catch (\Exception $e) {
                // Обработка исключений
                Log::error("Exception caught: " . $e->getMessage());
                $result = null;
            }
            sleep(5);
        } while (!$result && time() - $startTime < $maxExecutionTime);
    }

    public function uidDriver($uid)
    {
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
        $storedData = $orderweb->auto;

        $dataDriver = json_decode($storedData, true);
        return $dataDriver["uid"];
    }


    public function mapboxAccessToken()
    {
        $status = "mapboxAccessToken";
        return response()->json([
            'status' => $status,
            'message' => config('app.keyMapbox')
        ], 200);
    }

}

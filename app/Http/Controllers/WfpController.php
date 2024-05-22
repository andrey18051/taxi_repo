<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Mail\Server;
use App\Models\Card;
use App\Models\City;
use App\Models\City_PAS1;
use App\Models\City_PAS2;
use App\Models\City_PAS4;
use App\Models\Orderweb;
use App\Models\User;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Diff\Exception;

class WfpController extends Controller
{
    public static function messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $uid)
    {
        $order = Orderweb::where("dispatching_order_uid", $bonusOrderHold)->first();
        $wfp_order_id = $order->wfp_order_id;
        $amount = $order->web_cost;
        $connectAPI = $order->server;

        $subject = "Ошибка проверки статуса заказа";
        $localCreatedAt = Carbon::parse($order->created_at)->setTimezone('Europe/Kiev');
        $messageAdmin = "Заказ холд $bonusOrderHold.
                 Время $localCreatedAt.
                 Ошибка проверки статуса заказа $uid. Сервер $connectAPI.
                 Маршрут $order->routefrom - $order->routeto.
                 Телефон клиента:  $order->user_phone.
                 Номер заказа WFP: $wfp_order_id.
                 Сумма холда $amount грн.";
        $paramsAdmin = [
            'subject' => $subject,
            'message' => $messageAdmin,
        ];
        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($messageAdmin);
            $alarmMessage->sendMeMessage($messageAdmin);
        } catch (Exception $e) {
            $subject = 'Ошибка в телеграмм';
            $paramsCheck = [
                'subject' => $subject,
                'message' => $e,
            ];

            Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
        };

        Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
        Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
    }

    public function returnUrl()
    {
        Log::debug("returnUrl");
        return "returnUrl";
    }

    public function serviceUrl(Request $request)
    {
        Log::debug("serviceUrl " . $request);

        $data = json_decode($request->getContent(), true);
        Log::debug($data['email']);
        Log::debug($data['recToken']);

        $user = User::where('email', $data['email'])->first();

        if ($user && isset($data['recToken']) && $data['recToken'] != "") {
            $cardType = $data['cardType'];
            if (isset($data['issuerBankName']) && $data['issuerBankName'] != null) {
                $bankName = $data['issuerBankName'];
            } else {
                $bankName = " ";
            }

            $card = Card::where('pay_system', 'wfp')
                ->where('user_id', $user->id)
                ->where('rectoken', $data['recToken'])
                ->where('merchant', $data['merchantAccount'])
                ->first();

            if (!$card) {
                $card = new Card();
                $card->user_id = $user->id;
            }

            $card->pay_system = 'wfp';
            $card->masked_card = $data['cardPan'];
            $card->card_type = $cardType;
            $card->bank_name = $bankName;
            $card->rectoken = $data['recToken'];
            $card->merchant = $data['merchantAccount'];
//                $card->rectoken_lifetime = $data['rectoken_lifetime'];
            $card->save();
        }

        $time = strtotime(date('Y-m-d H:i:s'));

        $params = [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time
        ];
        $secretKey = "7aca3657f12fca79d876dcb50e2d84d71f544516";

        $signature = self::generateHmacMd5Signature($params, $secretKey, "serviceUrl");

        return [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time,
            "signature" =>  $signature
        ];
    }
    public function serviceUrlVerify(Request $request)
    {
        Log::debug($request);
    }
    public function createInvoice(
        $application,
        $city,
        $orderReference,
        $amount,
        $language,
        $productName,
        $clientEmail,
        $clientPhone
    ): Response {
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
        }

        $orderDate =  strtotime(date('Y-m-d H:i:s'));

        $params = [
            "merchantAccount" => $merchantAccount,
            "merchantDomainName" => "m.easy-order-taxi.site",
            "orderReference" => $orderReference,
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1]
        ];
//dd($params);

//        $merchantAccount = "test_merch_n1";
//        $secretKey = "flk3409refn54t54t*FNJRET";

        $params = [
            "transactionType" => "CREATE_INVOICE",
            "merchantAccount" => $merchantAccount,
            "merchantAuthType" => "SimpleSignature",
            "merchantDomainName" => "m.easy-order-taxi.site",
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "createInvoice"),
            "apiVersion" => 1,
            "language" => $language,
//            "returnUrl" => "https://m.easy-order-taxi.site/wfp/returnUrl",
            "serviceUrl" => "https://m.easy-order-taxi.site/wfp/serviceUrl",
            "orderReference" => $orderReference,
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "orderTimeout" => 86400,
            "merchantTransactionType" => "AUTH",
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1],
            "paymentSystems" => "card;privat24",
            "clientEmail" => $clientEmail,
            "clientPhone" => $clientPhone,
            "notifyMethod" => "bot"
        ];

// Відправлення POST-запиту
        $response = Http::post('https://api.wayforpay.com/api', $params);
        Log::debug("CREATE_INVOICE", ['response' => $response->body()]);
        return $response;
    }


    public function verify(
        $application,
        $city,
        $orderReference,
        $clientEmail,
        $clientPhone
    ): Response {
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
        }

        $params = [
            "merchantAccount" => $merchantAccount,
            "merchantDomainName" => "m.easy-order-taxi.site",
            "orderReference" => $orderReference,
            "amount" => "0",
            "currency" => "UAH",
        ];
//dd($params);

//        $merchantAccount = "test_merch_n1";
//        $secretKey = "flk3409refn54t54t*FNJRET";

        $params = [
            "merchantAccount" => $merchantAccount,
            "merchantDomainName" => "m.easy-order-taxi.site",
            "merchantAuthType" => "SimpleSignature",
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "verify"),
            "orderReference" => $orderReference,
            "amount" => "0",
            "currency" => "UAH",
            "clientEmail" => $clientEmail,
            "clientPhone" => $clientPhone,
//            "returnUrl" => "https://m.easy-order-taxi.site/wfp/returnUrl",
            "serviceUrl" => "https://m.easy-order-taxi.site/wfp/serviceUrl",
            "language"=> "RU",
            "paymentSystems" => "lookupCard",
            "verifyType" => "confirm",
        ];

// Відправлення POST-запиту
        $response = Http::post('https://secure.wayforpay.com/verify?behavior=offline', $params);

        Log::debug("verify: ", ['response' => $response->body()]);
        return $response;
    }

    public function checkStatus(
        $application,
        $city,
        $orderReference
    ): Response {
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
        }

        $params = [
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
        ];
//dd($params);

//        $merchantAccount = "test_merch_n1";
//        $secretKey = "flk3409refn54t54t*FNJRET";

        $params = [
            "transactionType" => "CHECK_STATUS",
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "checkStatus"),
            "apiVersion" => 1,
        ];

// Відправлення POST-запиту
        $response = Http::post('https://api.wayforpay.com/api', $params);
        Log::debug(["checkStatus " . 'response' => $response->body()]);

        if (isset($data['transactionStatus']) && !empty($data['transactionStatus'])) {
            $order = Orderweb::where("wfp_order_id", $orderReference)->first();
            $order->wfp_status_pay = $data['transactionStatus'];
            $order->save();
        }

        return $response;
    }

    public function refund(
        $application,
        $city,
        $orderReference,
        $amount
    ): Response {
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
        }

        $params = [
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
            "amount" => $amount,
            "currency" => "UAH",
        ];

        $params = [
            "transactionType" => "REFUND",
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
            "amount" => $amount,
            "currency" => "UAH",
            "comment" => "Повернення платежу",
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "refund"),
            "apiVersion" => 1
        ];

// Відправлення POST-запиту
        $response = Http::post('https://api.wayforpay.com/api', $params);
        Log::debug("CHECK_STATUS:", ['response' => $response->body()]);
        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($response->body());
            $alarmMessage->sendMeMessage($response->body());
        } catch (Exception $e) {
            $subject = 'Ошибка в телеграмм';
            $paramsCheck = [
                'subject' => $subject,
                'message' => $e,
            ];

            Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
        };

        $subject = "Возврат холда";
        $paramsAdmin = [
            'subject' => $subject,
            'message' => $response->body(),
        ];

        Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
        Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
        return $response;
    }


    public function settle(
        $application,
        $city,
        $orderReference,
        $amount
    ): Response {
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
        }
//            dd(" /merchantAccount- " . $merchantAccount . "\n"
//                . " /secretKey- " . $secretKey . "\n"
//                . " /orderReference- " . $orderReference . "\n"
//                . " /amount- " . $amount . "\n"
//                . " /language- " . $language . "\n"
//                . " /productName- " . $productName . "\n"
//                . " /clientEmail- " . $clientEmail . "\n"
//                . " /clientPhone- " . $clientPhone
//            );
//
        $params = [
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
            "amount" => $amount,
            "currency" => "UAH",
        ];
//dd($params);

//        $merchantAccount = "test_merch_n1";
//        $secretKey = "flk3409refn54t54t*FNJRET";

        $params = [
            "transactionType" => "SETTLE",
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
            "amount" => $amount,
            "currency" => "UAH",
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "settle"),
            "apiVersion" => 1
        ];

// Відправлення POST-запиту
        $response = Http::post('https://api.wayforpay.com/api', $params);
        Log::debug("SETTLE:", ['response' => $response->body()]);

        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($response->body());
            $alarmMessage->sendMeMessage($response->body());
        } catch (Exception $e) {
            $subject = 'Ошибка в телеграмм';
            $paramsCheck = [
                'subject' => $subject,
                'message' => $e,
            ];

            Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
        };

        $subject = "Списание холда";
        $paramsAdmin = [
            'subject' => $subject,
            'message' => $response->body(),
        ];

        Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
        Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
        return $response;
    }


    public function purchase(
        $application,
        $city,
        $orderReference,
        $amount,
        $productName,
        $clientEmail,
        $clientPhone,
        $recToken
    ) {
//        dd($application . " " .
//            $city . " " .
//            $orderReference . " " .
//            $amount . " " .
//            $productName . " " .
//            $recToken . " " );
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
        }

        $orderDate =  strtotime(date('Y-m-d H:i:s'));

        $params = [
            "merchantAccount" => $merchantAccount,
            "merchantDomainName" => "m.easy-order-taxi.site",
            "orderReference" => $orderReference,
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1]
        ];
//        dd($params);
        $params = [
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "purchase"),
            "merchantAuthType" => "SimpleSignature",
            "merchantDomainName" => "m.easy-order-taxi.site",
            "merchantTransactionSecureType" => "AUTO",
            "apiVersion" => 1,
//            "returnUrl" => "https://m.easy-order-taxi.site/wfp/returnUrl",
            "serviceUrl" => "https://m.easy-order-taxi.site/wfp/serviceUrl",
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "recToken" => "recToken",
            "merchantTransactionType" => "AUTH",
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1],
            "paymentSystems" => "card;privat24",
        ];

// Відправлення POST-запиту
        $response = Http::dd()->post('https:https://secure.wayforpay.com/pay', $params);
        Log::debug("purchase: ", $response);
        return $response;
    }

    public function charge(
        $application,
        $city,
        $orderReference,
        $amount,
        $productName,
        $clientEmail,
        $clientPhone,
        $recToken
    ) {
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
                $merchantAccount = $merchant->wfp_merchantAccount;
                $secretKey = $merchant->wfp_merchantSecretKey;
        }
//            dd(" /merchantAccount- " . $merchantAccount . "\n"
//                . " /secretKey- " . $secretKey . "\n"
//                . " /orderReference- " . $orderReference . "\n"
//                . " /amount- " . $amount . "\n"
//                . " /language- " . $language . "\n"
//                . " /productName- " . $productName . "\n"
//                . " /clientEmail- " . $clientEmail . "\n"
//                . " /clientPhone- " . $clientPhone
//            );
//

        $orderDate =  strtotime(date('Y-m-d H:i:s'));

        $params = [
            "merchantAccount" => $merchantAccount,
            "merchantDomainName" => "m.easy-order-taxi.site",
            "orderReference" => $orderReference,
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1]
        ];
//dd($params);

//        $merchantAccount = "test_merch_n1";
//        $secretKey = "flk3409refn54t54t*FNJRET";

        $params = [
            "transactionType" => "CHARGE",
            "merchantAccount" => $merchantAccount,
            "merchantAuthType" => "SimpleSignature",
            "merchantDomainName" => "m.easy-order-taxi.site",
            "merchantTransactionType" => "AUTH",
//            "merchantTransactionSecureType" => "AUTO",
            "merchantTransactionSecureType" => "NON3DS",
            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "charge"),
            "apiVersion" => 1,
            "orderReference" => $orderReference,
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "recToken" => $recToken,
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1],
            "clientFirstName" => "Bulba",
            "clientLastName" => "Taras",
            "clientEmail" => $clientEmail,
            "clientPhone" => $clientPhone,
            "clientCountry" => "UKR",
            "notifyMethod" => "bot"
        ];

// Відправлення POST-запиту
        $response = Http::post('https://api.wayforpay.com/api ', $params);
        Log::debug("purchase: ", ['response' => $response->body()]);
        return $response;
    }

    private function generateHmacMd5Signature($params, $secretKey, $type)
    {
        // Формуємо рядок, який підлягає підпису

        switch ($type) {
            case "createInvoice":
            case "charge":
                $signatureString = implode(';', [
                    $params['merchantAccount'],
                    $params['merchantDomainName'],
                    $params['orderReference'],
                    $params['orderDate'],
                    $params['amount'],
                    $params['currency'],
                ]);
                foreach ($params['productName'] as $index => $productName) {
                    $signatureString .= ';' . $productName . ';' . $params['productCount'][$index] . ';' . $params['productPrice'][$index];
                }
                break;
            case "serviceUrl":
                $signatureString = implode(';', [
                    $params['orderReference'],
                    $params['status'],
                    $params['time']
                ]);
                break;
            case "verify":
                $signatureString = implode(';', [
                    $params['merchantAccount'],
                    $params['merchantDomainName'],
                    $params['orderReference'],
                    $params['amount'],
                    $params['currency']
                ]);
                break;
            case "checkStatus":
                $signatureString = implode(';', [
                    $params['merchantAccount'],
                    $params['orderReference'],
                ]);
                break;
            case "refund":
            case "settle":
                $signatureString = implode(';', [
                    $params['merchantAccount'],
                    $params['orderReference'],
                    $params['amount'],
                    $params['currency']
                ]);
                break;
            case "purchase":
                $signatureString = implode(';', [
                    $params['merchantAccount'],
                    $params['merchantDomainName'],
                    $params['orderReference'],
                    $params['orderDate'],
                    $params['amount'],
                    $params['currency']
                ]);
                foreach ($params['productName'] as $index => $productName) {
                    $signatureString .= ';' . $productName . ';' . $params['productCount'][$index] . ';' . $params['productPrice'][$index];
                }
                break;
            case "charge":
                $signatureString = implode(';', [
                    $params['merchantAccount'],
                    $params['merchantDomainName'],
                    $params['orderReference'],
                    $params['orderDate'],
                    $params['amount'],
                    $params['currency'],
                    $params['recToken'],
                ]);
                foreach ($params['productName'] as $index => $productName) {
                    $signatureString .= ';' . $productName . ';' . $params['productCount'][$index] . ';' . $params['productPrice'][$index];
                }
                break;
        }


        // Генеруємо HMAC_MD5 контрольний підпис
        return hash_hmac('md5', $signatureString, $secretKey);
    }

    public function checkoutOneMinuteForCancelled(
        $uid,
        $uid_double,
        $authorization
    ) {
// Устанавливаем задержку в 60 секунд
        sleep(60);
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();

        switch ($orderweb->comment) {
            case "taxi_easy_ua_pas1":
                $application = "PAS1";
                $identificationId = config('app.version-PAS1');
                break;
            case "taxi_easy_ua_pas2":
                $application = "PAS2";
                $identificationId = config('app.version-PAS2');
                break;
            default:
                $application = "PAS4";
                $identificationId = config('app.version-PAS4');
        }
        switch ($orderweb->server) {
            case "http://31.43.107.151:7303":
                $city = "OdessaTest";
                break;
            case "http://167.235.113.231:7307":
            case "http://167.235.113.231:7306":
            case "http://134.249.181.173:7208":
            case "http://91.205.17.153:7208":
                $city = "Kyiv City";
                break;
            case "http://142.132.213.111:8071":
            case "http://167.235.113.231:7308":
                $city = "Dnipropetrovsk Oblast";
                break;
            case "http://142.132.213.111:8072":
                $city = "Odessa";
                break;
            case "http://142.132.213.111:8073":
                $city = "Zaporizhzhia";
                break;
            default:
                $city = "Cherkasy Oblast";
        }

        if ($orderweb->wfp_order_id != null) {
            $orderReference = $orderweb->wfp_order_id;
            $response = self::checkStatus(
                $application,
                $city,
                $orderReference
            );
            $data = json_decode($response, true);

            if (isset($data['transactionStatus']) && !empty($data['transactionStatus'])) {
                $transactionStatus = $data['transactionStatus'];
                if ($transactionStatus != "Approved" ||
                    $transactionStatus != "WaitingAuthComplete") {
                    $connectAPI = $orderweb->server;
                    $url = $connectAPI . '/api/weborders/cancel/' . $uid;
                    Http::withHeaders([
                        "Authorization" => $authorization,
                        "X-WO-API-APP-ID" => $identificationId
                    ])->put($url);
                    $url = $connectAPI . '/api/weborders/cancel/' .  $uid_double;
                    Http::withHeaders([
                        "Authorization" => $authorization,
                        "X-WO-API-APP-ID" => $identificationId
                    ])->put($url);
                }
            }
        }
    }
    public function wfpStatusReviewAdmin($wfp_order_id)
    {
        $order = Orderweb::where("wfp_order_id", $wfp_order_id)->first();

//        $connectAPI =  $order->server;
//        $autorization = self::autorization($connectAPI);
//        $identificationId = $order->comment;

//        $order->closeReason = UIDController::closeReasonUIDStatusFirst(
//            $order->dispatching_order_uid,
//            $connectAPI,
//            $autorization,
//            $identificationId
//        )["close_reason"];

//        Log::debug("fondyStatusReviewAdmin order->closeReason:" . strval($order->closeReason));

        switch ($order->comment) {
            case "taxi_easy_ua_pas1":
                $application = "PAS1";
                break;
            case "taxi_easy_ua_pas2":
                $application = "PAS2";
                break;
            default:
                $application = "PAS4";
        }
        switch ($order->server) {
            case "http://31.43.107.151:7303":
                $city = "OdessaTest";
                break;
            case "http://167.235.113.231:7307":
            case "http://167.235.113.231:7306":
            case "http://134.249.181.173:7208":
            case "http://91.205.17.153:7208":
                $city = "Kyiv City";
                break;
            case "http://142.132.213.111:8071":
            case "http://167.235.113.231:7308":
                $city = "Dnipropetrovsk Oblast";
                break;
            case "http://142.132.213.111:8072":
                $city = "Odessa";
                break;
            case "http://142.132.213.111:8073":
                $city = "Zaporizhzhia";
                break;
            default:
                $city = "Cherkasy Oblast";
        }
        $orderReference = $wfp_order_id;
//        $amount = $order->web_cost;

//        switch ($order->closeReason) {
//            case "-1":
//                break;
//            case "0":
//            case "8":
//                self::settle(
//                    $application,
//                    $city,
//                    $orderReference,
//                    $amount
//                );
//                break;
//            case "1":
//            case "2":
//            case "3":
//            case "4":
//            case "5":
//            case "6":
//            case "7":
//            case "9":
//                self::refund(
//                    $application,
//                    $city,
//                    $orderReference,
//                    $amount
//                );
//                break;
//        }
        self::checkStatus(
            $application,
            $city,
            $orderReference
        );
//        $data = json_decode($response, true);
//
//        if (isset($data['transactionStatus']) && !empty($data['transactionStatus'])) {
//            $order->wfp_status_pay = $data['transactionStatus'];
//        }
//
//        $order->save();
    }
    public function wfpStatus($bonusOrder, $doubleOrder, $bonusOrderHold)
    {
        $result = 0;
        $order = Orderweb::where("dispatching_order_uid", $bonusOrderHold)->first();
        $wfp_order_id = $order->wfp_order_id;
        $connectAPI = $order->server;
        $autorization = self::autorization($connectAPI);
        $identificationId = $order->comment;
        $amount = $order->web_cost;
        $amount_settle = $amount;

        $bonusOrder_response = (new UIDController)->closeReasonUIDStatusFirstWfp(
            $bonusOrder,
            $connectAPI,
            $autorization,
            $identificationId
        );
        if ($bonusOrder_response != -1) {
            $closeReason_bonusOrder = $bonusOrder_response["close_reason"];
            $order_cost_bonusOrder = $bonusOrder_response["order_cost"];
            Log::debug("closeReason_bonusOrder: $closeReason_bonusOrder");
            Log::debug("order_cost_bonusOrder: $order_cost_bonusOrder");
        } else {
            $closeReason_bonusOrder = -1;
            $order_cost_bonusOrder = $amount;
            self::messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $bonusOrder);
        }
        $doubleOrder_response = (new UIDController)->closeReasonUIDStatusFirstWfp(
            $doubleOrder,
            $connectAPI,
            $autorization,
            $identificationId
        );
        if ($doubleOrder_response != -1) {
            $closeReason_doubleOrder = $doubleOrder_response["close_reason"];
            $order_cost_doubleOrder = $doubleOrder_response["order_cost"];
            Log::debug("closeReason_doubleOrder: $closeReason_doubleOrder");
            Log::debug("order_cost_doubleOrder : $order_cost_doubleOrder");
        } else {
            $closeReason_doubleOrder = -1;
            $order_cost_doubleOrder = $amount;
            self::messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $doubleOrder);
        }


        $bonusOrderHold_response = (new UIDController)->closeReasonUIDStatusFirstWfp(
            $bonusOrderHold,
            $connectAPI,
            $autorization,
            $identificationId
        );
        if ($bonusOrderHold_response != -1) {
            $closeReason_bonusOrderHold = $bonusOrderHold_response["close_reason"];
            $order_cost_bonusOrderHold = $bonusOrderHold_response["order_cost"];
            Log::debug("closeReason_bonusOrderHold: $closeReason_bonusOrderHold");
            Log::debug("order_cost_bonusOrderHold : $order_cost_bonusOrderHold");
        } else {
            $closeReason_bonusOrderHold = -1;
            $order_cost_bonusOrderHold = $amount;
            self::messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $bonusOrderHold);
        }
        switch ($order->comment) {
            case "taxi_easy_ua_pas1":
                $application = "PAS1";
                break;
            case "taxi_easy_ua_pas2":
                $application = "PAS2";
                break;
            default:
                $application = "PAS4";
        }
        switch ($order->server) {
            case "http://31.43.107.151:7303":
                $city = "OdessaTest";
                break;
            case "http://167.235.113.231:7307":
            case "http://167.235.113.231:7306":
            case "http://134.249.181.173:7208":
            case "http://91.205.17.153:7208":
                $city = "Kyiv City";
                break;
            case "http://142.132.213.111:8071":
            case "http://167.235.113.231:7308":
                $city = "Dnipropetrovsk Oblast";
                break;
            case "http://142.132.213.111:8072":
                $city = "Odessa";
                break;
            case "http://142.132.213.111:8073":
                $city = "Zaporizhzhia";
                break;
            default:
                $city = "Cherkasy Oblast";
        }
        $orderReference = $wfp_order_id;


        $hold_bonusOrder = false;
        switch ($closeReason_bonusOrder) {
            case "0":
            case "8":
                $hold_bonusOrder = true;
                $amount_settle = $order_cost_bonusOrder;
                $result = 1;
                break;
        }
        $hold_doubleOrder = false;
        switch ($closeReason_doubleOrder) {
            case "0":
            case "8":
                $hold_doubleOrder = true;
                $amount_settle = $order_cost_doubleOrder;
                $result = 1;
                break;
        }
        $hold_bonusOrderHold = false;
        switch ($closeReason_bonusOrderHold) {
            case "0":
            case "8":
                $hold_bonusOrderHold = true;
                $amount_settle = $order_cost_bonusOrderHold;
                $result = 1;
                break;
        }
        if ($amount >= $amount_settle) {
            $amount = $amount_settle;
            $order->web_cost = $amount;
            $order->save();
        } else {
            $subject = "Оплата поездки больше холда";
            $localCreatedAt = Carbon::parse($order->created_at)->setTimezone('Europe/Kiev');
            $messageAdmin = "Заказ $bonusOrderHold. Сервер $connectAPI. Время $localCreatedAt.
                 Маршрут $order->routefrom - $order->routeto.
                 Телефон клиента:  $order->user_phone.
                 Сумма холда $amount грн. Сумма заказа $amount_settle грн.";
            $paramsAdmin = [
                'subject' => $subject,
                'message' => $messageAdmin,
            ];
            $alarmMessage = new TelegramController();

            try {
                $alarmMessage->sendAlarmMessage($messageAdmin);
                $alarmMessage->sendMeMessage($messageAdmin);
            } catch (Exception $e) {
                $subject = 'Ошибка в телеграмм';
                $paramsCheck = [
                    'subject' => $subject,
                    'message' => $e,
                ];

                Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
            };

            Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
            Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
        }

        if ($hold_bonusOrder || $hold_doubleOrder || $hold_bonusOrderHold) {
            self::settle(
                $application,
                $city,
                $orderReference,
                $amount
            );
            if ($hold_bonusOrder) {
                $order->closeReason = $closeReason_bonusOrder;
            }
            if ($hold_doubleOrder) {
                $order->closeReason = $closeReason_doubleOrder;
            }
            if ($hold_bonusOrderHold) {
                $order->closeReason = $closeReason_bonusOrderHold;
            }
        } else {
            if ($closeReason_bonusOrder != "-1"
                || $closeReason_doubleOrder != "-1"
                || $closeReason_bonusOrderHold != "-1") {
                self::refund(
                    $application,
                    $city,
                    $orderReference,
                    $amount
                );
                $order->closeReason = $closeReason_bonusOrderHold;
            }
        }


        self::checkStatus(
            $application,
            $city,
            $orderReference
        );
//        $data = json_decode($response, true);
//
//        if (isset($data['transactionStatus']) && !empty($data['transactionStatus'])) {
//            $order->wfp_status_pay = $data['transactionStatus'];
//        }
//


        $order->save();

        return $result;
    }

    private function autorization($connectApi)
    {

        $city = City::where('address', str_replace('http://', '', $connectApi))->first();

        $username = $city->login;
        $password = hash('SHA512', $city->password);

        return 'Basic ' . base64_encode($username . ':' . $password);
    }

    public function wfpStatusShowAdmin(): array
    {
        $order = Orderweb::where("closeReason", "!=", null)
            ->where("wfp_order_id", "!=", null)
            ->orderByDesc('created_at')
            ->get();
        $response = null;
//        dd($order->toArray());
        if (!$order->isEmpty()) {
            $i=0;

            foreach ($order->toArray() as $value) {
                if ($value["wfp_status_pay"] == null) {
                    $orderF = Orderweb::where("wfp_order_id", $value["wfp_order_id"])->first();
                    $orderF->wfp_status_pay = self::wfpOrderIdStatus($orderF->wfp_order_id);
                    $orderF->save();
                    $wfp_status_pay = $orderF->wfp_status_pay;
                } else {
                    $wfp_status_pay = $value["wfp_status_pay"];
                }

                switch ($value["closeReason"]) {
                    case "-1":
                        $closeReasonText = "(-1) В обработке";
                        break;
                    case "0":
                        $closeReasonText = "(0) Выполнен";
                        break;
                    case "1":
                        $closeReasonText = "(1) Снят клиентом";
                        break;
                    case "2":
                        $closeReasonText = "(2) Не выполнено";
                        break;
                    case "3":
                        $closeReasonText = "(3) Не выполнено";
                        break;
                    case "4":
                        $closeReasonText = "(4) Не выполнено";
                        break;
                    case "5":
                        $closeReasonText = "(5) Не выполнено";
                        break;
                    case "6":
                        $closeReasonText = "(6) Снят клиентом";
                        break;
                    case "7":
                        $closeReasonText = "(7) Снят клиентом";
                        break;
                    case "8":
                        $closeReasonText = "(8) Выполнен";
                        break;
                    case "9":
                        $closeReasonText = "(9) Снят клиентом";
                        break;
                    default:
                        $closeReasonText = "не известное значение";
                        break;

                }


                date_default_timezone_set('Europe/Kiev');


                $date = new DateTime($value["created_at"]);
                $date->add(new DateInterval('PT3H'));

                $formatted_date = $date->format('d.m.Y H:i:s');

                $response[$i] = [
                    'id' => $value["id"],
                    'first' =>$formatted_date,
                    'cost' => $value["web_cost"],
                    'wfp_order_id' => $value["wfp_order_id"],
                    'wfp_status_pay' => $wfp_status_pay,
                    'uid' => $value["dispatching_order_uid"],
                    'reason' => $closeReasonText,
                ];
                $i++;
            }
        }
//        dd($response);
        return $response;
    }
    public function wfpOrderIdStatus($wfp_order_id)
    {
        Log::debug('wfpOrderIdStatus wfp_order_id' . $wfp_order_id);
        $orderweb = Orderweb::where("wfp_order_id", $wfp_order_id)->first();

        switch ($orderweb->comment) {
            case "taxi_easy_ua_pas1":
                $application = "PAS1";
                break;
            case "taxi_easy_ua_pas2":
                $application = "PAS2";
                break;
            default:
                $application = "PAS4";
        }
        switch ($orderweb->server) {
            case "http://31.43.107.151:7303":
                $city = "OdessaTest";
                break;
            case "http://167.235.113.231:7307":
            case "http://167.235.113.231:7306":
            case "http://134.249.181.173:7208":
            case "http://91.205.17.153:7208":
                $city = "Kyiv City";
                break;
            case "http://142.132.213.111:8071":
            case "http://167.235.113.231:7308":
                $city = "Dnipropetrovsk Oblast";
                break;
            case "http://142.132.213.111:8072":
                $city = "Odessa";
                break;
            case "http://142.132.213.111:8073":
                $city = "Zaporizhzhia";
                break;
            default:
                $city = "Cherkasy Oblast";
        }
        $orderReference = $wfp_order_id;

        $response = (new WfpController)->checkStatus(
            $application,
            $city,
            $orderReference
        );
        $data = json_decode($response, true);
        if (isset($data['transactionStatus']) && !empty($data['transactionStatus'])) {
            return $data['transactionStatus'];
        } else {
            return 'Unknown'; // Можно заменить на другое значение или бросить исключение
        }
    }
}

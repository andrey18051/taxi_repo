<?php

namespace App\Http\Controllers;

use App\Jobs\CheckStatusJob;
use App\Jobs\RefundSettleCardPayJob;
use App\Mail\Check;
use App\Mail\Server;
use App\Models\Card;
use App\Models\City;
use App\Models\City_PAS1;
use App\Models\City_PAS2;
use App\Models\City_PAS4;
use App\Models\Orderweb;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WfpInvoice;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Diff\Exception;

class WfpController extends Controller
{
    private static function orderInfo($orderReference): string
    {
        $orderwebs = WfpInvoice::where("orderReference", $orderReference)->first();
        if ($orderwebs) {
            $params = $orderwebs->toArray();

            $user_phone  = $params["user_phone"];//Телефон пользователя

            $email = $params['email'];//Телефон пользователя
            if ($params["route_undefined"] != 0) {
                $order = "Замовлення від " . $params['user_full_name'] . " (телефон $user_phone, email $email) " .
                    " за маршрутом від " . $params['routefrom'] . " " . $params['routefromnumber'] .
                    " до "  . $params['routeto'] . " " . $params['routetonumber'] .
                    ". Вартість поїздки становитиме: " . $params['web_cost'] . "грн. Номер замовлення: " .
                    $params['dispatching_order_uid'] .
                    ", сервер " . $params['server'];
                ;
            } else {
                $order = "Замовлення від " . $params['user_full_name'] . " (телефон $user_phone, email $email) " .
                    " за маршрутом від " . $params['routefrom'] . " " . $params['routefromnumber'] .
                    " по місту. Вартість поїздки становитиме: " . $params['order_cost'] . "грн. Номер замовлення: " .
                    ". Вартість поїздки становитиме: " . $params['web_cost'] . "грн. Номер замовлення: " .
                    $params['dispatching_order_uid'] .
                    ", сервер " . $params['server'];
            }
            return $order;
        } else {
            return "";
        }
    }

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

            try {
                Mail::to('taxi.easy.ua.sup@gmail.com')->send(new Check($paramsCheck));
            } catch (\Exception $e) {
                Log::error('Mail send failed: ' . $e->getMessage());
                // Дополнительные действия для предотвращения сбоя
            }

        };

        try {
            Mail::to('taxi.easy.ua.sup@gmail.com')->send(new Check($paramsAdmin));
            Mail::to('cartaxi4@gmail.com')->send(new Check($paramsAdmin));
        } catch (\Exception $e) {
            Log::error('Mail send failed: ' . $e->getMessage());
            // Дополнительные действия для предотвращения сбоя
        }

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

            $rectoken = (new CardsController)->encryptToken($data['recToken']);

            $card->pay_system = 'wfp';
            $card->masked_card = $data['cardPan'];
            $card->card_type = $cardType;
            $card->bank_name = $bankName;
            $card->rectoken = $rectoken;
            $card->merchant = $data['merchantAccount'];
//                $card->rectoken_lifetime = $data['rectoken_lifetime'];
            $card->save();
            (new CardsController)->setActiveFirstCard($data['email'], $card->id);
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
    public function serviceUrl_PAS1(Request $request)
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

            $rectoken = (new CardsController)->encryptToken($data['recToken']);
            $card = Card::where('pay_system', 'wfp')
                ->where('user_id', $user->id)
                ->where('merchant', $data['merchantAccount'])
                ->where('masked_card', $data['cardPan'])
                ->first();

            if (!$card) {
                $card = new Card();
                $card->user_id = $user->id;
                $card->pay_system = 'wfp';
                $card->masked_card = $data['cardPan'];
                $card->card_type = $cardType;
                $card->bank_name = $bankName;
                $card->rectoken =  $rectoken;
                $card->merchant = $data['merchantAccount'];
//                $card->rectoken_lifetime = $data['rectoken_lifetime'];
                $card->save();
                (new CardsController)->setActiveFirstCard($data['email'], $card->id);
            }

        }

        $time = strtotime(date('Y-m-d H:i:s'));

        $params = [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time
        ];
        $city = "OdessaTest";
        $merchant = City_PAS1::where("name", $city)->first();
        $secretKey = $merchant->wfp_merchantSecretKey;
//        $secretKey = "7aca3657f12fca79d876dcb50e2d84d71f544516";

        $signature = self::generateHmacMd5Signature($params, $secretKey, "serviceUrl");

        return [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time,
            "signature" =>  $signature
        ];
    }

    public function serviceUrl_PAS1_app(Request $request)
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

            $rectoken = (new CardsController)->encryptToken($data['recToken']);
            $card = Card::where('pay_system', 'wfp')
                ->where('user_id', $user->id)
                ->where('merchant', $data['merchantAccount'])
                ->where('app', "PAS1")
                ->where('masked_card', $data['cardPan'])
                ->first();

            if (!$card) {
                $card = new Card();
                $card->user_id = $user->id;
                $card->pay_system = 'wfp';
                $card->app = 'PAS1';
                $card->masked_card = $data['cardPan'];
                $card->card_type = $cardType;
                $card->bank_name = $bankName;
                $card->rectoken =  $rectoken;
                $card->merchant = $data['merchantAccount'];
//                $card->rectoken_lifetime = $data['rectoken_lifetime'];
                $card->save();
                (new CardsController)->setActiveFirstCardApp($data['email'], $card->id, 'PAS1');
            }

        }

        $time = strtotime(date('Y-m-d H:i:s'));

        $params = [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time
        ];
        $city = "OdessaTest";
        $merchant = City_PAS1::where("name", $city)->first();
        $secretKey = $merchant->wfp_merchantSecretKey;
//        $secretKey = "7aca3657f12fca79d876dcb50e2d84d71f544516";

        $signature = self::generateHmacMd5Signature($params, $secretKey, "serviceUrl");

        return [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time,
            "signature" =>  $signature
        ];
    }
    public function serviceUrl_PAS2(Request $request)
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

            $rectoken = (new CardsController)->encryptToken($data['recToken']);
            $card = Card::where('pay_system', 'wfp')
                ->where('user_id', $user->id)
                ->where('merchant', $data['merchantAccount'])
                ->where('masked_card', $data['cardPan'])
                ->first();

            if (!$card) {
                $card = new Card();
                $card->user_id = $user->id;
                $card->pay_system = 'wfp';
                $card->masked_card = $data['cardPan'];
                $card->card_type = $cardType;
                $card->bank_name = $bankName;
                $card->rectoken =  $rectoken;
                $card->merchant = $data['merchantAccount'];
//                $card->rectoken_lifetime = $data['rectoken_lifetime'];
                $card->save();
                (new CardsController)->setActiveFirstCard($data['email'], $card->id);
            }



        }

        $time = strtotime(date('Y-m-d H:i:s'));

        $params = [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time
        ];
        $city = "OdessaTest";
        $merchant = City_PAS2::where("name", $city)->first();
        $secretKey = $merchant->wfp_merchantSecretKey;
//        $secretKey = "7aca3657f12fca79d876dcb50e2d84d71f544516";

        $signature = self::generateHmacMd5Signature($params, $secretKey, "serviceUrl");

        return [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time,
            "signature" =>  $signature
        ];
    }

    public function serviceUrl_PAS2_app(Request $request)
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

            $rectoken = (new CardsController)->encryptToken($data['recToken']);
            $card = Card::where('pay_system', 'wfp')
                ->where('user_id', $user->id)
                ->where('merchant', $data['merchantAccount'])
                ->where('app', "PAS2")
                ->where('masked_card', $data['cardPan'])
                ->first();

            if (!$card) {
                $card = new Card();
                $card->user_id = $user->id;
                $card->pay_system = 'wfp';
                $card->app = 'PAS2';
                $card->masked_card = $data['cardPan'];
                $card->card_type = $cardType;
                $card->bank_name = $bankName;
                $card->rectoken =  $rectoken;
                $card->merchant = $data['merchantAccount'];
//                $card->rectoken_lifetime = $data['rectoken_lifetime'];
                $card->save();
                (new CardsController)->setActiveFirstCardApp($data['email'], $card->id, 'PAS2');
            }



        }

        $time = strtotime(date('Y-m-d H:i:s'));

        $params = [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time
        ];
        $city = "OdessaTest";
        $merchant = City_PAS2::where("name", $city)->first();
        $secretKey = $merchant->wfp_merchantSecretKey;
//        $secretKey = "7aca3657f12fca79d876dcb50e2d84d71f544516";

        $signature = self::generateHmacMd5Signature($params, $secretKey, "serviceUrl");

        return [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time,
            "signature" =>  $signature
        ];
    }
    public function serviceUrl_PAS4(Request $request)
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
            $rectoken = (new CardsController)->encryptToken($data['recToken']);
            $card = Card::where('pay_system', 'wfp')
                ->where('user_id', $user->id)
                ->where('merchant', $data['merchantAccount'])
                ->where('masked_card', $data['cardPan'])
                ->first();

            if (!$card) {
                $card = new Card();
                $card->user_id = $user->id;
                $card->pay_system = 'wfp';
                $card->masked_card = $data['cardPan'];
                $card->card_type = $cardType;
                $card->bank_name = $bankName;
                $card->rectoken =  $rectoken;
                $card->merchant = $data['merchantAccount'];
//                $card->rectoken_lifetime = $data['rectoken_lifetime'];
                $card->save();
                (new CardsController)->setActiveFirstCard($data['email'], $card->id);
            }
        }

        $time = strtotime(date('Y-m-d H:i:s'));

        $params = [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time
        ];
        $city = "OdessaTest";
        $merchant = City_PAS4::where("name", $city)->first();
        $secretKey = $merchant->wfp_merchantSecretKey;

        $signature = self::generateHmacMd5Signature($params, $secretKey, "serviceUrl");

        return [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time,
            "signature" =>  $signature
        ];
    }
    public function serviceUrl_PAS4_app(Request $request)
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
            $rectoken = (new CardsController)->encryptToken($data['recToken']);
            $card = Card::where('pay_system', 'wfp')
                ->where('user_id', $user->id)
                ->where('merchant', $data['merchantAccount'])
                ->where('app', "PAS4")
                ->where('masked_card', $data['cardPan'])
                ->first();

            if (!$card) {
                $card = new Card();
                $card->user_id = $user->id;
                $card->pay_system = 'wfp';
                $card->app = 'PAS4';
                $card->masked_card = $data['cardPan'];
                $card->card_type = $cardType;
                $card->bank_name = $bankName;
                $card->rectoken =  $rectoken;
                $card->merchant = $data['merchantAccount'];
//                $card->rectoken_lifetime = $data['rectoken_lifetime'];
                $card->save();
                (new CardsController)->setActiveFirstCardApp($data['email'], $card->id, 'PAS4');
            }
        }

        $time = strtotime(date('Y-m-d H:i:s'));

        $params = [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time
        ];
        $city = "OdessaTest";
        $merchant = City_PAS4::where("name", $city)->first();
        $secretKey = $merchant->wfp_merchantSecretKey;

        $signature = self::generateHmacMd5Signature($params, $secretKey, "serviceUrl");

        return [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time,
            "signature" =>  $signature
        ];
    }
    /**
     * @throws \Exception
     */
    public function serviceUrl_VOD(Request $request)
    {
        Log::debug("serviceUrl " . $request);

        $data = json_decode($request->getContent(), true);
        Log::debug($data['email']);
        Log::debug($data['recToken']);

        $userData = (new FCMController)->findUserByEmail($data['email']);
        if (is_array($userData)  && isset($data['recToken']) && $data['recToken'] != null) {
            $uidDriver = $userData["uid"];

            $status = "payment_card";
            $amount = $data["amount"];


            $cardType = $data['cardType'];
            if (isset($data['issuerBankName']) && $data['issuerBankName'] != null) {
                $bankName = $data['issuerBankName'];
            } else {
                $bankName = " ";
            }
            $user = User::where('email', $data['email'])->first();

            $rectoken = (new CardsController)->encryptToken($data['recToken']);
            $card = Card::where('pay_system', 'wfp')
                ->where('user_id', $user->id)
                ->where('masked_card', $data['cardPan'])
                ->first();
            if (!$card) {
                $cardData = [
                    'cardType' => $cardType,
                    'bankName' => $bankName,
                    'maskedCard' => $data['cardPan'],
                    'recToken' => $rectoken,
                    'merchant' => $data['merchantAccount'],
                    'pay_system' => 'wfp'
                ];
            }

// Сохраняем данные в Firestore
            (new FCMController)->saveCardDataToFirestore($uidDriver, $cardData, $status, $amount);
        }


        $currentUtcTime = new DateTime('now', new DateTimeZone('UTC'));
        $currentUtcTime->setTimezone(new DateTimeZone('Europe/Kiev'));
        $formattedDateTime = $currentUtcTime->format('Y-m-d H:i:s');
        $time = strtotime($formattedDateTime);

        $params = [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time
        ];

        $secretKey = config("app.merchantSecretKey");

        $signature = self::generateHmacMd5Signature($params, $secretKey, "serviceUrl");

        return [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time,
            "signature" =>  $signature
        ];
    }

    public function serviceUrl_VOD_app(Request $request)
    {
        Log::debug("serviceUrl " . $request);

        $data = json_decode($request->getContent(), true);
        Log::debug($data['email']);
        Log::debug($data['recToken']);

        $userData = (new FCMController)->findUserByEmail($data['email']);
        if (is_array($userData)  && isset($data['recToken']) && $data['recToken'] != null) {
            $uidDriver = $userData["uid"];

            $status = "payment_card";
            $amount = $data["amount"];


            $cardType = $data['cardType'];
            if (isset($data['issuerBankName']) && $data['issuerBankName'] != null) {
                $bankName = $data['issuerBankName'];
            } else {
                $bankName = " ";
            }
            $user = User::where('email', $data['email'])->first();

            $rectoken = (new CardsController)->encryptToken($data['recToken']);
            $card = Card::where('pay_system', 'wfp')
                ->where('user_id', $user->id)
                ->where('merchant', $data['merchantAccount'])
                ->where('app', "VOD")
                ->where('masked_card', $data['cardPan'])
                ->first();
            if (!$card) {
                $cardData = [
                    'cardType' => $cardType,
                    'bankName' => $bankName,
                    'app' => 'VOD',
                    'maskedCard' => $data['cardPan'],
                    'recToken' => $rectoken,
                    'merchant' => $data['merchantAccount'],
                    'pay_system' => 'wfp'
                ];
                // Сохраняем данные в Firestore
                (new FCMController)->saveCardDataToFirestore($uidDriver, $cardData, $status, $amount);
            }


        }


        $currentUtcTime = new DateTime('now', new DateTimeZone('UTC'));
        $currentUtcTime->setTimezone(new DateTimeZone('Europe/Kiev'));
        $formattedDateTime = $currentUtcTime->format('Y-m-d H:i:s');
        $time = strtotime($formattedDateTime);

        $params = [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time
        ];

        $secretKey = config("app.merchantSecretKey");

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
    )
    {
        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                break;
            case "foreign countries":
                $city = "Kyiv City";
                break;
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
        }

        if ($city != "OdessaTest") {
            $serviceUrl = "https://m.easy-order-taxi.site/wfp/serviceUrl/$application";
        } else {
            $serviceUrl = "https://t.easy-order-taxi.site/wfp/serviceUrl/$application";
        }

        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = $merchant->wfp_merchantSecretKey;

            $orderDate = strtotime(date('Y-m-d H:i:s'));

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


            $params = [
                "transactionType" => "CREATE_INVOICE",
                "merchantAccount" => $merchantAccount,
                "merchantAuthType" => "SimpleSignature",
                "merchantDomainName" => "m.easy-order-taxi.site",
                "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "createInvoice"),
                "apiVersion" => 1,
                "language" => $language,
//            "returnUrl" => "https://m.easy-order-taxi.site/wfp/returnUrl",
                "serviceUrl" => $serviceUrl,
                "orderReference" => $orderReference,
                "orderDate" => $orderDate,
                "amount" => $amount,
                "currency" => "UAH",
                "orderTimeout" => 86400,
                "merchantTransactionType" => "AUTH",
                "productName" => [$productName],
                "productPrice" => [$amount],
                "productCount" => [1],
//            "paymentSystems" => "card;privat24;googlePay;applePay",
                "paymentSystems" => "card;privat24;googlePay;",
//            "paymentSystems" => "card;privat24;",
                "clientEmail" => $clientEmail,
                "clientPhone" => $clientPhone,
                "notifyMethod" => "bot"
            ];

// Відправлення POST-запиту
            $response = Http::post('https://api.wayforpay.com/api', $params);
            Log::debug("CREATE_INVOICE", ['response' => $response->body()]);
            return $response;
        }
    }


    public function verify(
        $application,
        $city,
        $orderReference,
        $clientEmail,
        $clientPhone,
        $language
    )
    {
        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                break;
            case "foreign countries":
                $city = "Kyiv City";
                break;
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
        }

        if ($city != "OdessaTest") {
            $serviceUrl = "https://m.easy-order-taxi.site/wfp/serviceUrl/$application";
        } else {
            $serviceUrl = "https://t.easy-order-taxi.site/wfp/serviceUrl/$application";
        }

        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = $merchant->wfp_merchantSecretKey;

            $params = [
                "merchantAccount" => $merchantAccount,
                "merchantDomainName" => "m.easy-order-taxi.site",
                "orderReference" => $orderReference,
                "amount" => "0",
                "currency" => "UAH",
            ];


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
                "serviceUrl" => $serviceUrl,
                "language" => $language,
                "paymentSystems" => "lookupCard",
                "verifyType" => "confirm",
            ];

// Відправлення POST-запиту
            $response = Http::post('https://secure.wayforpay.com/verify?behavior=offline', $params);
//        $response = Http::post('https://secure.wayforpay.com/verify?behavior=online', $params);

            Log::debug("verify response sent: ");
            return $response;
        }
    }

    public function checkStatus(
        $application,
        $city,
        $orderReference
    ) {
        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                break;
            case "foreign countries":
                $city = "Kyiv City";
                break;
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
        }

        if ($city != "OdessaTest") {
            $serviceUrl =  "https://m.easy-order-taxi.site/wfp/serviceUrl/$application";
        } else {
            $serviceUrl =  "https://t.easy-order-taxi.site/wfp/serviceUrl/$application";
        }

        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = $merchant->wfp_merchantSecretKey;

            if (isset($merchantAccount) && isset($secretKey)) {
                $params = [
                    "merchantAccount" => $merchantAccount,
                    "orderReference" => $orderReference,
                ];
                $params = [
                    "transactionType" => "CHECK_STATUS",
                    "merchantAccount" => $merchantAccount,
                    "orderReference" => $orderReference,
                    "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "checkStatus"),
                    "apiVersion" => 1,
                ];

// Відправлення POST-запиту
                $response = Http::post('https://api.wayforpay.com/api', $params);


                $messageAdmin = "checkStatus " . 'response' . $response->body();
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);

                if (isset($response)) {
                    $data = json_decode($response->body(), true);
                    $invoice = WfpInvoice::where("orderReference", $orderReference)->first();
                    if ($data['transactionStatus'] != "WaitingAuthComplete") {
                        dispatch(new CheckStatusJob($application, $city, $orderReference))
                            ->onQueue('medium');

                    }
                    if ($invoice) {
                        $invoice->transactionStatus = $data['transactionStatus'];
                        $invoice->reason = $data['reason'];
                        $invoice->reasonCode = $data['reasonCode'];
                        $invoice->save();

                    } else {
                        $order = Orderweb::where("wfp_order_id", $orderReference)->first();
                        if ($order) {
                            $order->wfp_status_pay = $data['transactionStatus'];
                            $order->save();
                        }

                    }

                } else {
                    Log::error("No response received from WayforPay API");
                }

            } else {
                $messageAdmin = "Нет данных мерчанта для города $city, приложение $application (checkStatus)";
                (new MessageSentController)->sentMessageAdmin($messageAdmin);
                $response = "error";

            }
            return $response;
        }
    }

    public function checkStatusJob($application, $city, $orderReference)
    {
        $messageAdmin = "Запущен checkStatusJob $orderReference";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                break;
            case "foreign countries":
                $city = "Kyiv City";
                break;
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
        }


        if (!$merchant) {
            $messageAdmin = "Нет данных мерчанта для города $city, приложение $application (checkStatus)";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);
            return "error";
        }

        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = $merchant->wfp_merchantSecretKey;

            $params = [
                "merchantAccount" => $merchantAccount,
                "orderReference" => $orderReference,
            ];

            $params["transactionType"] = "CHECK_STATUS";
            $params["merchantSignature"] = self::generateHmacMd5Signature($params, $secretKey, "checkStatus");
            $params["apiVersion"] = 1;

            if ($params['amount'] == "1") {
                $maxAttempts = 18; // 3 минуты / 10 секунд = 18 попыток
            } else {
                $maxAttempts = 6; // 1 минуты / 10 секунд = 6 попыток
            }

            $attempt = 0;

            do {
                $response = Http::post('https://api.wayforpay.com/api', $params);
                Log::debug((string)["checkStatus attempt $attempt response" => $response->body()]);

                $data = json_decode($response->body(), true);

                if (!$data || !isset($data['transactionStatus'])) {
                    Log::error("Ошибка получения ответа от WayforPay API на попытке $attempt");
                } else {
                    $transactionStatus = $data['transactionStatus'];
                    if ($transactionStatus === "WaitingAuthComplete") {
                        // Обновляем данные в базе
                        $invoice = WfpInvoice::where("orderReference", $orderReference)->first();
                        if ($invoice) {
                            $invoice->transactionStatus = $transactionStatus;
                            $invoice->reason = $data['reason'] ?? null;
                            $invoice->reasonCode = $data['reasonCode'] ?? null;
                            $invoice->save();
                        } else {
                            if ($data['amount'] == "1") {
                                $params = [
                                    "transactionType" => "REFUND",
                                    "merchantAccount" => $merchantAccount,
                                    "orderReference" => $orderReference,
                                    "amount" => "1",
                                    "currency" => "UAH",
                                    "comment" => "Повернення платежу",
                                    "merchantSignature" => self::generateHmacMd5Signature([
                                        "transactionType" => "REFUND",
                                        "merchantAccount" => $merchantAccount,
                                        "orderReference" => $orderReference,
                                        "amount" => "1",
                                        "currency" => "UAH",
                                    ], $secretKey, "refund"),
                                    "apiVersion" => 1,
                                ];
                                self:: refundSettleOneUAH($params);
                            }

                        }

                        // Если получили нужный статус — выходим из цикла

                        return $response;
                    }
                }

                // Ждём 10 секунд перед следующей попыткой
                sleep(10);
                $attempt++;
            } while ($attempt < $maxAttempts);

            Log::error("Тайм-аут: статус WaitingAuthComplete не получен за 3 минуты");
            return "timeout";
        }
    }


    public function checkMerchantInfo($order) {

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
            case "http://134.249.181.173:7201":
            case "http://91.205.17.153:7201":
                $city = "Cherkasy Oblast";
                break;
            default:
                $city = "OdessaTest";
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                 break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
        };
        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = $merchant->wfp_merchantSecretKey;
            if(isset($merchantAccount) && isset($secretKey)) {
                $response = [
                    "merchantAccount" => $merchantAccount,
                    "secretKey" => $secretKey
                ];
            } else {
                $messageAdmin = "Нет данных мерчанта для города $city, приложение $application (checkMerchantInfo)";
                (new MessageSentController)->sentMessageAdmin($messageAdmin);
                $response = [
                    "merchantAccount" => "errorMerchantAccount",
                    "secretKey" => "errorMerchantSecretKey"
                ];
            }
            return $response;
        }
    }

    public function refund(
        $application,
        $city,
        $orderReference,
        $amount
    ) {
        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                break;
            case "foreign countries":
                $city = "Kyiv City";
                break;
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
        }


        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = $merchant->wfp_merchantSecretKey;
            $orderwebs = Orderweb::where("wfp_order_id", $orderReference) ->latest()
                ->first();
            $wfpInvoices = WfpInvoice::where("dispatching_order_uid", $orderwebs->dispatching_order_uid)->get();
            if ($wfpInvoices->isNotEmpty()) {
                foreach ($wfpInvoices as $value) {
                    // Проверка статуса текущей транзакции
//                    $transactionStatus = strtolower(trim($value->transactionStatus ?? ''));
                    $transactionStatus = $value->transactionStatus;
                    if ($transactionStatus == 'WaitingAuthComplete') {
//                    if (!in_array($transactionStatus, ['refunded', 'voided', 'approved'])) {
                        // Параметры для REFUND
                        $params = [
                            "transactionType" => "REFUND",
                            "merchantAccount" => $merchantAccount,
                            "orderReference" => $value->orderReference,
                            "amount" => $value->amount,
                            "currency" => "UAH",
                            "comment" => "Повернення платежу",
                            "merchantSignature" => self::generateHmacMd5Signature([
                                "transactionType" => "REFUND",
                                "merchantAccount" => $merchantAccount,
                                "orderReference" => $value->orderReference,
                                "amount" => $value->amount,
                                "currency" => "UAH",
                            ], $secretKey, "refund"),
                            "apiVersion" => 1,
                        ];

                        // Диспетчеризация задачи RefundSettleCardPayJob
                        dispatch(new RefundSettleCardPayJob($params, $value->orderReference, "refund"))
                            ->onQueue('medium');

                    }
                }
            }

        }
    }



    public function refundVerifyCards(
        $application,
        $city,
        $orderReference,
        $amount
    ): JsonResponse {
        // Log input parameters
        Log::info('Starting refundVerifyCards', [
            'application' => $application,
            'city' => $city,
            'orderReference' => $orderReference,
            'amount' => $amount
        ]);

        // City mapping
        $originalCity = $city; // Store original city for logging
        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                Log::debug('City mapped', [
                    'original_city' => $originalCity,
                    'mapped_city' => $city
                ]);
                break;
            case "foreign countries":
                $city = "Kyiv City";
                Log::debug('City mapped', [
                    'original_city' => $originalCity,
                    'mapped_city' => $city
                ]);
                break;
            default:
                Log::debug('City not mapped, using original', ['city' => $city]);
                break;
        }

        // Merchant selection
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                Log::info('Merchant query for PAS1', [
                    'city' => $city,
                    'merchant_found' => $merchant !== null
                ]);
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                Log::info('Merchant query for PAS2', [
                    'city' => $city,
                    'merchant_found' => $merchant !== null
                ]);
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
                Log::info('Merchant query for PAS4 (default)', [
                    'city' => $city,
                    'merchant_found' => $merchant !== null
                ]);
                break;
        }

        // Check if merchant exists
        if ($merchant === null) {
            Log::warning('No merchant found for city and application', [
                'city' => $city,
                'application' => $application,
                'orderReference' => $orderReference
            ]);
            return response()->json([
                'orderReference' => $orderReference,
                'reasonCode' => 1001,
                'reason' => 'No merchant found for the specified city and application',
                'transactionStatus' => 'FAILED',
                'merchantAccount' => null
            ], 404);
        }

        $merchantAccount = $merchant->wfp_merchantAccount;
        $secretKey = $merchant->wfp_merchantSecretKey;

        // Log merchant details
        Log::info('Merchant details retrieved', [
            'merchantAccount' => $merchantAccount,
            'secretKey' => !empty($secretKey) ? 'set' : 'not set'
        ]);

        // Check if merchant account is valid
        if ($merchantAccount === null) {
            Log::warning('Merchant account is null, skipping refund', [
                'city' => $city,
                'application' => $application,
                'orderReference' => $orderReference
            ]);
            return response()->json([
                'orderReference' => $orderReference,
                'reasonCode' => 1002,
                'reason' => 'Invalid merchant account',
                'transactionStatus' => 'FAILED',
                'merchantAccount' => null
            ], 400);
        }

        $params = [
            "merchantAccount" => $merchantAccount,
            "orderReference" => $orderReference,
            "amount" => $amount,
            "currency" => "UAH",
        ];

        Log::debug('Initial refund parameters', $params);

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

        Log::debug('Final refund parameters', array_merge($params, [
            'merchantSignature' => !empty($params['merchantSignature']) ? 'generated' : 'not generated'
        ]));

        // Dispatch refund job
        try {
            dispatch(new RefundSettleCardPayJob($params, $orderReference, "refundVerifyCards"))
                ->onQueue('medium');

            Log::info('Refund job dispatched', [
                'orderReference' => $orderReference,
                'job' => 'RefundSettleCardPayJob'
            ]);
            return response()->json([
                'orderReference' => $orderReference,
                'reasonCode' => 0,
                'reason' => 'Refund job dispatched successfully',
                'transactionStatus' => 'PENDING',
                'merchantAccount' => $merchantAccount
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch refund job', [
                'orderReference' => $orderReference,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'orderReference' => $orderReference,
                'reasonCode' => 1003,
                'reason' => 'Failed to dispatch refund job: ' . $e->getMessage(),
                'transactionStatus' => 'FAILED',
                'merchantAccount' => $merchantAccount
            ], 500);
        }
    }


    public function refundSettle($params, $orderReference)
    {
        $startTime = time(); // Время начала выполнения скрипта
        $maxDuration = 2 * 60; // 2 минуты в секундах

        while (true) {
// Отправка POST-запроса к API
            $response = Http::post('https://api.wayforpay.com/api', $params);
            $responseArray = $response->json(); // Проверка на валидный JSON

            if (!is_array($responseArray)) {
                Log::error("refundSettleJob Некорректный ответ от API", ['response' => $response->body()]);

            } else {
                Log::debug("refundSettleJob Ответ от API", $responseArray);

                (new DailyTaskController)->sentTaskMessage("Проверка холда: " . json_encode($responseArray));

                // Проверка статуса транзакции

                $transactionStatus = strtolower(trim($responseArray['transactionStatus'] ?? ''));

                if (in_array($transactionStatus, ['refunded', 'voided', 'approved'])) {
                    Log::info("refundSettleJob Успешная транзакция: {$transactionStatus}");
                    (new MessageSentController)->sentMessageAdminLog("refund Статус транзакции: {$transactionStatus}");
                    return "exit";
                }
            }

            // Проверяем, превышено ли время ожидания
            if (time() - $startTime > $maxDuration) {
                Log::warning("refundSettleJob Превышен лимит времени. Прекращение попыток.");
                return "exit";
            }
            sleep(10);
        }

        Log::debug("refundSettleJob Завершение метода");
        return "exit";
    }

    public function refundSettleJob($params, $orderReference)
    {
        $invoice = WfpInvoice::where("orderReference", $orderReference)->first();

        Log::debug("refundSettleJob WfpInvoice invoice->transactionStatus: $invoice->transactionStatus");
        $messageAdmin = "refundSettleJob WfpInvoice invoice->transactionStatus: $invoice->transactionStatus";

        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        $transactionStatus = strtolower(trim($invoice->transactionStatus ?? ''));
        Log::debug("refundSettleJob WfpInvoice transactionStatus: {$transactionStatus}");
        $messageAdmin = "refundSettleJob WfpInvoice transactionStatus: {$transactionStatus}";

        (new MessageSentController)->sentMessageAdminLog($messageAdmin);


        if (in_array($transactionStatus, ['refunded', 'voided', 'approved'])) {
            return "exit";
        } else {
            Log::debug("refundSettleJob Транзакция отклонена.");


            $startTime = time(); // Время начала выполнения скрипта
            $maxDuration = 2 * 60; // 2 минуты в секундах
            $maxAttempts = 12; // Максимум 12 попыток (2 минуты при 10 секундах ожидания)
            $attempts = 0;
            while (true) {
                // Отправка POST-запроса к API
                $response = Http::post('https://api.wayforpay.com/api', $params);
                $responseArray = $response->json(); // Проверка на валидный JSON

                if (!is_array($responseArray)) {
                    Log::error("refundSettleJob Некорректный ответ от API", ['response' => $response->body()]);

                } else {
                    Log::debug("refundSettleJob Ответ от API", $responseArray);

                    (new DailyTaskController)->sentTaskMessage("Попытка проверки холда: " . json_encode($responseArray));

                    // Проверка статуса транзакции

                    $transactionStatus = strtolower(trim($responseArray['transactionStatus'] ?? ''));

                    if (in_array($transactionStatus, ['refunded', 'voided', 'approved'])) {
                        Log::info("refundSettleJob Успешная транзакция: {$transactionStatus}");
                        (new MessageSentController)->sentMessageAdminLog("refund Статус транзакции: {$transactionStatus}");

                        $this->updateOrderStatus($responseArray, $orderReference);
                        return "exit";
                    } else {
                        $invoice = WfpInvoice::where("orderReference", $orderReference)->first();
                        $transactionStatus = strtolower(trim($invoice->transactionStatus ?? ''));
                        Log::debug("refundSettleJob WfpInvoice transactionStatus: {$transactionStatus}");

                        if (in_array($transactionStatus, ['refunded', 'voided', 'approved'])) {
                            return "exit";
                        } else {
                            Log::debug("refundSettleJob Транзакция отклонена. Повторная попытка через 10 секунд.");
                        }
                    }
                    // Проверяем, превышено ли время ожидания

                    if (time() - $startTime > $maxDuration) {
                        $this->updateOrderStatus($responseArray, $orderReference);
                        Log::warning("refundSettleJob Превышен лимит времени. Прекращение попыток.");
                        return "exit";
                    }
                }

                $attempts++;
                if ($attempts > $maxAttempts) {
                    Log::warning("refundSettleJob Превышено число попыток. Прекращение цикла.");
                    return "exit";
                }
                sleep(10);
            }

            Log::debug("refundSettleJob Завершение метода");
            return "exit";
        }
    }

    public function refundSettleOneUAH($params)
    {

        $messageAdmin = "Запущен refundSettleOneUAH ";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
        $startTime = time(); // Время начала выполнения скрипта
        $maxDuration = 2 * 60; // 2 минуты в секундах
        $maxAttempts = 12; // Максимум 12 попыток (2 минуты при 10 секундах ожидания)
        $attempts = 0;
        while (true) {
            // Отправка POST-запроса к API
            $response = Http::post('https://api.wayforpay.com/api', $params);
            $responseArray = $response->json(); // Проверка на валидный JSON

            if (!is_array($responseArray)) {
                Log::error("refundSettleJob Некорректный ответ от API", ['response' => $response->body()]);
                $messageAdmin = "refundSettleJob Некорректный ответ от API ";
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
            } else {
                Log::debug("refundSettleJob Ответ от API", $responseArray);

                (new DailyTaskController)->sentTaskMessage("Возврат за привязку карты: " . json_encode($responseArray));
                $messageAdmin = "refundSettleJob Возврат за привязку карты: " . json_encode($responseArray) ;
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
                // Проверка статуса транзакции

                $transactionStatus = strtolower(trim($responseArray['transactionStatus'] ?? ''));

                if (in_array($transactionStatus, ['refunded', 'voided', 'approved'])) {
                    return "exit";
                }
                // Проверяем, превышено ли время ожидания

                if (time() - $startTime > $maxDuration) {
                    Log::warning("refundSettleJob Превышен лимит времени. Прекращение попыток.");
                    return "exit";
                }
            }

            $attempts++;
            if ($attempts > $maxAttempts) {
                Log::warning("refundSettleJob Превышено число попыток. Прекращение цикла.");
                return "exit";
            }
            sleep(10);
        }

        Log::debug("refundSettleOneUAH Завершение метода");
        return "exit";


    }
    /**
     * Обновляет статус заказа в базах данных.
     *
     * @param array $responseArray Ответ от API
     * @param string $orderReference Ссылка на заказ
     * @return void
     */
    private function updateOrderStatus(array $responseArray, string $orderReference)
    {
        $orderReferenceApi = $responseArray['orderReference'] ?? null;
        $transactionStatus = $responseArray['transactionStatus'] ?? null;
        $reason = $responseArray['reason'] ?? null;
        $reasonCode = $responseArray['reasonCode'] ?? null;

        if (!$orderReferenceApi || !$transactionStatus) {
            Log::error("refundSettleJob Некорректные данные для обновления заказа", $responseArray);
            return;
        }

        $wfpOrder = WfpInvoice::where('orderReference', $orderReferenceApi)->first();
        if ($wfpOrder) {
            $wfpOrder->transactionStatus = $transactionStatus;
            $wfpOrder->reason = $reason;
            $wfpOrder->reasonCode = $reasonCode;
            $wfpOrder->save();
        }

        $webOrder = Orderweb::where('wfp_order_id', $orderReference)->first();
        if ($webOrder) {
            $webOrder->wfp_status_pay = $transactionStatus;
            $webOrder->save();
        }

        Log::info("refundSettleJob Обновлен статус заказа: {$transactionStatus}");
    }



    public function settle(
        $application,
        $city,
        $orderReference,
        $amount
    ) {
        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                break;
            case "foreign countries":
                $city = "Kyiv City";
                break;
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
        }


        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = $merchant->wfp_merchantSecretKey;
            if ($merchantAccount != null) {
                $orderwebs = Orderweb::where("wfp_order_id", $orderReference)->first();
                $wfpInvoices = WfpInvoice::where("dispatching_order_uid", $orderwebs->dispatching_order_uid)->get();
                if ($wfpInvoices->isNotEmpty()) {
                    foreach ($wfpInvoices as $value) {
                        $params = [
                            "merchantAccount" => $merchantAccount,
                            "orderReference" => $value->orderReference,
                            "amount" => $value->amount,
                            "currency" => "UAH",
                        ];

                        $params = [
                            "transactionType" => "SETTLE",
                            "merchantAccount" => $merchantAccount,
                            "orderReference" => $value->orderReference,
                            "amount" => $value->amount,
                            "currency" => "UAH",
                            "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "settle"),
                            "apiVersion" => 1
                        ];
                        dispatch(new RefundSettleCardPayJob($params, $orderReference, "settle"))
                            ->onQueue('medium');

                    }


                }
//            else {
//                $params = [
//                    "merchantAccount" => $merchantAccount,
//                    "orderReference" => $orderReference,
//                    "amount" => $amount,
//                    "currency" => "UAH",
//                ];
//
//                $params = [
//                    "transactionType" => "SETTLE",
//                    "merchantAccount" => $merchantAccount,
//                    "orderReference" => $orderReference,
//                    "amount" => $amount,
//                    "currency" => "UAH",
//                    "merchantSignature" => self::generateHmacMd5Signature($params, $secretKey, "settle"),
//                    "apiVersion" => 1
//                ];
//                RefundSettleCardPayJob::dispatch($params, $orderReference);
//            }
            }
        }
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

        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                break;
            case "foreign countries":
                $city = "Kyiv City";
                break;
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
        }

        if ($city != "OdessaTest") {
            $serviceUrl =  "https://m.easy-order-taxi.site/wfp/serviceUrl/$application";
        } else {
            $serviceUrl =  "https://t.easy-order-taxi.site/wfp/serviceUrl/$application";
        }

        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = $merchant->wfp_merchantSecretKey;

            $orderDate = strtotime(date('Y-m-d H:i:s'));

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
                "serviceUrl" => $serviceUrl,
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
        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                break;
            case "foreign countries":
                $city = "Kyiv City";
                break;
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
        }

        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = $merchant->wfp_merchantSecretKey;

            $orderDate = strtotime(date('Y-m-d H:i:s'));

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
    }

    /**
     * @throws \Pusher\PusherException
     * @throws \Pusher\ApiErrorException
     */
    public function chargeActiveToken(
        $application,
        $city,
        $orderReference,
        $amount,
        $productName,
        $clientEmail,
        $clientPhone
    ) {
        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                break;
            case "foreign countries":
                $city = "Kyiv City";
                break;
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
        }

        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = $merchant->wfp_merchantSecretKey;

            $recToken = (new CardsController)->getActiveCard($clientEmail, $city, $application)['rectoken'];
            if ($recToken != null) {
                $recToken = (new CardsController)->decryptToken($recToken);

                $orderDate = strtotime(date('Y-m-d H:i:s'));

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

                $responseStatus = self::checkStatus(
                    $application,
                    $city,
                    $orderReference
                );
                $data = json_decode($responseStatus->body(), true);

                $wfpInvoices = WfpInvoice::where("orderReference", $orderReference)->first();
                if ($wfpInvoices !== null) {
                    if (isset($data['transactionStatus'])) {
                        try {
                            $transactionStatus = $data['transactionStatus'];
                            $uid = $wfpInvoices->dispatching_order_uid;
                            (new PusherController)->sentStatusWfp(
                                $transactionStatus,
                                $uid,
                                $application,
                                $clientEmail
                            );
                        } catch (\Exception $e) {
                            Log::error("Ошибка в sentStatusWfp для orderReference: $orderReference: " . $e->getMessage());
                            throw $e;
                        }
                    } else {
                        Log::error("transactionStatus отсутствует в данных для orderReference: $orderReference");
                    }
                } else {
                    Log::warning("WfpInvoice не найдено для orderReference: $orderReference");
                }

                return $response;
            }
        }
    }

    public function chargeActiveTokenWithChangeToken(
        $application,
        $city,
        $orderReference,
        $uid,
        $productName,
        $clientEmail,
        $clientPhone
    ) {
        switch ($city) {
            case "Lviv":
            case "Ivano_frankivsk":
            case "Vinnytsia":
            case "Poltava":
            case "Sumy":
            case "Kharkiv":
            case "Chernihiv":
            case "Rivne":
            case "Ternopil":
            case "Khmelnytskyi":
            case "Zakarpattya":
            case "Zhytomyr":
            case "Kropyvnytskyi":
            case "Mykolaiv":
            case "Chernivtsi":
            case "Lutsk":
                $city = "OdessaTest";
                break;
            case "foreign countries":
                $city = "Kyiv City";
                break;
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS4::where("name", $city)->first();
        }


        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = $merchant->wfp_merchantSecretKey;

            $recToken = (new CardsController)->getActiveCard($clientEmail, $city, $application)['rectoken'];
            if ($recToken != null) {
                $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();

                $amount = $orderweb->client_cost;

                $recToken = (new CardsController)->decryptToken($recToken);

                $orderDate = strtotime(date('Y-m-d H:i:s'));

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

                $responseStatus = self::checkStatus(
                    $application,
                    $city,
                    $orderReference
                );
                $data = json_decode($responseStatus->body(), true);

                $wfpInvoices = WfpInvoice::where("orderReference", $orderReference)->first();
                if ($wfpInvoices !== null) {
                    if (isset($data['transactionStatus'])) {
                        try {
                            $transactionStatus = $data['transactionStatus'];
                            $uid = $wfpInvoices->dispatching_order_uid;
                            (new PusherController)->sentStatusWfp(
                                $transactionStatus,
                                $uid,
                                $application,
                                $clientEmail
                            );
                        } catch (\Exception $e) {
                            Log::error("Ошибка в sentStatusWfp для orderReference: $orderReference: " . $e->getMessage());
                            throw $e;
                        }
                    } else {
                        Log::error("transactionStatus отсутствует в данных для orderReference: $orderReference");
                    }
                } else {
                    Log::warning("WfpInvoice не найдено для orderReference: $orderReference");
                }

                return $response;
            }
        }
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
            case "TRANSACTION_LIST":
                $signatureString = implode(';', [
                    $params['merchantAccount'],
                    $params['dateBegin'],
                    $params['dateEnd']
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
            case "http://188.190.245.102:7303 ":
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
            if($response != "error") {
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
            } else {
                $connectAPI = $orderweb->server;
                $url = $connectAPI . '/api/weborders/cancel/' . $uid;
                $resp = Http::withHeaders([
                    "Authorization" => $authorization,
                    "X-WO-API-APP-ID" => $identificationId
                ])->put($url);
                $messageAdmin = "Запрос отмены безналичного заказа  $uid url: $url";
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);

                $messageAdmin = "Ответ по запросу безналичного заказа  $uid url: " . json_decode($resp, true);
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);

                $url = $connectAPI . '/api/weborders/cancel/' .  $uid_double;
                $resp = Http::withHeaders([
                    "Authorization" => $authorization,
                    "X-WO-API-APP-ID" => $identificationId
                ])->put($url);

                $messageAdmin = "Запрос отмены наличного дубля заказа $uid_double url: $url";
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);

                $messageAdmin = "Ответ по запросу отмены дубля заказа  $uid_double url: " . json_decode($resp, true);
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
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
            case "http://188.190.245.102:7303 ":
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
//    public function wfpStatus($bonusOrder, $doubleOrder, $bonusOrderHold)
//    {
//        Log::info("wfpStatus");
//        $result = 0;
//        $order = Orderweb::where("dispatching_order_uid", $bonusOrderHold)->first();
//        $wfp_order_id = $order->wfp_order_id;
//        $connectAPI = $order->server;
//
//        $messageAdmin = "function wfpStatus запущена для  $bonusOrder, $doubleOrder, $bonusOrderHold ";
//        (new MessageSentController)->sentMessageAdmin($messageAdmin);
//
//        switch ($order->comment) {
//            case "taxi_easy_ua_pas1":
//                $application = "PAS1";
//                break;
//            case "taxi_easy_ua_pas2":
//                $application = "PAS2";
//                break;
//            default:
//                $application = "PAS4";
//        }
//        switch ($order->server) {
//            case "http://188.190.245.102:7303":
//            case "http://31.43.107.151:7303":
//                $city = "OdessaTest";
//                break;
//            case "http://167.235.113.231:7307":
//            case "http://167.235.113.231:7306":
//            case "http://134.249.181.173:7208":
//            case "http://91.205.17.153:7208":
//                $city = "Kyiv City";
//                break;
//            case "http://142.132.213.111:8071":
//            case "http://167.235.113.231:7308":
//                $city = "Dnipropetrovsk Oblast";
//                break;
//            case "http://142.132.213.111:8072":
//                $city = "Odessa";
//                break;
//            case "http://142.132.213.111:8073":
//                $city = "Zaporizhzhia";
//                break;
//            default:
//                $city = "Cherkasy Oblast";
//        }
//
//        $orderReference = $wfp_order_id;
//
//        $autorization = self::autorization($connectAPI);
//        $identificationId = $order->comment;
//        $amount = $order->web_cost;
//        $amount_settle = $amount;
////        $amount = $order->web_cost;
////
////        if($order->client_cost !=null) {
////            $amount = $order->client_cost + $order->attempt_20;
////        }
////
////        $amount_settle = $amount;
//        $bonusOrder_response = (new UIDController)->closeReasonUIDStatusFirstWfp(
//            $bonusOrder,
//            $connectAPI,
//            $autorization,
//            $identificationId
//        );
//        if ($bonusOrder_response != -1) {
//            $closeReason_bonusOrder = $bonusOrder_response["close_reason"];
//            $order_cost_bonusOrder = $bonusOrder_response["order_cost"];
//            $order_car_info_bonusOrder = $bonusOrder_response["order_car_info"];
//            Log::debug("closeReason_bonusOrder: $closeReason_bonusOrder");
//            Log::debug("order_cost_bonusOrder: $order_cost_bonusOrder");
//        } else {
//            $closeReason_bonusOrder = -1;
//            $order_cost_bonusOrder = $amount;
//            $order_car_info_bonusOrder = null;
//            self::messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $bonusOrder);
//        }
//        $messageAdmin = "function wfpStatus closeReason_bonusOrder: $closeReason_bonusOrder";
//        (new MessageSentController)->sentMessageAdmin($messageAdmin);
//
//        $doubleOrder_response = (new UIDController)->closeReasonUIDStatusFirstWfp(
//            $doubleOrder,
//            $connectAPI,
//            $autorization,
//            $identificationId
//        );
//        if ($doubleOrder_response != -1) {
//            $closeReason_doubleOrder = $doubleOrder_response["close_reason"];
//            $order_cost_doubleOrder = $doubleOrder_response["order_cost"];
//            $order_car_info_doubleOrder = $doubleOrder_response["order_car_info"];
//            Log::debug("closeReason_doubleOrder: $closeReason_doubleOrder");
//            Log::debug("order_cost_doubleOrder : $order_cost_doubleOrder");
//        } else {
//            $closeReason_doubleOrder = -1;
//            $order_cost_doubleOrder = $amount;
//            $order_car_info_doubleOrder = null;
//            self::messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $doubleOrder);
//        }
//        $messageAdmin = "function wfpStatus closeReason_doubleOrder: $closeReason_doubleOrder";
//        (new MessageSentController)->sentMessageAdmin($messageAdmin);
//
//        $bonusOrderHold_response = (new UIDController)->closeReasonUIDStatusFirstWfp(
//            $bonusOrderHold,
//            $connectAPI,
//            $autorization,
//            $identificationId
//        );
//        if ($bonusOrderHold_response != -1) {
//            $closeReason_bonusOrderHold = $bonusOrderHold_response["close_reason"];
//            $order_cost_bonusOrderHold = $bonusOrderHold_response["order_cost"];
//            $order_car_info_bonusOrderHold = $bonusOrderHold_response["order_car_info"];
//            Log::debug("closeReason_bonusOrderHold: $closeReason_bonusOrderHold");
//            Log::debug("order_cost_bonusOrderHold : $order_cost_bonusOrderHold");
//        } else {
//            $closeReason_bonusOrderHold = -1;
//            $order_cost_bonusOrderHold = $amount;
//            $order_car_info_bonusOrderHold = null;
//            self::messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $bonusOrderHold);
//        }
//
//
//        $hold_bonusOrder = false;
//        switch ($closeReason_bonusOrder) {
//            case "0":
//            case "8":
//                $hold_bonusOrder = true;
//                $amount_settle = $order_cost_bonusOrder;
//                $result = 1;
//                $order->auto = $order_car_info_bonusOrder;
//                break;
//        }
//        $hold_doubleOrder = false;
//        switch ($closeReason_doubleOrder) {
//            case "0":
//            case "8":
//                $hold_doubleOrder = true;
//                $amount_settle = $order_cost_bonusOrderHold;
//                $result = 1;
//                $order->auto = $order_car_info_doubleOrder;
//                break;
//        }
//        $hold_bonusOrderHold = false;
//        switch ($closeReason_bonusOrderHold) {
//            case "0":
//            case "8":
//                $hold_bonusOrderHold = true;
//                $amount_settle = $order_cost_bonusOrderHold;
//                $result = 1;
//                $order->auto = $order_car_info_bonusOrderHold;
//                break;
//        }
//        if ($amount >= $amount_settle) {
//            $amount = $amount_settle;
//            $order->web_cost = $amount;
//            $order->save();
//        } else {
//            $subject = "Оплата поездки больше холда";
//            $localCreatedAt = Carbon::parse($order->created_at)->setTimezone('Europe/Kiev');
//            $messageAdmin = "Заказ $bonusOrderHold. Сервер $connectAPI. Время $localCreatedAt.
//                 Маршрут $order->routefrom - $order->routeto.
//                 Телефон клиента:  $order->user_phone.
//                 Сумма холда $amount грн. Сумма заказа $amount_settle грн.";
//            $paramsAdmin = [
//                'subject' => $subject,
//                'message' => $messageAdmin,
//            ];
//            $alarmMessage = new TelegramController();
//
//            try {
//                $alarmMessage->sendAlarmMessage($messageAdmin);
//                $alarmMessage->sendMeMessage($messageAdmin);
//            } catch (Exception $e) {
//                $subject = 'Ошибка в телеграмм';
//                $paramsCheck = [
//                    'subject' => $subject,
//                    'message' => $e,
//                ];
//
//                try {
//                    Mail::to('taxi.easy.ua.sup@gmail.com')->send(new Check($paramsCheck));
//                } catch (\Exception $e) {
//                    Log::error('Mail send failed: ' . $e->getMessage());
//                    // Дополнительные действия для предотвращения сбоя
//                }
//
//            };
//
//            try {
//                Mail::to('taxi.easy.ua.sup@gmail.com')->send(new Check($paramsAdmin));
//                Mail::to('cartaxi4@gmail.com')->send(new Check($paramsAdmin));
//            } catch (\Exception $e) {
//                Log::error('Mail send failed: ' . $e->getMessage());
//                // Дополнительные действия для предотвращения сбоя
//            }
//
//        }
//
//        if ($hold_bonusOrder || $hold_doubleOrder || $hold_bonusOrderHold) {
//            self::settle(
//                $application,
//                $city,
//                $orderReference,
//                $amount
//            );
//            $user = User::where("email", $order->email)->first();
//            (new BonusBalanceController)->recordsAddApp($order->id, $user->id, "2", $amount, $application);
//            (new BonusBalanceController)->userBalanceApp($user->id, $application);
//
//            if ($hold_bonusOrder) {
//                $order->closeReason = $closeReason_bonusOrder;
//            }
//            if ($hold_doubleOrder) {
//                $order->closeReason = $closeReason_doubleOrder;
//            }
//            if ($hold_bonusOrderHold) {
//                $order->closeReason = $closeReason_bonusOrderHold;
//            }
//        } else {
//            if ($closeReason_bonusOrder != "-1"
//                && $closeReason_doubleOrder != "-1"
//                && $closeReason_bonusOrderHold != "-1") {
//                self::refund(
//                    $application,
//                    $city,
//                    $orderReference,
//                    $amount
//                );
//                $order->closeReason = $closeReason_bonusOrderHold;
//            }
//        }
//
//
////        self::checkStatus(
////            $application,
////            $city,
////            $orderReference
////        );
//
//        $order->save();
////        }
//        return $result;
//
//    }
    public function wfpStatus($bonusOrder, $doubleOrder, $bonusOrderHold)
    {
        Log::info("wfpStatus started for bonusOrder: $bonusOrder, doubleOrder: $doubleOrder, bonusOrderHold: $bonusOrderHold");
        $result = 0;
        $order = Orderweb::where("dispatching_order_uid", $bonusOrderHold)->first();
        Log::info("Order retrieved: dispatching_order_uid=$bonusOrderHold, wfp_order_id={$order->wfp_order_id}, server={$order->server}, initial closeReason={$order->closeReason}");

        $wfp_order_id = $order->wfp_order_id;
        $connectAPI = $order->server;

        $messageAdmin = "function wfpStatus запущена для $bonusOrder, $doubleOrder, $bonusOrderHold";
        (new MessageSentController)->sentMessageAdmin($messageAdmin);
        Log::info("Admin message sent: $messageAdmin");

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
        Log::info("Application determined: $application");

        switch ($order->server) {
            case "http://188.190.245.102:7303":
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
        Log::info("City determined: $city");

        $orderReference = $wfp_order_id;
        $autorization = self::autorization($connectAPI);
        Log::info("Authorization completed for connectAPI: $connectAPI");

        $identificationId = $order->comment;
        $amount = $order->web_cost;
        $amount_settle = $amount;
        Log::info("Initial amount: $amount, amount_settle: $amount_settle");

        $bonusOrder_response = (new UIDController)->closeReasonUIDStatusFirstWfp(
            $bonusOrder,
            $connectAPI,
            $autorization,
            $identificationId
        );
        if ($bonusOrder_response != -1) {
            $closeReason_bonusOrder = $bonusOrder_response["close_reason"];
            $order_cost_bonusOrder = $bonusOrder_response["order_cost"];
            $order_car_info_bonusOrder = $bonusOrder_response["order_car_info"];
            Log::debug("closeReason_bonusOrder: $closeReason_bonusOrder, order_cost_bonusOrder: $order_cost_bonusOrder");
        } else {
            $closeReason_bonusOrder = -1;
            $order_cost_bonusOrder = $amount;
            $order_car_info_bonusOrder = null;
            self::messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $bonusOrder);
            Log::info("bonusOrder_response failed, closeReason_bonusOrder set to -1");
        }
        $messageAdmin = "function wfpStatus closeReason_bonusOrder: $closeReason_bonusOrder";
        (new MessageSentController)->sentMessageAdmin($messageAdmin);
        Log::info("Admin message sent: $messageAdmin");

        $doubleOrder_response = (new UIDController)->closeReasonUIDStatusFirstWfp(
            $doubleOrder,
            $connectAPI,
            $autorization,
            $identificationId
        );
        if ($doubleOrder_response != -1) {
            $closeReason_doubleOrder = $doubleOrder_response["close_reason"];
            $order_cost_doubleOrder = $doubleOrder_response["order_cost"];
            $order_car_info_doubleOrder = $doubleOrder_response["order_car_info"];
            Log::debug("closeReason_doubleOrder: $closeReason_doubleOrder, order_cost_doubleOrder: $order_cost_doubleOrder");
        } else {
            $closeReason_doubleOrder = -1;
            $order_cost_doubleOrder = $amount;
            $order_car_info_doubleOrder = null;
            self::messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $doubleOrder);
            Log::info("doubleOrder_response failed, closeReason_doubleOrder set to -1");
        }
        $messageAdmin = "function wfpStatus closeReason_doubleOrder: $closeReason_doubleOrder";
        (new MessageSentController)->sentMessageAdmin($messageAdmin);
        Log::info("Admin message sent: $messageAdmin");

        $bonusOrderHold_response = (new UIDController)->closeReasonUIDStatusFirstWfp(
            $bonusOrderHold,
            $connectAPI,
            $autorization,
            $identificationId
        );
        if ($bonusOrderHold_response != -1) {
            $closeReason_bonusOrderHold = $bonusOrderHold_response["close_reason"];
            $order_cost_bonusOrderHold = $bonusOrderHold_response["order_cost"];
            $order_car_info_bonusOrderHold = $bonusOrderHold_response["order_car_info"];
            Log::debug("closeReason_bonusOrderHold: $closeReason_bonusOrderHold, order_cost_bonusOrderHold: $order_cost_bonusOrderHold");
        } else {
            $closeReason_bonusOrderHold = -1;
            $order_cost_bonusOrderHold = $amount;
            $order_car_info_bonusOrderHold = null;
            self::messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $bonusOrderHold);
            Log::info("bonusOrderHold_response failed, closeReason_bonusOrderHold set to -1");
        }

        $hold_bonusOrder = false;
        switch ($closeReason_bonusOrder) {
            case "0":
            case "8":
                $hold_bonusOrder = true;
                $amount_settle = $order_cost_bonusOrder;
                $result = 1;
                $order->auto = $order_car_info_bonusOrder;
                Log::info("hold_bonusOrder set to true, amount_settle: $amount_settle, result: $result, order->auto updated");
                break;
            default:
                Log::info("closeReason_bonusOrder ($closeReason_bonusOrder) not 0 or 8, hold_bonusOrder remains false");
        }

        $hold_doubleOrder = false;
        switch ($closeReason_doubleOrder) {
            case "0":
            case "8":
                $hold_doubleOrder = true;
                $amount_settle = $order_cost_bonusOrderHold;
                $result = 1;
                $order->auto = $order_car_info_doubleOrder;
                Log::info("hold_doubleOrder set to true, amount_settle: $amount_settle, result: $result, order->auto updated");
                break;
            default:
                Log::info("closeReason_doubleOrder ($closeReason_doubleOrder) not 0 or 8, hold_doubleOrder remains false");
        }

        $hold_bonusOrderHold = false;
        switch ($closeReason_bonusOrderHold) {
            case "0":
            case "8":
                $hold_bonusOrderHold = true;
                $amount_settle = $order_cost_bonusOrderHold;
                $result = 1;
                $order->auto = $order_car_info_bonusOrderHold;
                Log::info("hold_bonusOrderHold set to true, amount_settle: $amount_settle, result: $result, order->auto updated");
                break;
            default:
                Log::info("closeReason_bonusOrderHold ($closeReason_bonusOrderHold) not 0 or 8, hold_bonusOrderHold remains false");
        }

        if ($amount >= $amount_settle) {
            $amount = $amount_settle;
            $order->web_cost = $amount;
            $order->save();
            Log::info("amount ($amount) >= amount_settle ($amount_settle), updated order->web_cost: $amount, order saved");
        } else {
            $subject = "Оплата поездки больше холда";
            $localCreatedAt = Carbon::parse($order->created_at)->setTimezone('Europe/Kiev');
            $messageAdmin = "Заказ $bonusOrderHold. Сервер $connectAPI. Время $localCreatedAt.
             Маршрут $order->routefrom - $order->routeto.
             Телефон клиента: $order->user_phone.
             Сумма холда $amount грн. Сумма заказа $amount_settle грн.";
            $paramsAdmin = [
                'subject' => $subject,
                'message' => $messageAdmin,
            ];
            $alarmMessage = new TelegramController();

            try {
                $alarmMessage->sendAlarmMessage($messageAdmin);
                $alarmMessage->sendMeMessage($messageAdmin);
                Log::info("Telegram messages sent successfully");
            } catch (Exception $e) {
                $subject = 'Ошибка в телеграмм';
                $paramsCheck = [
                    'subject' => $subject,
                    'message' => $e,
                ];
                try {
                    Mail::to('taxi.easy.ua.sup@gmail.com')->send(new Check($paramsCheck));
                    Log::info("Error mail sent to taxi.easy.ua.sup@gmail.com");
                } catch (\Exception $e) {
                    Log::error('Mail send failed: ' . $e->getMessage());
                }
            }

            try {
                Mail::to('taxi.easy.ua.sup@gmail.com')->send(new Check($paramsAdmin));
                Mail::to('cartaxi4@gmail.com')->send(new Check($paramsAdmin));
                Log::info("Admin mails sent successfully");
            } catch (\Exception $e) {
                Log::error('Mail send failed: ' . $e->getMessage());
            }
        }

        if ($hold_bonusOrder || $hold_doubleOrder || $hold_bonusOrderHold) {
            self::settle(
                $application,
                $city,
                $orderReference,
                $amount
            );
            $user = User::where("email", $order->email)->first();
            (new BonusBalanceController)->recordsAddApp($order->id, $user->id, "2", $amount, $application);
            (new BonusBalanceController)->userBalanceApp($user->id, $application);
            Log::info("Settle called, recordsAddApp and userBalanceApp executed");

            if ($hold_bonusOrder) {
                $order->closeReason = $closeReason_bonusOrder;
                Log::info("order->closeReason set to $closeReason_bonusOrder due to hold_bonusOrder");
            }
            if ($hold_doubleOrder) {
                $order->closeReason = $closeReason_doubleOrder;
                Log::info("order->closeReason set to $closeReason_doubleOrder due to hold_doubleOrder");
            }
            if ($hold_bonusOrderHold) {
                $order->closeReason = $closeReason_bonusOrderHold;
                Log::info("order->closeReason set to $closeReason_bonusOrderHold due to hold_bonusOrderHold");
            }
        } else {
            if ($closeReason_bonusOrder != "-1"
                && $closeReason_doubleOrder != "-1"
                && $closeReason_bonusOrderHold != "-1") {
                self::refund(
                    $application,
                    $city,
                    $orderReference,
                    $amount
                );
                $order->closeReason = $closeReason_bonusOrderHold;
                Log::info("Refund called, order->closeReason set to $closeReason_bonusOrderHold");
            } else {
                Log::info("Refund not called, at least one closeReason is -1");
            }
        }

        Log::info("Final order->closeReason before save: {$order->closeReason}");
        $order->save();
        Log::info("Order saved with closeReason: {$order->closeReason}, result: $result");

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
            case "http://188.190.245.102:7303 ":
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

    public function createInvoiceVod(
        $uidDriver,
        $amount,
        $language
    ): Response {

        $driverData  = (new FCMController)->readUserInfoFromFirestore($uidDriver);

        if ($driverData !== null) {
            $clientName = $driverData['name'] ?? 'Unknown';
            $clientEmail = $driverData['email'] ?? 'Unknown';
            $clientPhone = $driverData['phoneNumber'] ?? 'Unknown';
            $clientNumber = $driverData['driverNumber'] ?? 'Unknown';
        } else {
            // Обработка случая, когда данные не были получены
            Log::error("Failed to retrieve driver data.");
            $clientName = 'Unknown';
            $clientEmail = 'Unknown';
            $clientPhone = 'Unknown';
            $clientNumber = 'Unknown';
        }

        $productName = "Поповнення балансу драйвера $clientName (позывной $clientNumber, телефон $clientPhone, email $clientEmail) по іншії допоміжній діяльності у сфері транспорту";

        $orderReference = "VOD-" . time() . '-' . rand(1000, 9999);

        $merchantAccount = config("app.merchantAccount");
        $secretKey = config("app.merchantSecretKey");
        $serviceUrl =  "https://m.easy-order-taxi.site/wfp/serviceUrl/VOD";

        // Получаем текущее время в UTC
        $currentUtcTime = new DateTime('now', new DateTimeZone('UTC'));

// Устанавливаем временную зону Киева (UTC+3)
        $currentUtcTime->setTimezone(new DateTimeZone('Europe/Kiev'));

// Форматируем дату и время
        $formattedDateTime = $currentUtcTime->format('Y-m-d H:i:s');

// Преобразуем в метку времени
        $orderDate = strtotime($formattedDateTime);

        $params_order = [
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

        $params = [
            "transactionType" => "CREATE_INVOICE",
            "merchantAccount" => $merchantAccount,
            "merchantAuthType" => "SimpleSignature",
            "merchantDomainName" => "m.easy-order-taxi.site",
            "merchantSignature" => self::generateHmacMd5Signature($params_order, $secretKey, "createInvoice"),
            "apiVersion" => 1,
            "language" => $language,
//            "returnUrl" => "https://m.easy-order-taxi.site/wfp/returnUrl",
            "serviceUrl" => $serviceUrl,
            "orderReference" => $orderReference,
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "orderTimeout" => 86400,
            "merchantTransactionType" => "SALE",
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1],
//            "paymentSystems" => "card;privat24;googlePay;applePay",
            "paymentSystems" => "card;privat24;",
            "clientEmail" => $clientEmail,
            "clientPhone" => $clientPhone,
            "notifyMethod" => "bot"
        ];

// Відправлення POST-запиту
        $response = Http::post('https://api.wayforpay.com/api', $params);
        Log::debug("CREATE_INVOICE", ['response' => $response->body()]);
        return $response;
    }
    public function chargeVOD(
        $uidDriver,
        $amount,
        $recToken
    ): Response {
        $driverData  = (new FCMController)->readUserInfoFromFirestore($uidDriver);

        if ($driverData !== null) {
            $clientName = $driverData['name'] ?? 'Unknown';
            $clientEmail = $driverData['email'] ?? 'Unknown';
            $clientPhone = $driverData['phoneNumber'] ?? 'Unknown';
            $clientNumber = $driverData['driverNumber'] ?? 'Unknown';
        } else {
            // Обработка случая, когда данные не были получены
            Log::error("Failed to retrieve driver data.");
            $clientName = 'Unknown';
            $clientEmail = 'Unknown';
            $clientPhone = 'Unknown';
            $clientNumber = 'Unknown';
        }

        $productName = "Поповнення балансу драйвера $clientName (позывной $clientNumber, телефон $clientPhone, email $clientEmail) по іншії допоміжній діяльності у сфері транспорту";

        $orderReference = "VOD-" . time() . '-' . rand(1000, 9999);

        $merchantAccount = config("app.merchantAccount");
        $secretKey = config("app.merchantSecretKey");
        $serviceUrl =  "https://m.easy-order-taxi.site/wfp/serviceUrl/VOD";

        // Получаем текущее время в UTC
        $currentUtcTime = new DateTime('now', new DateTimeZone('UTC'));

// Устанавливаем временную зону Киева (UTC+3)
        $currentUtcTime->setTimezone(new DateTimeZone('Europe/Kiev'));

// Форматируем дату и время
        $formattedDateTime = $currentUtcTime->format('Y-m-d H:i:s');

// Преобразуем в метку времени
        $orderDate = strtotime($formattedDateTime);

        $params_order = [
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

        $params = [
            "transactionType" => "CHARGE",
            "merchantAccount" => $merchantAccount,
            "merchantAuthType" => "SimpleSignature",
            "merchantDomainName" => "m.easy-order-taxi.site",
            "merchantTransactionType" => "SALE",
//            "merchantTransactionSecureType" => "AUTO",
            "merchantTransactionSecureType" => "NON3DS",
            "merchantSignature" => self::generateHmacMd5Signature($params_order, $secretKey, "charge"),
            "apiVersion" => 1,
            "orderReference" => $orderReference,
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "recToken" => $recToken,
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1],
            "clientFirstName" => $clientName,
            "clientLastName" => " ",
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

    public function transactionList($merchant) {

        $currentDate = Carbon::now()->subDay();


        $nextDate = $currentDate->copy()->addDay();

        $dateBegin = $currentDate->timestamp;
        $dateEnd  = $nextDate->timestamp;

        switch ($merchant) {
            case "taxi":
                $merchantAccount = config("app.merchantAccount");
                $secretKey = config("app.merchantSecretKey");
                break;
            case "my":
                $merchantAccount =  config("app.merchantAccountMy");
                $secretKey = config("app.merchantSecretKeyMy");
                break;

        }

        $params = [
            "transactionType" => "TRANSACTION_LIST",
            "merchantAccount" => $merchantAccount,
            "apiVersion" => 1,
            "dateBegin" => $dateBegin,
            "dateEnd" => $dateEnd,
        ];

        $merchantSignature =  self::generateHmacMd5Signature($params, $secretKey, "TRANSACTION_LIST");

        $params = [
            "apiVersion" => 1,
            "transactionType" => "TRANSACTION_LIST",
            "merchantAccount" => $merchantAccount,
            "merchantSignature" => $merchantSignature,
            "dateBegin" => $dateBegin,
            "dateEnd" => $dateEnd,
        ];

        $response = Http::post('https://api.wayforpay.com/api', $params);
        $responseArray = $response->json();

        foreach ($response['transactionList'] as $transactionData) {
            Transaction::updateOrCreate(
                ['order_reference' => $transactionData['orderReference'],
                    'transaction_status' => $transactionData['transactionStatus'],
                ],  // Уникальные ключи для поиска
                [
                    'merchantAccount' => $merchantAccount,
                    'transaction_type' => $transactionData['transactionType'],
                    'amount' => $transactionData['amount'],
                    'currency' => $transactionData['currency'],
                    'base_amount' => $transactionData['baseAmount'],
                    'base_currency' => $transactionData['baseCurrency'],
                    'transaction_status' => $transactionData['transactionStatus'],
                    'created_date' => Carbon::createFromTimestamp($transactionData['createdDate']),
                    'processing_date' => $transactionData['processingDate'] ? Carbon::createFromTimestamp($transactionData['processingDate']) : null,
                    'reason_code' => $transactionData['reasonCode'],
                    'reason' => $transactionData['reason'],
                    'settlement_date' => $transactionData['settlementDate'] ? Carbon::createFromTimestamp($transactionData['settlementDate']) : null,
                    'email' => $transactionData['email'],
                    'phone' => $transactionData['phone'],
                    'payment_system' => $transactionData['paymentSystem'] ?? 'unknown',
                    'card_pan' => $transactionData['cardPan'],
                    'card_type' => $transactionData['cardType'],
                    'issuer_bank_country' => $transactionData['issuerBankCountry'],
                    'issuer_bank_name' => $transactionData['issuerBankName'],
                    'fee' => $transactionData['fee'],
                ]
            );
        }
        return $responseArray;
    }

    public function transactionListJob() {
        self::transactionList("taxi");
        self::transactionList("my");
        self::findOrdersWithoutVoidedAmountOne();
        self::findOrdersWithoutVoided();
    }


    public function findOrdersWithoutVoided()
    {
        // Получаем все уникальные order_reference с transaction_status "WaitingAuthComplete" или "Pending",
        // у которых нет пары с transaction_status "Voided"
        $orders = Transaction::select('merchantAccount', 'order_reference', 'amount')
            ->where(function ($query) {
                $query->where('transaction_status', 'WaitingAuthComplete')
                    ->orWhere('transaction_status', 'Pending');
            })
            ->whereNotIn('order_reference', function ($query) {
                $query->select('order_reference')
                    ->from('transactions')
                    ->where('transaction_status', 'Voided');
            })
            ->get();


        // Проверяем, если коллекция пуста
        if ($orders->isEmpty()) {
            $messageAdmin = "Результат проверки платежей за последние сутки: нет подвисших холдов";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            Transaction::truncate();

            return response()->json(["result" => "нет подвисших холдов"]);
        }

        // Логируем информацию о найденных заказах
        $messageAdmin = "Результат проверки платежей за последние сутки: нужно проверить: " . json_encode($orders);
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        // Возвращаем результат
        return response()->json($orders);
    }

    public function findOrdersWithoutVoidedAmountOne()
    {
        // Получаем все уникальные order_reference с transaction_status "WaitingAuthComplete",
        // у которых нет пары с transaction_status "Voided"
        $orders = Transaction::select('merchantAccount', 'order_reference', 'amount')
            ->where('transaction_status', 'WaitingAuthComplete')
            ->where('amount', 1)  // Условие для amount == 1
            ->whereNotIn('order_reference', function($query) {
                $query->select('order_reference')
                    ->from('transactions')
                    ->where('transaction_status', 'Voided');
            })
            ->get();

        $orders->each(function($order) {
            // Вызываем ваш метод возврата, передавая необходимые данные
            $this->processRefund($order);
        });

        // Выводим результат
        // Проверяем, если коллекция пуста
        if ($orders->isEmpty()) {
            return response()->json(["result" => "нет подвисших холдов"]);
        }

        // Возвращаем результат
        return response()->json($orders);

    }
    public function processRefund($order) {

         switch ($order["merchantAccount"]) {
             case "taxi":
                 $secretKey = config("app.merchantSecretKey");
                 break;
             default:
                 $secretKey = config("app.merchantSecretKeyMy");
         }
         $params = [
             "transactionType" => "REFUND",
             "merchantAccount" =>$order['merchantAccount'],
             "orderReference" => $order["order_reference"],
             "amount" => 1,
             "currency" => "UAH",
             "comment" => "Повернення платежу",
             "merchantSignature" => self::generateHmacMd5Signature([
                 "transactionType" => "REFUND",
                 "merchantAccount" => $order['merchantAccount'],
                 "orderReference" => $order["order_reference"],
                 "amount" => 1,
                 "currency" => "UAH",
             ], $secretKey, "refund"),
             "apiVersion" => 1,
         ];

        // Диспетчеризация задачи RefundSettleCardPayJob
        dispatch(new RefundSettleCardPayJob($params, $order["order_reference"], "refundVerifyCards"))
            ->onQueue('medium');

    }

    public function verifyHold ($uid): array
    {
        $result = "hold";

        $wfpInvoices = WfpInvoice::where("dispatching_order_uid", $uid)-> get();
        if($wfpInvoices != null) {
            foreach ($wfpInvoices as $value) {
                if($value->transactionStatus !== "WaitingAuthComplete") {
                    $result = "no_hold";
                    return ["result" => $result];
                }
            }
        }


        return ["result" => $result];
    }

    public function deleteInvoice($orderReference): array
    {
        $invoice = WfpInvoice::where('orderReference', $orderReference)->first();

        if ($invoice !== null) {
            $invoice->delete();
            return ['result' => 'deleted'];
        }

        return ['result' => 'not_found'];
    }
}

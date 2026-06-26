<?php

namespace App\Http\Controllers;

use App\City\PaymentFlow;
use App\Helpers\WfpOrderPaymentContextHelper;
use App\Jobs\CheckStatusJob;
use App\Jobs\RefundSettleCardPayJob;
use App\Jobs\StartAddCostCardBottomCreat;
use App\Services\PaymentStatusNotifier;
use App\Services\WfpHoldRefundEligibility;
use App\Mail\Check;
use App\Mail\Server;
use App\Models\Card;
use App\Models\City;
use App\Models\City_PAS1;
use App\Models\City_PAS2;
use App\Models\City_PAS4;
use App\Models\City_PAS5;
use App\Models\Orderweb;
use App\Models\Transaction;
use App\Models\Uid_history;
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
use Illuminate\Support\Facades\Cache;
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

        if ($user && self::shouldPersistRecTokenFromServiceUrl($data)) {
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

        if ($user && self::shouldPersistRecTokenFromServiceUrl($data)) {
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

        if (!empty($data['orderReference']) && !empty($data['merchantAccount'])) {
            $invoice = WfpInvoice::where('orderReference', $data['orderReference'])->first();
            if ($invoice) {
                $this->stampInvoiceMerchantAccount($invoice, $data['merchantAccount']);
            }
        }

        $user = User::where('email', $data['email'])->first();

        if ($user && self::shouldPersistRecTokenFromServiceUrl($data)) {
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

        $this->syncGooglePayServiceUrlCallback($data);

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

        if ($user && self::shouldPersistRecTokenFromServiceUrl($data)) {
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

        if ($user && self::shouldPersistRecTokenFromServiceUrl($data)) {
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

        $this->syncGooglePayServiceUrlCallback($data);

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

        if ($user && self::shouldPersistRecTokenFromServiceUrl($data)) {
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

        $this->syncGooglePayServiceUrlCallback($data);

        return [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time,
            "signature" =>  $signature
        ];
    }

    public function serviceUrl_PAS5(Request $request)
    {
        Log::debug("serviceUrl " . $request);

        $data = json_decode($request->getContent(), true);
        Log::debug($data['email']);
        Log::debug($data['recToken']);

        $user = User::where('email', $data['email'])->first();

        if ($user && self::shouldPersistRecTokenFromServiceUrl($data)) {
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
        $merchant = City_PAS5::where("name", $city)->first();
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

        if ($user && self::shouldPersistRecTokenFromServiceUrl($data)) {
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

        $this->syncGooglePayServiceUrlCallback($data);

        return [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time,
            "signature" =>  $signature
        ];
    }
    public function serviceUrl_PAS5_app(Request $request)
    {
        Log::debug("serviceUrl " . $request);

        $data = json_decode($request->getContent(), true);
        Log::debug($data['email']);
        Log::debug($data['recToken']);

        $user = User::where('email', $data['email'])->first();

        if ($user && self::shouldPersistRecTokenFromServiceUrl($data)) {
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
                ->where('app', "PAS5")
                ->where('masked_card', $data['cardPan'])
                ->first();

            if (!$card) {
                $card = new Card();
                $card->user_id = $user->id;
                $card->pay_system = 'wfp';
                $card->app = 'PAS5';
                $card->masked_card = $data['cardPan'];
                $card->card_type = $cardType;
                $card->bank_name = $bankName;
                $card->rectoken =  $rectoken;
                $card->merchant = $data['merchantAccount'];
//                $card->rectoken_lifetime = $data['rectoken_lifetime'];
                $card->save();
                (new CardsController)->setActiveFirstCardApp($data['email'], $card->id, 'PAS5');
            }
        }

        $time = strtotime(date('Y-m-d H:i:s'));

        $params = [
            "orderReference" => $request->orderReference,
            "status" => "accept",
            "time" => $time
        ];
        $city = "OdessaTest";
        $merchant = City_PAS5::where("name", $city)->first();
        $secretKey = $merchant->wfp_merchantSecretKey;

        $signature = self::generateHmacMd5Signature($params, $secretKey, "serviceUrl");

        $this->syncGooglePayServiceUrlCallback($data);

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
        if (is_array($userData) && self::shouldPersistRecTokenFromServiceUrl($data)) {
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
        if (is_array($userData) && self::shouldPersistRecTokenFromServiceUrl($data)) {
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
            case "PAS4":
                $merchant = City_PAS4::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS5::where("name", $city)->first();
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
            case "PAS4":
                $merchant = City_PAS4::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS5::where("name", $city)->first();
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

    /**
     * WFP reasonCode 1127 — заказ ещё не создан (CHARGE не отправлен). Не пишем Declined в invoice.
     */
    public static function shouldSkipCheckStatusInvoiceUpdate(array $wfpResponse): bool
    {
        return (int) ($wfpResponse['reasonCode'] ?? 0) === 1127;
    }

    /**
     * Токен из Google Pay не сохраняем как привязанную карту wfp_payment.
     */
    public static function shouldPersistRecTokenFromServiceUrl(array $data): bool
    {
        if (strtolower((string) ($data['paymentSystem'] ?? '')) === 'googlepay') {
            return false;
        }

        $recToken = $data['recToken'] ?? null;

        return $recToken !== null && $recToken !== '';
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
            case "PAS4":
                $merchant = City_PAS4::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS5::where("name", $city)->first();
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
                    if (self::shouldSkipCheckStatusInvoiceUpdate($data)) {
                        Log::debug('checkStatus: order not in WFP yet, skip invoice update', [
                            'orderReference' => $orderReference,
                        ]);

                        return $response;
                    }

                    $invoice = WfpInvoice::where("orderReference", $orderReference)->first();
                    if ($data['transactionStatus'] != "WaitingAuthComplete") {
                        dispatch(new CheckStatusJob($application, $city, $orderReference))
                            ->onQueue('medium');

                    }
                    if ($invoice) {
                        $this->stampInvoiceMerchantFromWfpResponse($invoice, $data, $merchantAccount);
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
            case "PAS4":
                $merchant = City_PAS4::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS5::where("name", $city)->first();
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

            $invoice = WfpInvoice::where("orderReference", $orderReference)->first();
            $amount = $invoice->amount ?? null;
            if ($amount === null) {
                $order = Orderweb::where("wfp_order_id", $orderReference)->first();
                $amount = $order->web_cost ?? $order->client_cost ?? null;
            }
            $isAddCostInvoice = $this->isAddCostInvoice($invoice);
            if ($amount == "1" || $amount == 1) {
                $maxAttempts = 18; // 3 минуты / 10 секунд = 18 попыток
            } elseif ($isAddCostInvoice) {
                $maxAttempts = 18; // доплата: до 3 минут ожидания Approved
            } else {
                $maxAttempts = 6; // 1 минуты / 10 секунд = 6 попыток
            }

            $attempt = 0;

            do {
                $response = Http::post('https://api.wayforpay.com/api', $params);
                Log::debug("checkStatus attempt $attempt response", ['body' => $response->body()]);

                $data = json_decode($response->body(), true);

                if (!$data || !isset($data['transactionStatus'])) {
                    Log::error("Ошибка получения ответа от WayforPay API на попытке $attempt");
                } else {
                    $transactionStatus = $data['transactionStatus'];
                    if (!$invoice) {
                        $invoice = WfpInvoice::where('orderReference', $orderReference)->first();
                    }
                    if ($invoice) {
                        $this->stampInvoiceMerchantFromWfpResponse($invoice, $data, $merchantAccount);
                        $invoice->transactionStatus = $transactionStatus;
                        $invoice->reason = $data['reason'] ?? null;
                        $invoice->reasonCode = $data['reasonCode'] ?? null;
                        $invoice->save();
                    }

                    if ($isAddCostInvoice) {
                        if (in_array($transactionStatus, ['Approved', 'WaitingAuthComplete'], true)) {
                            $this->dispatchAddCostProcessing($application, $city, $orderReference, $invoice);
                            return $response;
                        }
                        if ($transactionStatus === 'Declined') {
                            $uid = $invoice->dispatching_order_uid ?? null;
                            if ($uid) {
                                $clientEmail = Orderweb::where('dispatching_order_uid', $uid)->value('email');
                                PaymentStatusNotifier::notifyTransactionStatus(
                                    $transactionStatus,
                                    $uid,
                                    $application,
                                    $clientEmail ?? ''
                                );
                            }
                            return $response;
                        }
                    } elseif ($transactionStatus === "WaitingAuthComplete") {
                        if (!$invoice) {
                            if (($data['amount'] ?? null) == "1" || $amount == "1" || $amount == 1) {
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

                        return $response;
                    }
                }

                sleep(10);
                $attempt++;
            } while ($attempt < $maxAttempts);

            Log::error("Тайм-аут checkStatusJob: orderReference=$orderReference, addCost=" . ($isAddCostInvoice ? 'yes' : 'no'));
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
            case "taxi_easy_ua_pas4":
                $application = "PAS4";
                break;
            default:
                $application = "PAS5";
        }

        switch ($order->server) {
            case "http://188.40.143.61:7222":
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
            case "my_server_api":
                $city = $this->resolveWfpCityFromOrderweb($order);
                break;
            default:
                $city = $this->resolveWfpCityFromOrderweb($order);
        }
        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                 break;
            case "PAS4":
                $merchant = City_PAS4::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS5::where("name", $city)->first();
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
        $amount,
        ?string $dispatchingOrderUid = null
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
            case "PAS4":
                $merchant = City_PAS4::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS5::where("name", $city)->first();
        }




        if (isset($merchant)) {
            Log::info('Merchant is set', ['merchant_id' => $merchant->id ?? null]);

            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = $merchant->wfp_merchantSecretKey;

            Log::info('Retrieved merchant account and secret key', [
                'merchantAccount' => $merchantAccount,
                'orderReference' => $orderReference
            ]);

            $invoiceQuery = WfpInvoice::where('orderReference', $orderReference)
                ->where('transactionStatus', 'WaitingAuthComplete');

            if ($dispatchingOrderUid !== null && $dispatchingOrderUid !== '') {
                $invoiceQuery->where('dispatching_order_uid', $dispatchingOrderUid);
            }

            $wfpInvoices = $invoiceQuery->get();

            if ($wfpInvoices->isNotEmpty()) {
                Log::info('Processing invoices for refund', [
                    'invoice_count' => $wfpInvoices->count(),
                    'orderReference' => $orderReference,
                    'dispatching_order_uid' => $dispatchingOrderUid,
                ]);

                foreach ($wfpInvoices as $value) {
                    Log::info('Checking transaction status for invoice', [
                        'invoice_id' => $value->id,
                        'orderReference' => $value->orderReference,
                        'dispatching_order_uid' => $value->dispatching_order_uid,
                        'transactionStatus' => $value->transactionStatus
                    ]);

                    $transactionStatus = $value->transactionStatus;
                    if ($transactionStatus === 'WaitingAuthComplete') {
                        Log::info('Transaction status is WaitingAuthComplete, preparing refund', [
                            'invoice_id' => $value->id,
                            'orderReference' => $value->orderReference
                        ]);

                        $this->stampInvoiceMerchantAccount($value, $merchantAccount);

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

                        Log::info('Refund parameters prepared', [
                            'params' => $params,
                            'orderReference' => $value->orderReference
                        ]);

                        $dispatchKey = 'wfp_refund_dispatch:' . $value->orderReference;
                        if (!Cache::add($dispatchKey, 1, 300)) {
                            Log::info('Refund job already dispatched recently, skipping', [
                                'orderReference' => $value->orderReference,
                            ]);
                            continue;
                        }

                        RefundSettleCardPayJob::dispatch($params, $value->orderReference, 'refund')
                            ->onQueue('medium');

                        Log::info('Refund job dispatched to medium queue', [
                            'orderReference' => $value->orderReference,
                            'invoice_id' => $value->id,
                        ]);
                    } else {
                        Log::warning('Transaction status not eligible for refund', [
                            'invoice_id' => $value->id,
                            'transactionStatus' => $transactionStatus
                        ]);
                    }
                }
            } else {
                Log::warning('No invoices found for processing', ['orderReference' => $orderReference]);
            }
        } else {
            Log::error('Merchant not set', ['orderReference' => $orderReference]);
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
            case "PAS4":
                $merchant = City_PAS4::where("name", $city)->first();
                Log::info('Merchant query for PAS4', [
                    'city' => $city,
                    'merchant_found' => $merchant !== null
                ]);
                break;
            default:
                $merchant = City_PAS5::where("name", $city)->first();
                Log::info('Merchant query for PAS5 (default)', [
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

//    public function refundSettleJob($params, $orderReference)
//    {
//        $invoice = WfpInvoice::where("orderReference", $orderReference)->first();
//
//        Log::debug("refundSettleJob WfpInvoice invoice->transactionStatus: $invoice->transactionStatus");
//        $messageAdmin = "refundSettleJob WfpInvoice invoice->transactionStatus: $invoice->transactionStatus";
//
//        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
//
//        $transactionStatus = strtolower(trim($invoice->transactionStatus ?? ''));
//        Log::debug("refundSettleJob WfpInvoice transactionStatus: {$transactionStatus}");
//        $messageAdmin = "refundSettleJob WfpInvoice transactionStatus: {$transactionStatus}";
//
//        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
//
//
//        if (in_array($transactionStatus, ['refunded', 'voided', 'approved'])) {
//            return "exit";
//        } else {
//            Log::debug("refundSettleJob Транзакция отклонена.");
//
//
//            $startTime = time(); // Время начала выполнения скрипта
//            $maxDuration = 2 * 60; // 2 минуты в секундах
//            $maxAttempts = 12; // Максимум 12 попыток (2 минуты при 10 секундах ожидания)
//            $attempts = 0;
//            while (true) {
//                // Отправка POST-запроса к API
//                $response = Http::post('https://api.wayforpay.com/api', $params);
//                $responseArray = $response->json(); // Проверка на валидный JSON
//
//                if (!is_array($responseArray)) {
//                    Log::error("refundSettleJob Некорректный ответ от API", ['response' => $response->body()]);
//
//                } else {
//                    Log::debug("refundSettleJob Ответ от API", $responseArray);
//
//                    (new DailyTaskController)->sentTaskMessage("Попытка проверки холда: " . json_encode($responseArray));
//
//                    // Проверка статуса транзакции
//
//                    $transactionStatus = strtolower(trim($responseArray['transactionStatus'] ?? ''));
//
//                    if (in_array($transactionStatus, ['refunded', 'voided', 'approved'])) {
//                        Log::info("refundSettleJob Успешная транзакция: {$transactionStatus}");
//                        (new MessageSentController)->sentMessageAdminLog("refund Статус транзакции: {$transactionStatus}");
//
//                        $this->updateOrderStatus($responseArray, $orderReference);
//                        return "exit";
//                    } else {
//                        $invoice = WfpInvoice::where("orderReference", $orderReference)->first();
//                        $transactionStatus = strtolower(trim($invoice->transactionStatus ?? ''));
//                        Log::debug("refundSettleJob WfpInvoice transactionStatus: {$transactionStatus}");
//
//                        if (in_array($transactionStatus, ['refunded', 'voided', 'approved'])) {
//                            return "exit";
//                        } else {
//                            Log::debug("refundSettleJob Транзакция отклонена. Повторная попытка через 10 секунд.");
//                        }
//                    }
//                    // Проверяем, превышено ли время ожидания
//
//                    if (time() - $startTime > $maxDuration) {
//                        $this->updateOrderStatus($responseArray, $orderReference);
//                        Log::warning("refundSettleJob Превышен лимит времени. Прекращение попыток.");
//                        return "exit";
//                    }
//                }
//
//                $attempts++;
//                if ($attempts > $maxAttempts) {
//                    Log::warning("refundSettleJob Превышено число попыток. Прекращение цикла.");
//                    return "exit";
//                }
//                sleep(10);
//            }
//
//            Log::debug("refundSettleJob Завершение метода");
//            return "exit";
//        }
//    }

    private function resolveMerchantForInvoice(WfpInvoice $invoice, ?array $dispatchParams = null): ?array
    {
        $merchantAccount = trim((string) ($dispatchParams['merchantAccount'] ?? $invoice->merchantAccount ?? ''));
        if ($merchantAccount !== '') {
            $secretKey = $this->resolveSecretKeyForMerchantAccount($merchantAccount);
            if ($secretKey !== null) {
                $this->stampInvoiceMerchantAccount($invoice, $merchantAccount);
                return [
                    'merchantAccount' => $merchantAccount,
                    'secretKey' => $secretKey,
                ];
            }
        }

        $order = null;
        if (!empty($invoice->dispatching_order_uid)) {
            $order = Orderweb::where('dispatching_order_uid', $invoice->dispatching_order_uid)->first();
        }
        if (!$order) {
            $order = Orderweb::where('wfp_order_id', $invoice->orderReference)->latest()->first();
        }
        if (!$order) {
            return null;
        }

        return $this->checkMerchantInfo($order);
    }

    private function syncInvoiceFromWfpCheckStatus(
        WfpInvoice $invoice,
        string $merchantAccount,
        string $secretKey
    ): ?array {
        $checkParams = [
            'merchantAccount' => $merchantAccount,
            'orderReference' => $invoice->orderReference,
            'transactionType' => 'CHECK_STATUS',
            'merchantSignature' => self::generateHmacMd5Signature([
                'merchantAccount' => $merchantAccount,
                'orderReference' => $invoice->orderReference,
            ], $secretKey, 'checkStatus'),
            'apiVersion' => 1,
        ];

        $response = Http::post('https://api.wayforpay.com/api', $checkParams);
        $data = $response->json();
        if (!is_array($data) || empty($data['transactionStatus'])) {
            Log::warning('refundSettleJob: CHECK_STATUS failed', [
                'orderReference' => $invoice->orderReference,
                'body' => $response->body(),
            ]);
            return null;
        }

        if (
            ($data['transactionStatus'] ?? '') === 'Declined'
            && (int) ($data['reasonCode'] ?? 0) === 1127
        ) {
            Log::warning('refundSettleJob: CHECK_STATUS Order Not Found — skip sync (wrong merchant)', [
                'orderReference' => $invoice->orderReference,
                'merchantAccount' => $merchantAccount,
            ]);
            return null;
        }

        $this->stampInvoiceMerchantAccount($invoice, $merchantAccount);
        $invoice->transactionStatus = $data['transactionStatus'];
        $invoice->reason = $data['reason'] ?? null;
        $invoice->reasonCode = $data['reasonCode'] ?? null;
        $invoice->save();

        Log::info('refundSettleJob: synced from CHECK_STATUS', [
            'orderReference' => $invoice->orderReference,
            'transactionStatus' => $data['transactionStatus'],
            'reasonCode' => $data['reasonCode'] ?? null,
        ]);

        return $data;
    }

    private function buildRefundParams(
        string $merchantAccount,
        string $secretKey,
        WfpInvoice $invoice
    ): array {
        $amount = (string) (int) round((float) $invoice->amount);

        return [
            'transactionType' => 'REFUND',
            'merchantAccount' => $merchantAccount,
            'orderReference' => $invoice->orderReference,
            'amount' => $amount,
            'currency' => 'UAH',
            'comment' => 'Повернення платежу',
            'merchantSignature' => self::generateHmacMd5Signature([
                'transactionType' => 'REFUND',
                'merchantAccount' => $merchantAccount,
                'orderReference' => $invoice->orderReference,
                'amount' => $amount,
                'currency' => 'UAH',
            ], $secretKey, 'refund'),
            'apiVersion' => 1,
        ];
    }

    private function isFinalWfpStatus(?string $status): bool
    {
        return in_array(strtolower(trim($status ?? '')), ['refunded', 'voided', 'approved'], true);
    }

    private function shouldUpdateWfpInvoiceStatus(?string $currentStatus, ?string $newStatus): bool
    {
        if ($newStatus === null || $newStatus === '') {
            return false;
        }

        if (!$this->isFinalWfpStatus($currentStatus)) {
            return true;
        }

        return $this->isFinalWfpStatus($newStatus);
    }

    /**
     * @return bool true если холд уже закрыт (void/refund)
     */
    private function applyRefundApiResponse(
        WfpInvoice $invoice,
        string $orderReference,
        array $responseArray,
        bool $notifyTelegramOnSuccess
    ): bool {
        $apiStatus = strtolower(trim($responseArray['transactionStatus'] ?? ''));
        $reasonCode = (int) ($responseArray['reasonCode'] ?? 0);

        if ($this->isFinalWfpStatus($apiStatus)) {
            if ($notifyTelegramOnSuccess) {
                (new DailyTaskController)->sentTaskMessage(
                    'Холд закрыт (' . $apiStatus . '): ' . json_encode($responseArray)
                );
            }
            Log::info("refundSettleJob Успешная транзакция: {$apiStatus}");
            (new MessageSentController)->sentMessageAdminLog("refund Статус транзакции: {$apiStatus}");
            $this->updateOrderStatus($responseArray, $orderReference);
            Cache::forget('wfp_refund_dispatch:' . $orderReference);
            return true;
        }

        $invoice->refresh();
        if ($this->isFinalWfpStatus($invoice->transactionStatus)) {
            Log::info('refundSettleJob: finalized in DB after refresh', [
                'orderReference' => $orderReference,
                'transactionStatus' => $invoice->transactionStatus,
            ]);
            Cache::forget('wfp_refund_dispatch:' . $orderReference);
            return true;
        }

        if ($apiStatus === 'declined' && in_array($reasonCode, [1126, 1129, 1130], true)) {
            Log::warning('refundSettleJob: refund declined, will allow cron retry', [
                'orderReference' => $orderReference,
                'reasonCode' => $reasonCode,
                'reason' => $responseArray['reason'] ?? null,
                'response' => $responseArray,
            ]);
            Cache::forget('wfp_refund_dispatch:' . $orderReference);
            return false;
        }

        (new DailyTaskController)->sentTaskMessage('Попытка проверки холда: ' . json_encode($responseArray));
        Cache::forget('wfp_refund_dispatch:' . $orderReference);
        return false;
    }

    public function refundSettleJob($params, $orderReference)
    {
        $lock = Cache::lock('refund_settle_job:' . $orderReference, 120);
        if (!$lock->get()) {
            Log::info('refundSettleJob: lock busy, skipping duplicate run', ['orderReference' => $orderReference]);
            return 'exit';
        }

        try {
            $invoice = WfpInvoice::where('orderReference', $orderReference)->first();
            if (!$invoice) {
                Log::warning('refundSettleJob: invoice not found', ['orderReference' => $orderReference]);
                return 'exit';
            }

            if ($this->isFinalWfpStatus($invoice->transactionStatus)) {
                Log::info('refundSettleJob: already finalized in DB, skip API', [
                    'orderReference' => $orderReference,
                    'transactionStatus' => $invoice->transactionStatus,
                ]);
                return 'exit';
            }

            $merchant = $this->resolveMerchantForInvoice(
                $invoice,
                is_array($params) ? $params : null
            );
            if (
                !$merchant
                || empty($merchant['merchantAccount'])
                || $merchant['merchantAccount'] === 'errorMerchantAccount'
            ) {
                Log::error('refundSettleJob: merchant not resolved', ['orderReference' => $orderReference]);
                Cache::forget('wfp_refund_dispatch:' . $orderReference);
                return 'exit';
            }

            $merchantAccount = $merchant['merchantAccount'];
            $secretKey = $merchant['secretKey'];
            $this->stampInvoiceMerchantAccount($invoice, $merchantAccount);

            $this->syncInvoiceFromWfpCheckStatus($invoice, $merchantAccount, $secretKey);
            $invoice->refresh();

            if ($this->isFinalWfpStatus($invoice->transactionStatus)) {
                Log::info('refundSettleJob: WFP already finalized after CHECK_STATUS', [
                    'orderReference' => $orderReference,
                    'transactionStatus' => $invoice->transactionStatus,
                ]);
                $this->updateOrderStatus([
                    'orderReference' => $orderReference,
                    'transactionStatus' => $invoice->transactionStatus,
                    'reason' => $invoice->reason,
                    'reasonCode' => $invoice->reasonCode,
                ], $orderReference);
                Cache::forget('wfp_refund_dispatch:' . $orderReference);
                return 'exit';
            }

            $refundParams = $this->buildRefundParams($merchantAccount, $secretKey, $invoice);
            $responseArray = Http::post('https://api.wayforpay.com/api', $refundParams)->json();
            Log::debug('refundSettleJob Ответ от API (attempt 1)', is_array($responseArray) ? $responseArray : []);

            if (is_array($responseArray) && $this->applyRefundApiResponse($invoice, $orderReference, $responseArray, true)) {
                return 'exit';
            }

            sleep(3);
            $this->syncInvoiceFromWfpCheckStatus($invoice, $merchantAccount, $secretKey);
            $invoice->refresh();

            if ($this->isFinalWfpStatus($invoice->transactionStatus)) {
                Log::info('refundSettleJob: finalized after CHECK_STATUS before retry', [
                    'orderReference' => $orderReference,
                ]);
                $this->updateOrderStatus([
                    'orderReference' => $orderReference,
                    'transactionStatus' => $invoice->transactionStatus,
                    'reason' => $invoice->reason,
                    'reasonCode' => $invoice->reasonCode,
                ], $orderReference);
                Cache::forget('wfp_refund_dispatch:' . $orderReference);
                return 'exit';
            }

            $refundParams = $this->buildRefundParams($merchantAccount, $secretKey, $invoice);
            $responseArray = Http::post('https://api.wayforpay.com/api', $refundParams)->json();
            Log::debug('refundSettleJob Ответ от API (attempt 2)', is_array($responseArray) ? $responseArray : []);

            if (is_array($responseArray)) {
                $this->applyRefundApiResponse($invoice, $orderReference, $responseArray, false);
            }

            return 'exit';
        } finally {
            $lock->release();
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
            if (!empty($responseArray['merchantAccount'])) {
                $this->stampInvoiceMerchantAccount($wfpOrder, $responseArray['merchantAccount']);
            }
            $wfpOrder->transactionStatus = $transactionStatus;
            $wfpOrder->reason = $reason;
            $wfpOrder->reasonCode = $reasonCode;
            $wfpOrder->save();
        }

        if ($wfpOrder && $wfpOrder->dispatching_order_uid) {
            $webOrder = Orderweb::where('dispatching_order_uid', $wfpOrder->dispatching_order_uid)->first();
            if ($webOrder) {
                $webOrder->wfp_status_pay = $transactionStatus;
                $webOrder->save();
            }
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
            case "PAS4":
                $merchant = City_PAS4::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS5::where("name", $city)->first();
        }


        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = $merchant->wfp_merchantSecretKey;
            if ($merchantAccount != null) {
                $orderwebs = Orderweb::where("wfp_order_id", $orderReference)->first();
                if ($orderwebs) {
                    $wfpInvoices = WfpInvoice::where("dispatching_order_uid", $orderwebs->dispatching_order_uid)
                        ->get();
                } else {
                    $wfpInvoices = WfpInvoice::where("orderReference", $orderReference)
                        ->get();
                }
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
            case "PAS4":
                $merchant = City_PAS4::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS5::where("name", $city)->first();
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
            case "PAS4":
                $merchant = City_PAS4::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS5::where("name", $city)->first();
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

    public function googlePayConfig($application, $city)
    {
        $merchant = $this->resolveWfpMerchant($application, $city);
        if ($merchant === null) {
            return response()->json(['error' => 'merchant_not_found'], 404);
        }

        return response()->json([
            'merchantAccount' => $merchant->wfp_merchantAccount,
            'gateway' => 'wayforpay',
        ]);
    }

    public function googlePayCharge(\Illuminate\Http\Request $request)
    {
        $application = $request->input('application');
        $city = $request->input('city');
        $orderReference = $request->input('orderReference');
        $amount = $request->input('amount');
        $productName = $request->input('productName');
        $clientEmail = $request->input('clientEmail');
        $clientPhone = $request->input('clientPhone');
        $paymentDataJson = $request->input('paymentDataJson');

        if (!$application || !$city || !$orderReference || !$amount || !$paymentDataJson) {
            return response()->json(['error' => 'missing_required_fields'], 400);
        }

        $paymentData = json_decode($paymentDataJson, true);
        if (!is_array($paymentData)) {
            return response()->json(['error' => 'invalid_payment_data'], 400);
        }

        $paymentMethodData = $paymentData['paymentMethodData'] ?? null;
        $tokenizationData = is_array($paymentMethodData) ? ($paymentMethodData['tokenizationData'] ?? null) : null;
        $gpToken = is_array($tokenizationData) ? ($tokenizationData['token'] ?? null) : null;
        if (!$gpToken) {
            return response()->json(['error' => 'missing_gp_token'], 400);
        }

        $merchant = $this->resolveWfpMerchant($application, $city);
        if ($merchant === null) {
            return response()->json(['error' => 'merchant_not_found'], 404);
        }

        $merchantAccount = $merchant->wfp_merchantAccount;
        $secretKey = $merchant->wfp_merchantSecretKey;
        $resolvedCity = $this->resolveWfpCityName($city);

        if ($resolvedCity != "OdessaTest") {
            $serviceUrl = "https://m.easy-order-taxi.site/wfp/serviceUrl/$application";
        } else {
            $serviceUrl = "https://t.easy-order-taxi.site/wfp/serviceUrl/$application";
        }

        $orderDate = strtotime(date('Y-m-d H:i:s'));
        $pmInfo = is_array($paymentMethodData) ? ($paymentMethodData['info'] ?? []) : [];
        $billingAddress = is_array($pmInfo) ? ($pmInfo['billingAddress'] ?? []) : [];
        $cardHolder = is_array($billingAddress) && !empty($billingAddress['name'])
            ? $billingAddress['name']
            : 'GOOGLE PAY';

        $signatureParams = [
            "merchantAccount" => $merchantAccount,
            "merchantDomainName" => "m.easy-order-taxi.site",
            "orderReference" => $orderReference,
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1],
        ];

        $params = [
            "transactionType" => "CHARGE",
            "merchantAccount" => $merchantAccount,
            "merchantAuthType" => "SimpleSignature",
            "merchantDomainName" => "m.easy-order-taxi.site",
            "merchantTransactionType" => "AUTH",
            "merchantTransactionSecureType" => "NON3DS",
            "merchantSignature" => self::generateHmacMd5Signature($signatureParams, $secretKey, "charge"),
            "apiVersion" => 1,
            "orderReference" => $orderReference,
            "orderDate" => $orderDate,
            "amount" => $amount,
            "currency" => "UAH",
            "productName" => [$productName],
            "productPrice" => [$amount],
            "productCount" => [1],
            "clientFirstName" => "Bulba",
            "clientLastName" => "Taras",
            "clientEmail" => $clientEmail,
            "clientPhone" => $clientPhone,
            "clientCountry" => "UKR",
            "serviceUrl" => $serviceUrl,
            "notifyMethod" => "bot",
            "cardHolder" => $cardHolder,
            "gpApiVersionMinor" => $paymentData['apiVersionMinor'] ?? 0,
            "gpApiVersion" => $paymentData['apiVersion'] ?? 2,
            "gpPMDescription" => $paymentMethodData['description'] ?? '',
            "gpPMType" => $paymentMethodData['type'] ?? 'CARD',
            "gpPMTCardNetwork" => $pmInfo['cardNetwork'] ?? '',
            "gpPMTCardDetails" => $pmInfo['cardDetails'] ?? '',
            "gpTokenizationType" => $tokenizationData['type'] ?? 'PAYMENT_GATEWAY',
            "gpToken" => $gpToken,
            "holdTimeout" => 1000000,
        ];

        $response = Http::post('https://api.wayforpay.com/api', $params);
        Log::debug("GOOGLE_PAY_CHARGE", ['orderReference' => $orderReference, 'response' => $response->body()]);

        $responseData = json_decode($response->body(), true);
        $transactionStatus = is_array($responseData)
            ? ($responseData['transactionStatus'] ?? $responseData['orderStatus'] ?? null)
            : null;
        try {
            $this->upsertWfpInvoiceRecord(
                $orderReference,
                $amount,
                $merchantAccount,
                is_string($transactionStatus) ? $transactionStatus : null
            );
        } catch (\Throwable $e) {
            Log::error('googlePayCharge: upsert WfpInvoice failed', [
                'order_reference' => $orderReference,
                'transaction_status' => $transactionStatus,
                'error_message' => $e->getMessage(),
            ]);
        }

        try {
            $this->finalizeWalletAddCostAfterCharge(
                $application,
                $resolvedCity,
                $orderReference,
                $amount,
                (string) ($clientEmail ?? ''),
                $response,
                false
            );
        } catch (\Exception $e) {
            Log::error('googlePayCharge add-cost finalize failed', [
                'order_reference' => $orderReference,
                'error_message' => $e->getMessage(),
            ]);
        }

        if (is_array($responseData)
            && in_array($transactionStatus, ['Approved', 'WaitingAuthComplete'], true)) {
            return response()->json($responseData, 200);
        }

        return response($response->body(), $response->status())
            ->header('Content-Type', 'application/json');
    }

    private function resolveWfpCityName($city)
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
                return "OdessaTest";
            case "foreign countries":
                return "Kyiv City";
            default:
                return $city;
        }
    }

    private function resolveWfpMerchant($application, $city)
    {
        $city = $this->resolveWfpCityName($city);
        switch ($application) {
            case "PAS1":
                return City_PAS1::where("name", $city)->first();
            case "PAS2":
                return City_PAS2::where("name", $city)->first();
            case "PAS4":
                return City_PAS4::where("name", $city)->first();
            default:
                return City_PAS5::where("name", $city)->first();
        }
    }

    private function resolveWfpCityFromOrderweb(Orderweb $order): string
    {
        switch ($order->city ?? null) {
            case 'city_kiev':
                $city = 'Kyiv City';
                break;
            case 'city_cherkassy':
                $city = 'Cherkasy Oblast';
                break;
            case 'city_odessa':
                if (
                    ($order->server ?? null) === 'http://188.190.245.102:7303'
                    || ($order->server ?? null) === 'my_server_api'
                ) {
                    $city = 'OdessaTest';
                } else {
                    $city = 'Odessa';
                }
                break;
            case 'city_zaporizhzhia':
                $city = 'Zaporizhzhia';
                break;
            case 'city_dnipro':
                $city = 'Dnipropetrovsk Oblast';
                break;
            case 'city_lviv':
                $city = 'Lviv';
                break;
            case 'city_ivano_frankivsk':
                $city = 'Ivano_frankivsk';
                break;
            case 'city_vinnytsia':
                $city = 'Vinnytsia';
                break;
            case 'city_poltava':
                $city = 'Poltava';
                break;
            case 'city_sumy':
                $city = 'Sumy';
                break;
            case 'city_kharkiv':
                $city = 'Kharkiv';
                break;
            case 'city_chernihiv':
                $city = 'Chernihiv';
                break;
            case 'city_rivne':
                $city = 'Rivne';
                break;
            case 'city_ternopil':
                $city = 'Ternopil';
                break;
            case 'city_khmelnytskyi':
                $city = 'Khmelnytskyi';
                break;
            case 'city_zakarpattya':
                $city = 'Zakarpattya';
                break;
            case 'city_zhytomyr':
                $city = 'Zhytomyr';
                break;
            case 'city_kropyvnytskyi':
                $city = 'Kropyvnytskyi';
                break;
            case 'city_mykolaiv':
                $city = 'Mykolaiv';
                break;
            case 'city_chernivtsi':
                $city = 'Chernivtsi';
                break;
            case 'city_lutsk':
                $city = 'Lutsk';
                break;
            default:
                $city = 'OdessaTest';
        }

        return $this->resolveWfpCityName($city);
    }

    private function resolveSecretKeyForMerchantAccount(string $merchantAccount): ?string
    {
        if ($merchantAccount === 'play_google_com_f183e') {
            return config('app.merchantSecretKey');
        }

        foreach ([City_PAS1::class, City_PAS2::class, City_PAS4::class, City_PAS5::class] as $modelClass) {
            $merchant = $modelClass::where('wfp_merchantAccount', $merchantAccount)->first();
            if ($merchant && !empty($merchant->wfp_merchantSecretKey)) {
                return $merchant->wfp_merchantSecretKey;
            }
        }

        if ($merchantAccount === config('app.merchantAccountMy')) {
            return config('app.merchantSecretKeyMy');
        }

        if ($merchantAccount === config('app.merchantAccount')) {
            return config('app.merchantSecretKey');
        }

        return null;
    }

    private function stampInvoiceMerchantAccount(WfpInvoice $invoice, string $merchantAccount): void
    {
        $merchantAccount = trim($merchantAccount);
        if ($merchantAccount === '' || $invoice->merchantAccount === $merchantAccount) {
            return;
        }

        $invoice->merchantAccount = $merchantAccount;
        $invoice->save();
    }

    private function stampInvoiceMerchantFromWfpResponse(
        WfpInvoice $invoice,
        array $data,
        string $fallbackMerchantAccount
    ): void {
        $merchantAccount = trim((string) ($data['merchantAccount'] ?? ''));
        if ($merchantAccount === '') {
            $merchantAccount = $fallbackMerchantAccount;
        }
        $this->stampInvoiceMerchantAccount($invoice, $merchantAccount);
    }

    private function stampInvoiceMerchantAfterCharge(
        string $orderReference,
        $response,
        string $fallbackMerchantAccount
    ): void {
        $invoice = WfpInvoice::where('orderReference', $orderReference)->first();
        if (!$invoice) {
            return;
        }

        $data = null;
        if (is_object($response) && method_exists($response, 'json')) {
            $data = $response->json();
        } elseif (is_array($response)) {
            $data = $response;
        }

        if (is_array($data)) {
            $this->stampInvoiceMerchantFromWfpResponse($invoice, $data, $fallbackMerchantAccount);
            return;
        }

        $this->stampInvoiceMerchantAccount($invoice, $fallbackMerchantAccount);
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
        $invoice = WfpInvoice::where('orderReference', $orderReference)->first();
        if ($invoice !== null) {
            $order = Orderweb::where('dispatching_order_uid', $invoice->dispatching_order_uid)->first();
            if ($order !== null && (string) $order->pay_system === 'google_pay_payment') {
                Log::info('chargeActiveToken skipped for google_pay_payment', [
                    'orderReference' => $orderReference,
                    'uid' => $order->dispatching_order_uid,
                ]);

                return null;
            }
        }

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
            case "PAS4":
                $merchant = City_PAS4::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS5::where("name", $city)->first();
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
                $this->stampInvoiceMerchantAfterCharge($orderReference, $response, $merchantAccount);

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
                            PaymentStatusNotifier::notifyTransactionStatus(
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


    /**
     * @throws \Pusher\PusherException
     * @throws \Pusher\ApiErrorException
     * @throws \Exception
     */
    public function chargeActiveTokenAddCost(
        $application,
        $city,
        $orderReference,
        $amount,
        $productName,
        $clientEmail,
        $clientPhone
    ) {
        Log::info('Starting chargeActiveTokenAddCost', [
            'method' => 'chargeActiveTokenAddCost',
            'input_parameters' => [
                'application' => $application,
                'original_city' => $city,
                'order_reference' => $orderReference,
                'amount' => $amount,
                'product_name' => $productName,
                'client_email' => $clientEmail,
                'client_phone' => substr($clientPhone, 0, 3) . '****' . substr($clientPhone, -2), // Маскируем телефон
            ],
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Логирование преобразования города
        $originalCity = $city;
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
                Log::debug('City transformed to OdessaTest', [
                    'original_city' => $originalCity,
                    'new_city' => $city,
                    'rule' => 'multiple_cities_to_odessa'
                ]);
                break;
            case "foreign countries":
                $city = "Kyiv City";
                Log::debug('City transformed to Kyiv City', [
                    'original_city' => $originalCity,
                    'new_city' => $city,
                    'rule' => 'foreign_to_kyiv'
                ]);
                break;
            default:
                Log::debug('City unchanged', [
                    'original_city' => $originalCity,
                    'new_city' => $city
                ]);
        }

        // Логирование выбора мерчанта
        Log::info('Selecting merchant', [
            'application' => $application,
            'city' => $city,
            'selection_logic' => 'based_on_application'
        ]);

        switch ($application) {
            case "PAS1":
                $merchant = City_PAS1::where("name", $city)->first();
                $model = 'City_PAS1';
                break;
            case "PAS2":
                $merchant = City_PAS2::where("name", $city)->first();
                $model = 'City_PAS2';
                break;
            case "PAS4":
                $merchant = City_PAS4::where("name", $city)->first();
                $model = 'City_PAS4';
                break;
            default:
                $merchant = City_PAS5::where("name", $city)->first();
                $model = 'City_PAS5';
        }

        Log::debug('Merchant query executed', [
            'model' => $model,
            'city' => $city,
            'merchant_found' => !is_null($merchant),
            'merchant_id' => optional($merchant)->id,
        ]);

        if (isset($merchant)) {
            $merchantAccount = $merchant->wfp_merchantAccount;
            $secretKey = substr($merchant->wfp_merchantSecretKey, 0, 8) . '...'; // Маскируем ключ для логов

            Log::info('Merchant found', [
                'merchant_id' => $merchant->id,
                'merchant_account' => $merchantAccount,
                'secret_key_masked' => $secretKey,
                'city' => $merchant->name,
            ]);

            // Получение активной карты
            Log::debug('Getting active card', [
                'client_email' => $clientEmail,
                'city' => $city,
                'application' => $application,
            ]);

            $cardInfo = (new CardsController)->getActiveCard($clientEmail, $city, $application);
            $recToken = $cardInfo['rectoken'] ?? null;

            Log::info('Card token retrieval', [
                'token_received' => !is_null($recToken),
                'token_present' => !empty($recToken),
                'client_email' => $clientEmail,
            ]);

            if ($recToken != null) {
                $recToken = (new CardsController)->decryptToken($recToken);
                $tokenPreview = substr($recToken, 0, 10) . '...'; // Маскируем токен

                Log::debug('Token decrypted', [
                    'token_preview' => $tokenPreview,
                    'token_length' => strlen($recToken),
                ]);

                $orderDate = strtotime(date('Y-m-d H:i:s'));

                // Подготовка параметров для подписи
                $signatureParams = [
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

                Log::debug('Signature parameters prepared', [
                    'params_for_signature' => $signatureParams,
                    'amount' => $amount,
                    'currency' => 'UAH',
                ]);

                // Формирование полных параметров запроса
                $params = [
                    "transactionType" => "CHARGE",
                    "merchantAccount" => $merchantAccount,
                    "merchantAuthType" => "SimpleSignature",
                    "merchantDomainName" => "m.easy-order-taxi.site",
                    "merchantTransactionType" => "AUTH",
                    "merchantTransactionSecureType" => "NON3DS",
                    "merchantSignature" => self::generateHmacMd5Signature($signatureParams, $merchant->wfp_merchantSecretKey, "charge"),
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

                Log::info('Payment request prepared', [
                    'order_reference' => $orderReference,
                    'amount' => $amount,
                    'currency' => 'UAH',
                    'transaction_type' => 'CHARGE',
                    'secure_type' => 'NON3DS',
                    'merchant_account' => $merchantAccount,
                    'has_signature' => !empty($params['merchantSignature']),
                    'client_info' => [
                        'email' => $clientEmail,
                        'phone_masked' => substr($clientPhone, 0, 3) . '****' . substr($clientPhone, -2),
                    ]
                ]);

                // Отправка запроса к WayForPay
                Log::debug('Sending request to WayForPay API', [
                    'url' => 'https://api.wayforpay.com/api',
                    'parameters_sent' => array_merge($params, [
                        'recToken' => '***MASKED***', // Маскируем чувствительные данные
                        'clientPhone' => substr($clientPhone, 0, 3) . '****' . substr($clientPhone, -2),
                    ]),
                ]);

                try {
                    $addCostInvoice = WfpInvoice::where('orderReference', $orderReference)->first();
                    if ($addCostInvoice !== null && !empty($addCostInvoice->dispatching_order_uid)) {
                        if ($this->hasPendingAddCostPayment(
                            $addCostInvoice->dispatching_order_uid,
                            $orderReference
                        )) {
                            Log::info('Blocked duplicate add-cost while another payment is in progress', [
                                'order_reference' => $orderReference,
                                'uid' => $addCostInvoice->dispatching_order_uid,
                            ]);

                            return response()->json([
                                'orderReference' => $orderReference,
                                'transactionStatus' => 'InProcessing',
                                'reason' => 'add_cost_already_in_progress',
                            ]);
                        }
                    }

                    $startTime = microtime(true);
                    $existingInvoice = WfpInvoice::where("orderReference", $orderReference)->first();
                    $skipCharge = false;
                    if ($existingInvoice !== null) {
                        $existingStatus = $existingInvoice->transactionStatus;
                        if (in_array($existingStatus, ['InProcessing', 'Pending', 'Approved', 'WaitingAuthComplete'], true)) {
                            $skipCharge = true;
                            Log::info('Skipping duplicate CHARGE for add-cost, payment already in progress or completed', [
                                'order_reference' => $orderReference,
                                'transaction_status' => $existingStatus,
                            ]);
                        }
                    }

                    $response = null;
                    if (!$skipCharge) {
                        $response = Http::post('https://api.wayforpay.com/api', $params);
                        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

                        Log::info('WayForPay API response received', [
                            'order_reference' => $orderReference,
                            'response_time_ms' => $responseTime,
                            'http_status' => $response->status(),
                            'response_body_preview' => substr($response->body(), 0, 200) . '...',
                        ]);

                        Log::debug("Full WFP response", [
                            'response' => $response->body(),
                            'headers' => $response->headers(),
                        ]);

                        $chargeData = json_decode($response->body(), true);
                        $chargeReasonCode = (int)($chargeData['reasonCode'] ?? 0);
                        $chargeReason = (string)($chargeData['reason'] ?? '');
                        if ($chargeReasonCode === 1112 || stripos($chargeReason, 'Duplicate Order') !== false) {
                            Log::info('Duplicate Order ID from WFP, polling existing payment status', [
                                'order_reference' => $orderReference,
                                'reason_code' => $chargeReasonCode,
                            ]);
                        }
                    } else {
                        Log::info('Reusing existing add-cost payment, proceeding to status check only', [
                            'order_reference' => $orderReference,
                        ]);
                    }

                    $finalize = $this->finalizeWalletAddCostAfterCharge(
                        $application,
                        $city,
                        $orderReference,
                        $amount,
                        $clientEmail,
                        $response,
                        true
                    );
                    if ($finalize['addCostResult'] !== null) {
                        return $finalize['addCostResult'];
                    }

                    Log::info('Returning WFP response', [
                        'order_reference' => $orderReference,
                        'response_status' => $response !== null ? $response->status() : 'status_check_only',
                    ]);

                    if ($response !== null) {
                        return $response;
                    }

                    return $finalize['responseStatus'];

                } catch (\Exception $e) {
                    Log::error('WayForPay API request failed', [
                        'order_reference' => $orderReference,
                        'error_message' => $e->getMessage(),
                        'error_type' => get_class($e),
                        'merchant_account' => $merchantAccount,
                        'amount' => $amount,
                        'client_email' => $clientEmail,
                        'stack_trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            } else {
                Log::warning('No active card token found', [
                    'client_email' => $clientEmail,
                    'city' => $city,
                    'application' => $application,
                    'order_reference' => $orderReference,
                    'action' => 'Cannot proceed with payment without token',
                ]);
                throw new \Exception('No active card token found for customer');
            }
        } else {
            Log::error('Merchant not found for payment', [
                'application' => $application,
                'city' => $city,
                'order_reference' => $orderReference,
                'amount' => $amount,
                'client_email' => $clientEmail,
                'models_checked' => ['City_PAS1', 'City_PAS2', 'City_PAS4', 'City_PAS5'],
            ]);
            throw new \Exception("Merchant not found for application: $application, city: $city");
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
    ): Response
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
            case "PAS4":
                $merchant = City_PAS4::where("name", $city)->first();
                break;
            default:
                $merchant = City_PAS5::where("name", $city)->first();
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
                $this->stampInvoiceMerchantAfterCharge($orderReference, $response, $merchantAccount);

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
                            PaymentStatusNotifier::notifyTransactionStatus(
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
                $identificationId = config('app.X-WO-API-APP-ID-PAS1');
                break;
            case "taxi_easy_ua_pas2":
                $application = "PAS2";
                $identificationId = config('app.X-WO-API-APP-ID-PAS2');
                break;
            case "taxi_easy_ua_pas4":
                $application = "PAS4";
                $identificationId = config('app.X-WO-API-APP-ID-PAS4');
                break;
            default:
                $application = "PAS5";
                $identificationId = config('app.X-WO-API-APP-ID-PAS5');
        }
        switch ($orderweb->server) {
            case "http://188.190.245.102:7303 ":
                $city = "OdessaTest";
                break;
            case "http://188.40.143.61:7222":
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
            case "taxi_easy_ua_pas4":
                $application = "PAS4";
                break;
            default:
                $application = "PAS5";
        }
        switch ($order->server) {
            case "http://188.190.245.102:7303 ":
                $city = "OdessaTest";
                break;
            case "http://188.40.143.61:7222":
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
//            case "http://188.40.143.61:7222":
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
            case "taxi_easy_ua_pas4":
                $application = "PAS4";
                break;
            default:
                $application = "PAS5";
        }
        Log::info("Application determined: $application");

        switch ($order->server) {
            case "http://188.190.245.102:7303":
            case "http://31.43.107.151:7303":
                $city = "OdessaTest";
                break;
            case "http://188.40.143.61:7222":
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
            $wfpInvoices = WfpInvoice::where('dispatching_order_uid', $bonusOrder) -> get();
            if ($wfpInvoices != null) {
                foreach ($wfpInvoices as $value) {
                    $orderReference = $value->orderReference;
                    $amount = $value->amount;
                    self::settle(
                        $application,
                        $city,
                        $orderReference,
                        $amount
                    );
                }
            }
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
                    $amount,
                    $bonusOrderHold
                );
                $wfpInvoices = WfpInvoice::where('dispatching_order_uid', $bonusOrder)->get();
                if ($wfpInvoices != null) {
                    foreach ($wfpInvoices as $value) {
                        self::refund(
                            $application,
                            $city,
                            $value->orderReference,
                            $value->amount,
                            $value->dispatching_order_uid
                        );
                    }
                }

                $order->closeReason = $closeReason_bonusOrderHold;
                Log::info("Refund called, order->closeReason set to $closeReason_bonusOrderHold");
            } else {
                Log::info("Refund not called, at least one closeReason is -1");
            }
        }

        $activeOnDispatch = in_array((string) $closeReason_bonusOrder, ['-1'], true)
            || in_array((string) $closeReason_doubleOrder, ['-1'], true)
            || in_array((string) $closeReason_bonusOrderHold, ['-1'], true);
        if ($activeOnDispatch) {
            $order->closeReason = '-1';
            Log::info('wfpStatus: dispatch still active, order->closeReason reset to -1');
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
            case "taxi_easy_ua_pas4":
                $application = "PAS4";
                break;
            default:
                $application = "PAS5";
        }
        switch ($orderweb->server) {
            case "http://188.190.245.102:7303 ":
                $city = "OdessaTest";
                break;
            case "http://188.40.143.61:7222":
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
             case "play_google_com_f183e":
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
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();
        if ($order === null) {
            return ['result' => 'no_hold'];
        }

        $mainOrderReference = $order->wfp_order_id ?? null;
        if ($mainOrderReference !== null && $mainOrderReference !== ''
            && $this->hasPendingAddCostPayment($uid, $mainOrderReference)) {
            return ['result' => 'pending_add_cost'];
        }

        if ($mainOrderReference === null || $mainOrderReference === '') {
            return ['result' => 'no_hold'];
        }

        $mainInvoice = WfpInvoice::where('orderReference', $mainOrderReference)->first();
        if ($mainInvoice === null) {
            return ['result' => 'no_hold'];
        }

        if (in_array($mainInvoice->transactionStatus, ['Approved', 'WaitingAuthComplete'], true)) {
            return ['result' => 'hold'];
        }

        return ['result' => 'no_hold'];
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

    /**
     * Доплата картой: invoice с другим orderReference, чем основной платёж заказа.
     */
    public function isAddCostInvoice(?WfpInvoice $invoice): bool
    {
        if ($invoice === null || empty($invoice->dispatching_order_uid) || empty($invoice->orderReference)) {
            return false;
        }

        $order = Orderweb::where('dispatching_order_uid', $invoice->dispatching_order_uid)->first();
        if ($order === null) {
            return false;
        }

        if (!empty($order->wfp_order_id) && $order->wfp_order_id !== $invoice->orderReference) {
            return true;
        }

        $mainCost = (float)($order->web_cost ?? $order->client_cost ?? 0);
        $invoiceAmount = (float)($invoice->amount ?? 0);

        return $mainCost > 0 && $invoiceAmount > 0 && $invoiceAmount < $mainCost;
    }

    /**
     * Есть ли незавершённая доплата по uid (InProcessing/Pending/WaitingAuthComplete).
     */
    public function hasPendingAddCostPayment(string $uid, ?string $mainOrderReference = null): bool
    {
        $query = WfpInvoice::where('dispatching_order_uid', $uid);

        if ($mainOrderReference !== null && $mainOrderReference !== '') {
            $query->where('orderReference', '!=', $mainOrderReference);
        }

        // WaitingAuthComplete — платёж уже прошёл, идёт пересоздание заказа; не блокируем повторное повышение.
        return $query->whereIn('transactionStatus', ['InProcessing', 'Pending'])->exists();
    }

    /**
     * Запуск пересоздания заказа после успешной доплаты (идемпотентно).
     */
    private function dispatchAddCostProcessing(
        string $application,
        string $city,
        string $orderReference,
        ?WfpInvoice $invoice
    ): void {
        if ($invoice === null) {
            return;
        }

        $uid = $invoice->dispatching_order_uid;
        $amount = $invoice->amount;
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();

        if ($order !== null && $order->wfp_order_id === $orderReference) {
            Log::info('Add-cost already applied, skipping duplicate processing', [
                'order_reference' => $orderReference,
                'uid' => $uid,
            ]);
            return;
        }

        $uid_history = Uid_history::where('uid_bonusOrderHold', $uid)->first();
        if (!$uid_history) {
            Log::warning('dispatchAddCostProcessing: uid_history not found', [
                'uid' => $uid,
                'order_reference' => $orderReference,
            ]);
            return;
        }

        $order = Orderweb::where('dispatching_order_uid', $uid)->first();
        $payMethod = $this->resolveAddCostPayMethod($order);

        dispatch(new StartAddCostCardBottomCreat(
            $uid,
            $uid_history->uid_doubleOrder,
            $payMethod,
            $orderReference,
            $city,
            $amount
        ))->onQueue('high');

        Log::info('StartAddCostCardBottomCreat dispatched from payment poll', [
            'order_reference' => $orderReference,
            'uid' => $uid,
            'amount' => $amount,
            'application' => $application,
        ]);
    }

    /**
     * Доплата картой/Google Pay: checkStatus → push → пересоздание заказа (как chargeActiveTokenAddCost).
     *
     * @param mixed $chargeResponse ответ WayForPay CHARGE (может быть null при skipCharge)
     * @return array{addCostResult: mixed, responseStatus: mixed}
     */
    private function finalizeWalletAddCostAfterCharge(
        string $application,
        string $city,
        string $orderReference,
        $amount,
        string $clientEmail,
        $chargeResponse,
        bool $returnAddCostResult
    ): array {
        Log::debug('finalizeWalletAddCostAfterCharge', [
            'order_reference' => $orderReference,
            'application' => $application,
            'city' => $city,
        ]);

        $responseStatus = self::checkStatus($application, $city, $orderReference);

        Log::debug('finalizeWalletAddCostAfterCharge status check', [
            'status_response' => $responseStatus->body(),
            'status_code' => $responseStatus->status(),
        ]);

        $data = json_decode($responseStatus->body(), true);
        $invoice = WfpInvoice::where('orderReference', $orderReference)->first();
        $addCostResult = null;

        if ($invoice === null || !$this->isAddCostInvoice($invoice)) {
            return [
                'addCostResult' => null,
                'responseStatus' => $responseStatus,
            ];
        }

        if (!isset($data['transactionStatus'])) {
            Log::error('finalizeWalletAddCostAfterCharge: transactionStatus missing', [
                'order_reference' => $orderReference,
                'response_data' => $data,
            ]);

            return [
                'addCostResult' => null,
                'responseStatus' => $responseStatus,
            ];
        }

        $transactionStatus = $data['transactionStatus'];
        $uid = $invoice->dispatching_order_uid;
        $notifyEmail = $clientEmail;
        if ($notifyEmail === '' && $uid) {
            $notifyEmail = (string) (Orderweb::where('dispatching_order_uid', $uid)->value('email') ?? '');
        }

        Log::info('finalizeWalletAddCostAfterCharge notify', [
            'order_reference' => $orderReference,
            'uid' => $uid,
            'transaction_status' => $transactionStatus,
        ]);

        PaymentStatusNotifier::notifyTransactionStatus(
            $transactionStatus,
            $uid,
            $application,
            $notifyEmail
        );

        if ($transactionStatus === 'Approved' || $transactionStatus === 'WaitingAuthComplete') {
            Log::info('finalizeWalletAddCostAfterCharge: processing add-cost', [
                'order_reference' => $orderReference,
                'status' => $transactionStatus,
            ]);

            $addCostResult = $this->processAddCostAfterApproved(
                $orderReference,
                $city,
                $amount,
                $application,
                $chargeResponse
            );

            if (!$returnAddCostResult) {
                $addCostResult = null;
            }
        } elseif (in_array($transactionStatus, ['InProcessing', 'Pending'], true)) {
            Log::info('finalizeWalletAddCostAfterCharge: payment still processing', [
                'order_reference' => $orderReference,
                'status' => $transactionStatus,
            ]);
        } else {
            Log::info('finalizeWalletAddCostAfterCharge: payment not approved', [
                'order_reference' => $orderReference,
                'status' => $transactionStatus,
            ]);
        }

        return [
            'addCostResult' => $addCostResult,
            'responseStatus' => $responseStatus,
        ];
    }

    private function resolveAddCostPayMethod(?Orderweb $order): string
    {
        if ($order !== null && (string) $order->pay_system === 'google_pay_payment') {
            return 'google_pay_payment';
        }

        return 'wfp_payment';
    }

    /**
     * Синхронная обработка доплаты сразу после Approved (chargeActiveTokenAddCost).
     *
     * @return mixed|null
     */
    private function processAddCostAfterApproved(
        string $orderReference,
        string $city,
        $amount,
        string $application,
        $chargeResponse
    ) {
        $wfpInvoice = WfpInvoice::where('orderReference', $orderReference)->first();
        if ($wfpInvoice === null) {
            return null;
        }

        $uid = $wfpInvoice->dispatching_order_uid;
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();

        if ($order === null) {
            Log::warning('Add-cost order not found', [
                'uid' => $uid,
                'order_reference' => $orderReference,
            ]);

            return null;
        }

        if ($order->wfp_order_id === $orderReference && !$this->isAddCostInvoice($wfpInvoice)) {
            Log::info('Add-cost already applied (sync path)', [
                'order_reference' => $orderReference,
                'uid' => $uid,
            ]);
            return null;
        }

        Log::debug('Order lookup by UID for add-cost', [
            'uid' => $uid,
            'order_found' => !is_null($order),
            'order_id' => optional($order)->id,
            'order_server' => optional($order)->server,
        ]);

        $email = $order->email ?? null;

        $applicationFromComment = 'PAS5';
        switch ($order->comment ?? null) {
            case 'taxi_easy_ua_pas1':
                $applicationFromComment = 'PAS1';
                break;
            case 'taxi_easy_ua_pas2':
                $applicationFromComment = 'PAS2';
                break;
            case 'taxi_easy_ua_pas4':
                $applicationFromComment = 'PAS4';
                break;
        }

        if (($order->server ?? null) === 'my_server_api') {
            Log::info('Processing MyTaxi API add cost', [
                'order_reference' => $orderReference,
                'server' => $order->server,
                'application' => $applicationFromComment,
                'amount' => $amount,
            ]);

            return (new MyTaxiApiController)->startAddCostMyApi(
                $order,
                $applicationFromComment,
                $email,
                $amount,
                $chargeResponse
            );
        }

        if (PaymentFlow::normalize($order->payment_flow_mode ?? 0) === PaymentFlow::SIMPLE) {
            Log::info('Processing simple cashless add cost (no fork)', [
                'uid' => $uid,
                'order_reference' => $orderReference,
                'amount' => $amount,
            ]);

            return (new UniversalAndroidFunctionController)->startAddCostSimpleCashless(
                $uid,
                $this->resolveAddCostPayMethod($order),
                $orderReference,
                $city,
                $amount
            );
        }

        $uid_history = Uid_history::where('uid_bonusOrderHold', $uid)->first();
        if (!$uid_history) {
            Log::warning('UID history not found, cannot process universal add cost', [
                'uid' => $uid,
                'order_reference' => $orderReference,
            ]);
            return null;
        }

        return (new UniversalAndroidFunctionController)->startAddCostCardBottomCreat(
            $uid,
            $uid_history->uid_doubleOrder,
            $this->resolveAddCostPayMethod($order),
            $orderReference,
            $city,
            $amount
        );
    }

    /**
     * WayForPay callback для Google Pay: привязать hold к активному заказу,
     * если приложение передало другой orderReference при создании заказа.
     */
    private function syncGooglePayServiceUrlCallback(array $data): void
    {
        if (strtolower((string) ($data['paymentSystem'] ?? '')) !== 'googlepay') {
            return;
        }

        $orderReference = $data['orderReference'] ?? null;
        $transactionStatus = $data['transactionStatus'] ?? null;

        if ($orderReference === null || $orderReference === '' || $transactionStatus === null || $transactionStatus === '') {
            return;
        }

        if (in_array($transactionStatus, ['Voided', 'Refunded'], true)) {
            $this->syncGooglePayVoidOrRefundCallback($data);
            return;
        }

        $email = $data['email'] ?? null;
        $paidStatuses = ['WaitingAuthComplete', 'Approved'];

        if ($email === null || $email === '' || !in_array($transactionStatus, $paidStatuses, true)) {
            return;
        }

        $order = Orderweb::where('email', $email)
            ->where('pay_system', 'google_pay_payment')
            ->whereNull('cancel_timestamp')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->orderByDesc('id')
            ->first();

        if ($order === null) {
            $order = Orderweb::where('email', $email)
                ->where('pay_system', 'google_pay_payment')
                ->where('created_at', '>=', now()->subMinutes(15))
                ->orderByDesc('id')
                ->first();
        }

        $invoiceAmount = $data['amount'] ?? null;
        $invoiceUid = null;
        if ($order !== null) {
            $existingInvoice = WfpInvoice::where('orderReference', $orderReference)->first();
            $isAddCostCallback = $existingInvoice !== null && $this->isAddCostInvoice($existingInvoice);

            // Доплата: не перезаписываем основной wfp_order_id — иначе processAddCostAfterApproved
            // считает доплату уже применённой и заказ не пересоздаётся с новой суммой.
            if (!$isAddCostCallback && $order->wfp_order_id !== $orderReference) {
                $oldOrderReference = $order->wfp_order_id;
                $holdEligibility = new WfpHoldRefundEligibility();
                if (!$holdEligibility->mayRebindGooglePayHold($order, (string) $orderReference, $oldOrderReference)) {
                    Log::info('syncGooglePayServiceUrlCallback: skip rebind wfp_order_id', [
                        'uid' => $order->dispatching_order_uid,
                        'old' => $oldOrderReference,
                        'new' => $orderReference,
                        'transactionStatus' => $transactionStatus,
                    ]);
                } else {
                    Log::info('syncGooglePayServiceUrlCallback: rebind wfp_order_id', [
                        'uid' => $order->dispatching_order_uid,
                        'old' => $oldOrderReference,
                        'new' => $orderReference,
                        'transactionStatus' => $transactionStatus,
                    ]);
                    if ($oldOrderReference !== null && $oldOrderReference !== ''
                        && $holdEligibility->mayVoidSupersededGooglePayHold($order, (string) $oldOrderReference)) {
                        $this->voidSupersededGooglePayHold($order, (string) $oldOrderReference);
                    }
                    $order->wfp_order_id = $orderReference;
                }
            } elseif ($isAddCostCallback) {
                Log::info('syncGooglePayServiceUrlCallback: skip rebind for add-cost invoice', [
                    'uid' => $order->dispatching_order_uid,
                    'main_wfp_order_id' => $order->wfp_order_id,
                    'add_cost_reference' => $orderReference,
                    'transactionStatus' => $transactionStatus,
                ]);
            }

            if (!$isAddCostCallback) {
                $order->wfp_status_pay = $transactionStatus;
                $order->save();
            }

            $invoiceUid = $order->dispatching_order_uid;
            if ($invoiceAmount === null) {
                $invoiceAmount = $order->client_cost ?? $order->web_cost;
            }
        }

        $this->upsertWfpInvoiceRecord(
            $orderReference,
            $invoiceAmount,
            (string) ($data['merchantAccount'] ?? ''),
            $transactionStatus,
            $invoiceUid,
            $data['reason'] ?? null,
            isset($data['reasonCode']) ? (string) $data['reasonCode'] : null
        );
    }

    /**
     * WayForPay callback Google Pay: void/refund — синхронизировать wfp_invoices без email в payload.
     */
    private function syncGooglePayVoidOrRefundCallback(array $data): void
    {
        $orderReference = $data['orderReference'];
        $transactionStatus = $data['transactionStatus'];

        $invoice = WfpInvoice::where('orderReference', $orderReference)->first();
        $invoiceUid = $invoice !== null ? $invoice->dispatching_order_uid : null;

        $order = null;
        if ($invoiceUid !== null && $invoiceUid !== '') {
            $order = Orderweb::where('dispatching_order_uid', $invoiceUid)->first();
        }
        if ($order === null) {
            $order = Orderweb::where('wfp_order_id', $orderReference)->first();
        }

        if ($order !== null) {
            $order->wfp_status_pay = $transactionStatus;
            $order->save();
            if ($invoiceUid === null || $invoiceUid === '') {
                $invoiceUid = $order->dispatching_order_uid;
            }
        }

        $this->upsertWfpInvoiceRecord(
            $orderReference,
            $data['amount'] ?? ($invoice !== null ? $invoice->amount : null),
            (string) ($data['merchantAccount'] ?? ($invoice !== null ? ($invoice->merchantAccount ?? '') : '')),
            $transactionStatus,
            $invoiceUid,
            $data['reason'] ?? null,
            isset($data['reasonCode']) ? (string) $data['reasonCode'] : null
        );

        Log::info('syncGooglePayServiceUrlCallback: synced void/refund', [
            'orderReference' => $orderReference,
            'transactionStatus' => $transactionStatus,
        ]);
    }

    private function voidSupersededGooglePayHold(Orderweb $order, string $oldOrderReference): void
    {
        $invoice = WfpInvoice::where('orderReference', $oldOrderReference)->first();
        if ($invoice === null || $invoice->transactionStatus !== 'WaitingAuthComplete') {
            return;
        }
        if ($this->isAddCostInvoice($invoice)) {
            Log::info('syncGooglePayServiceUrlCallback: skip void for add-cost invoice', [
                'uid' => $order->dispatching_order_uid,
                'orderReference' => $oldOrderReference,
            ]);

            return;
        }

        $application = WfpOrderPaymentContextHelper::resolveApplication($order);
        $city = WfpOrderPaymentContextHelper::resolveCity($order);

        try {
            $this->refund(
                $application,
                $city,
                $oldOrderReference,
                $invoice->amount,
                $order->dispatching_order_uid
            );
            Log::info('syncGooglePayServiceUrlCallback: void superseded hold dispatched', [
                'uid' => $order->dispatching_order_uid,
                'orderReference' => $oldOrderReference,
                'application' => $application,
                'city' => $city,
            ]);
        } catch (\Exception $e) {
            Log::error('syncGooglePayServiceUrlCallback: void superseded hold failed', [
                'uid' => $order->dispatching_order_uid,
                'orderReference' => $oldOrderReference,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function upsertWfpInvoiceRecord(
        string $orderReference,
        $amount,
        string $merchantAccount,
        ?string $transactionStatus = null,
        ?string $uid = null,
        ?string $reason = null,
        ?string $reasonCode = null
    ): WfpInvoice {
        $invoice = WfpInvoice::firstOrNew(['orderReference' => $orderReference]);

        if ($uid !== null && $uid !== '') {
            $invoice->dispatching_order_uid = $uid;
        }
        if ($amount !== null && $amount !== '') {
            $invoice->amount = (string) $amount;
        }
        if ($this->shouldUpdateWfpInvoiceStatus($invoice->transactionStatus, $transactionStatus)) {
            $invoice->transactionStatus = $transactionStatus;
        }
        if ($reason !== null) {
            $invoice->reason = $reason;
        }
        if ($reasonCode !== null) {
            $invoice->reasonCode = $reasonCode;
        }
        if ($merchantAccount !== '') {
            $merchantAccount = trim($merchantAccount);
            if ($merchantAccount !== '' && $invoice->merchantAccount !== $merchantAccount) {
                $invoice->merchantAccount = $merchantAccount;
            }
        }
        $invoice->save();

        return $invoice;
    }
}

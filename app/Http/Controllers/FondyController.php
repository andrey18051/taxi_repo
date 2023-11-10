<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\City;
use App\Models\Orderweb;
use App\Models\User;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FondyController extends Controller
{
    public function successView()
    {
        return view('fondy.success');
    }
    public function errorView()
    {
        return view('fondy.error');
    }
    public function subscriptionView()
    {
        return view('fondy.subscription');
    }
    public function callBack(Request $request)
    {
        Log::debug($request->all);
    }
    public function chargebackCallBack(Request $request)
    {
        Log::debug($request->all);
    }



    public function fondyStatusShowAdmin(): array
    {
        $order = Orderweb::where("closeReason", "!=", null)
            ->where("fondy_order_id", "!=", null)
            ->orderByDesc('created_at')
            ->get();
        $response = null;
//        dd($order->toArray());
        if (!$order->isEmpty()) {
            $i=0;

            foreach ($order->toArray() as $value) {
                if ($value["fondy_status_pay"] == null) {
                    $orderF = Orderweb::where("fondy_order_id", $value["fondy_order_id"])->first();
                    $orderF->fondy_status_pay = self::fondyOrderIdStatus($orderF->fondy_order_id);
                    $orderF->save();
                    $fondy_status_pay = $orderF->fondy_status_pay;
                } else {
                    $fondy_status_pay = $value["fondy_status_pay"];
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
                    'fondy_order_id' => $value["fondy_order_id"],
                    'fondy_status_pay' => $fondy_status_pay,
                    'uid' => $value["dispatching_order_uid"],
                    'reason' => $closeReasonText,
                ];
                $i++;
            }
        }
//        dd($response);
        return $response;
    }

    public function fondyOrderIdStatus($fondy_order_id)
    {
        Log::debug('fondyOrderIdStatus fondy_order_id' . $fondy_order_id);
        $url = "https://pay.fondy.eu/api/status/order_id";
        $params =  array(
            "order_id" => $fondy_order_id,
            "merchant_id" => config('app.merchantId'),
        );

        $signature = self::generateSignature($params);
        $requestData = array(
            "request" => array(
                "order_id" => $fondy_order_id,
                "merchant_id" => config('app.merchantId'),
                "signature" => $signature
            )
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);


        $responseData = $response->json();
        Log::debug('fondyOrderIdStatus responseData' . json_encode($responseData));

        return $responseData['response']['order_status'];
    }

    public function fondyOrderIdReverse($fondy_order_id)
    {
        $order = Orderweb::where("fondy_order_id", $fondy_order_id)->first();

        $url = "https://pay.fondy.eu/api/reverse/order_id";
        $params =  array(
            "order_id" => $fondy_order_id,
            "currency" => "UAH",
            "amount" => $order->web_cost . "00",
            "merchant_id" => config('app.merchantId'),
        );

        $signature = self::generateSignature($params);
        $requestData = array(
            "request" => array(
                "order_id" => $fondy_order_id,
                "currency" => "UAH",
                "amount" => $order->web_cost . "00",
                "merchant_id" => config('app.merchantId'),
                "signature" => $signature
            )
        );
        Log::debug("fondyOrderIdReverse requestData:" . json_encode($requestData));


        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);

// Получаем JSON-ответ
        $responseData = $response->json();
        Log::debug("fondyOrderIdReverse responseData:" . json_encode($responseData));

        return $responseData;
    }
    public function fondyOrderIdCupture($fondy_order_id)
    {
        $order = Orderweb::where("fondy_order_id", $fondy_order_id)->first();

        $url = "https://pay.fondy.eu/api/capture/order_id";
        $params =  array(
            "order_id" => $fondy_order_id,
            "currency" => "UAH",
            "amount" => $order->web_cost . "00",
            "merchant_id" => config('app.merchantId'),
        );

        $signature = self::generateSignature($params);
        $requestData = array(
            "request" => array(
                "order_id" => $fondy_order_id,
                "currency" => "UAH",
                "amount" => $order->web_cost ."00",
                "merchant_id" => config('app.merchantId'),
                "signature" => $signature
            )
        );
//dd($requestData);
        Log::debug("fondyOrderIdCupture requestData:" . json_encode($requestData));
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $requestData);

// Получаем JSON-ответ
        $responseData = $response->json();

        Log::debug("fondyOrderIdCupture responseData:" . json_encode($responseData));
        return $responseData['response']['capture_status'];
    }
    public function fondyStatusReviewAdmin($fondy_order_id)
    {
        $order = Orderweb::where("fondy_order_id", $fondy_order_id)->first();

        $connectAPI =  $order->server;
        $autorization = self::autorization($connectAPI);
        $identificationId = $order->comment;

        $order->closeReason = self::closeReasonUIDStatusFirst(
            $order->dispatching_order_uid,
            $connectAPI,
            $autorization,
            $identificationId
        );

        Log::debug("fondyStatusReviewAdmin order->closeReason:" . strval($order->closeReason));

        switch ($order->closeReason) {
            case "-1":
                break;
            case "0":
            case "8":
                self::fondyOrderIdCupture($fondy_order_id);
                break;
            case "1":
            case "2":
            case "3":
            case "4":
            case "5":
            case "6":
            case "7":
            case "9":
                self::fondyOrderIdReverse($fondy_order_id);
                break;
        }
        $order->fondy_status_pay = self::fondyOrderIdStatus($fondy_order_id);
        $order->save();
    }

    public function fondyStatusReview($bonusOrder, $doubleOrder, $bonusOrderHold): int
    {
        $result = 0;

        $order = Orderweb::where("dispatching_order_uid", $bonusOrderHold)->first();

        $connectAPI =  $order->server;
        $autorization = self::autorization($connectAPI);
        $identificationId = $order->comment;

        $order->closeReason = self::closeReasonUIDStatusFirst(
            $bonusOrder,
            $connectAPI,
            $autorization,
            $identificationId
        );


        $bonusOrderCloseReason = $order->closeReason;
        $doubleOrderCloseReason = self::closeReasonUIDStatusFirst(
            $doubleOrder,
            $connectAPI,
            $autorization,
            $identificationId
        );

        Log::debug("fondyStatusReview order->closeReason:" . strval($order->closeReason));

        switch ($bonusOrderCloseReason) {
            case "-1":
                break;
            case "0":
            case "8":
                self::fondyOrderIdCupture($order->fondy_order_id);
                $result = 1;
                break;
            case "1":
            case "2":
            case "3":
            case "4":
            case "5":
            case "6":
            case "7":
            case "9":
                switch ($doubleOrderCloseReason) {
                    case "-1":
                        break;
                    case "0":
                    case "8":
                        self::fondyOrderIdCupture($order->fondy_order_id);
                        $result = 1;
                        break;
                    case "1":
                    case "2":
                    case "3":
                    case "4":
                    case "5":
                    case "6":
                    case "7":
                    case "9":
                        self::fondyOrderIdReverse($order->fondy_order_id);
                        $result = 1;
                        break;
                }
                break;
        }
        $order->fondy_status_pay = self::fondyOrderIdStatus($order->fondy_order_id);
        $order->save();
        return $result;
    }

    public function closeReasonUIDStatusFirst($uid, $connectAPI, $autorization, $identificationId)
    {
        $url = $connectAPI . '/api/weborders/' . $uid;
        $response = Http::withHeaders([
            "Authorization" => $autorization,
            "X-WO-API-APP-ID" => $identificationId,
        ])->get($url);
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            return $response_arr["close_reason"];
        }
        return "-1";
    }

    public function closeReasonUIDStatus($uid, $connectAPI, $autorization, $identificationId)
    {
        $url = $connectAPI . '/api/weborders/' . $uid;
        $response = Http::withHeaders([
            "Authorization" => $autorization,
            "X-WO-API-APP-ID" => $identificationId,
        ])->get($url);
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);

            $order = Orderweb::where("dispatching_order_uid", $uid)->first();
            $old_order_closeReason = $order->closeReason;

            if ($old_order_closeReason == $response_arr["close_reason"]) {
                $order->closeReasonI += 1;
            } else {
                $order->closeReason = $response_arr["close_reason"];
                $order->closeReasonI = 1;
            }
            $order->save();
        }
    }

    public function handleCallback(Request $request)
    {
        // Проверьте IP-адрес запроса, чтобы убедиться, что это запрос от FONDY
        $allowedIP = '54.154.216.60';
        $clientIP = $request->ip();

        if ($clientIP !== $allowedIP) {
            return response('Access Denied', 403);
        }
        Log::debug('handleCallback request->getContent(): ' . $request->getContent());

        $data = json_decode($request->getContent(), true);
        Log::debug($data['sender_email']);
        Log::debug($data['rectoken']);

        $user = User::where('email', $data['sender_email'])->first();

        if ($user) {
            $additionalInfo = json_decode($data['additional_info'], true);

            if (isset($additionalInfo['card_type'], $additionalInfo['bank_name'])) {
                $cardType = $additionalInfo['card_type'];
                $bankName = $additionalInfo['bank_name'];

                $card = Card::where('pay_system', 'fondy')
                    ->where('user_id', $user->id)
                    ->where('rectoken', $data['rectoken'])
                    ->first();

                if (!$card) {
                    $card = new Card();
                    $card->user_id = $user->id;
                }

                $card->pay_system = 'fondy';
                $card->masked_card = $data['masked_card'];
                $card->card_type = $cardType;
                $card->bank_name = $bankName;
                $card->rectoken = $data['rectoken'];
                $card->rectoken_lifetime = $data['rectoken_lifetime'];

                $card->save();
            }
        }

        // Ответ на callback
        return response('OK', 200);
    }



    public function generateSignature($params)
    {
        // Сортируем параметры по ключам (алфавитный порядок)
        ksort($params);

        // Создаем строку для подписи
        $signatureData = config('app.merchantPassword'); // Добавляем пароль мерчанта

        // Добавляем все отсортированные параметры, разделенные символом вертикальной черты
        foreach ($params as $value) {
            $signatureData .= '|' . $value;
        }

        try {
            // Вычисляем SHA-1 хеш
            $digest = sha1($signatureData);

            return $digest;
        } catch (Exception $e) {
            // Обработка ошибки
            return null;
        }
    }

    private function autorization($connectApi)
    {

        $city = City::where('address', str_replace('http://', '', $connectApi))->first();

        $username = $city->login;
        $password = hash('SHA512', $city->password);

        return 'Basic ' . base64_encode($username . ':' . $password);
    }


}

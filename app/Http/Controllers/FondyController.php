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

        // Проверяем наличие ключа 'response' в ответе
        if (isset($responseData['response'])) {
            // Проверяем наличие ключа 'order_status' внутри 'response'
            if (isset($responseData['response']['order_status'])) {
                return $responseData['response']['order_status'];
            } else {
                // Если ключ 'order_status' отсутствует, возвращаем ошибку или другое значение по умолчанию
                return 'Unknown'; // Можно заменить на другое значение или бросить исключение
            }
        } else {
            // Если ключ 'response' отсутствует, значит произошла ошибка во время запроса к API Fondy
            Log::error('fondyOrderIdStatus: Error in response from Fondy API');
            return 'Error'; // Можно заменить на другое значение или бросить исключение
        }
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

    private function fondyOrderIdCuptureCost($fondy_order_id, $cost)
    {
        $url = "https://pay.fondy.eu/api/capture/order_id";
        $params =  array(
            "order_id" => $fondy_order_id,
            "currency" => "UAH",
            "amount" => $cost . "00",
            "merchant_id" => config('app.merchantId'),
        );

        $signature = self::generateSignature($params);
        $requestData = array(
            "request" => array(
                "order_id" => $fondy_order_id,
                "currency" => "UAH",
                "amount" => $cost ."00",
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
        )["close_reason"];

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

        $holdOrderCost = $order->web_cost;

        $uidBonus = self::closeReasonUIDStatusFirst(
            $bonusOrder,
            $connectAPI,
            $autorization,
            $identificationId
        );

        $bonusOrderCloseReason = $uidBonus["close_reason"];
        $bonusOrderCost = $uidBonus["order_cost"];

        $uidDouble = self::closeReasonUIDStatusFirst(
            $doubleOrder,
            $connectAPI,
            $autorization,
            $identificationId
        );

        $doubleOrderCloseReason = $uidDouble["close_reason"];
        $doubleOrderCost =  $uidDouble["order_cost"];


        Log::debug("fondyStatusReview order->closeReason:" . strval($order->closeReason));

        switch ($bonusOrderCloseReason) {
            case "-1":
                break;
            case "0":
            case "8":
                switch ($doubleOrderCloseReason) {
                    case "-1":
                    case "1":
                    case "2":
                    case "3":
                    case "4":
                    case "5":
                    case "6":
                    case "7":
                    case "9":
                        if ($holdOrderCost >= $bonusOrderCost) {
                            self::fondyOrderIdCuptureCost($order->fondy_order_id, $bonusOrderCost);
                        } else {
                            self::fondyOrderIdCuptureCost($order->fondy_order_id, $holdOrderCost);
                        }
                        $result = 1;
                        break;
                    case "0":
                    case "8":
                        if ($bonusOrderCost >= $doubleOrderCost) {
                            if ($holdOrderCost >= $bonusOrderCost) {
                                self::fondyOrderIdCuptureCost($order->fondy_order_id, $bonusOrderCost);
                            } else {
                                self::fondyOrderIdCuptureCost($order->fondy_order_id, $holdOrderCost);
                            }
                        } else {
                            if ($holdOrderCost >= $doubleOrderCost) {
                                self::fondyOrderIdCuptureCost($order->fondy_order_id, $doubleOrderCost);
                            } else {
                                self::fondyOrderIdCuptureCost($order->fondy_order_id, $holdOrderCost);
                            }
                        }
                        $result = 1;
                        break;
                }
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
                        if ($holdOrderCost >= $doubleOrderCost) {
                            self::fondyOrderIdCuptureCost($order->fondy_order_id, $doubleOrderCost);
                        } else {
                            self::fondyOrderIdCuptureCost($order->fondy_order_id, $holdOrderCost);
                        }
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

        $order->closeReason = $bonusOrderCloseReason;
        $order->fondy_status_pay = self::fondyOrderIdStatus($order->fondy_order_id);
        $order->save();
        return $result;
    }

    private function closeReasonUIDStatusFirst($uid, $connectAPI, $autorization, $identificationId)
    {
        $url = $connectAPI . '/api/weborders/' . $uid;
        $response = Http::withHeaders([
            "Authorization" => $autorization,
            "X-WO-API-APP-ID" => $identificationId,
        ])->get($url);
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            Log::debug('closeReasonUIDStatusFirst ' . json_encode($response_arr));

            return $response_arr;
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
//        $allowedIP = '54.154.216.60';
//        $clientIP = $request->ip();
//
//        if ($clientIP !== $allowedIP) {
//            return response('Access Denied', 403);
//        }
//        Log::debug('handleCallback request->getContent(): ' . $request->getContent());

        $data = json_decode($request->getContent(), true);
        Log::debug($data['sender_email']);
        Log::debug($data['rectoken']);

        $user = User::where('email', $data['sender_email'])->first();

        if ($user && isset($data['rectoken']) && !empty($data['rectoken'])) {
            $additionalInfo = json_decode($data['additional_info'], true);

            if (isset($additionalInfo['card_type'], $additionalInfo['bank_name'])) {
                $cardType = $additionalInfo['card_type'];
                if ($additionalInfo['bank_name'] != null) {
                    $bankName = $additionalInfo['bank_name'];
                } else {
                    $bankName = " ";
                }

                $card = Card::where('pay_system', 'fondy')
                    ->where('user_id', $user->id)
                    ->where('rectoken', $data['rectoken'])
                    ->where('merchant', $data['merchant_id'])
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
                $card->merchant = $data['merchant_id'];
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

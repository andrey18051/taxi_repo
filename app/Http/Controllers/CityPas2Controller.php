<?php

namespace App\Http\Controllers;

use App\Helpers\ConnectionErrorHandler;
use App\Mail\Check;
use App\Models\City_PAS2;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Diff\Exception;

class CityPas2Controller extends Controller
{
//    public function cityAdd(Request $request)
//    {
//        $city = new City_PAS2();
//        $city->name = $request->input('name');
//        $city->address = $request->input('address');
//        $city->login = $request->input('login');
//        $city->password = $request->input('password');
//        $city->password = "true";
//        $city->save();
//    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        return response()->json(City_PAS2::get());
    }


    public function edit(
        $id,
        $name,
        $address,
        $login,
        $password,
        $online,
        $card_max_pay,
        $bonus_max_pay,
        $black_list
    ) {
        $city = City_PAS2::find($id);

        if (!$city) {
            // Обработка ошибки, если город не найден
            return response()->json(['error' => 'Город не найден'], 404);
        }

        $city->name = $name;
        $city->address = $address;
        $city->login = $login;
        $city->password = $password;
        $city->online = $online;
        $city->card_max_pay = $card_max_pay;
        $city->bonus_max_pay = $bonus_max_pay;
        $city->black_list = $black_list;
        $city->save();

        return response()->json($city);
    }

    public function destroy($id)
    {
        City_PAS2::find($id)->delete();
    }

    public function cityCreat(Request $req)
    {
        $city = new City_PAS2();

        $city->name = $req->name;
        $city->address = $req->address;
        $city->login = $req->login;
        $city->password = $req->password;
        $city->online = "true";
        $city->save();
        return redirect()->route('admin');
    }

    public function newCityCreat()
    {
        $city = new City_PAS2();

        $city->name = "";
        $city->address = "";
        $city->login = "";
        $city->password = "";
        $city->online = "true";
        $city->save();
    }

    public function cityAll($city): array
    {
        return City_PAS2::where('name', $city)->get()->toArray();
    }

    /**
     * @throws \Exception
     */
    public function cityOnline($city)
    {
        $serverArr = self::cityAll($city);

        foreach ($serverArr as $value) {
            $timeFive = self::hasPassedFiveMinutes($value['updated_at']);
            $checking = self::checkDomain($value["address"]);
            $online = $value["online"];

            $city = City_PAS2::where('address', $value["address"])->first();

            if ($online === "false" && $timeFive && $checking) {
                $city->online = "true";
                $city->save();
                return "http://" . $value["address"];
            }

            if (($online === "true" && !$checking) || ($online === "false" && $timeFive && !$checking)) {
                $city->online = "false";
                $city->save();

//                $alarmMessage = new TelegramController();
//                $client_ip = $_SERVER['REMOTE_ADDR'];
//                $messageAdmin = "Нет подключения к серверу города {$city->name->name} http://{$value['address']}. IP $client_ip";
//
//                Log::debug($messageAdmin);
//                $isCurrentTimeInRange = (new UniversalAndroidFunctionController)->isCurrentTimeInRange();
//                if (!$isCurrentTimeInRange) {
//                    $cacheKey = 'alarm_message_' . md5($messageAdmin);
//                    $lock = Cache::lock($cacheKey, 300); // Lock for 5 minutes (300 seconds)
//
//                    if ($lock->get()) {
//                        try {
//                            $alarmMessage->sendAlarmMessage($messageAdmin);
//                            $alarmMessage->sendMeMessage($messageAdmin);
//                        } catch (Exception $e) {
//                            $paramsCheck = [
//                                'subject' => 'Ошибка в телеграмм',
//                                'message' => $e->getMessage(),
//                            ];
//                            Mail::to('taxi.easy.ua.sup@gmail.com')->send(new Check($paramsCheck));
//                        } finally {
//                            $lock->release();
//                        }
//                    }
//                }
                $handler = new ConnectionErrorHandler();
                $handler->handleConnectionError($city, $value, $_SERVER['REMOTE_ADDR'], $checking, $online, $timeFive);
            }

            if ($online === "true" && $checking) {
                return "http://" . $value["address"];
            }
        }

        return 400;
    }





    /**
     * @throws \Exception
     */
    public function cityOnlineOrder($city)
    {
//Разморозка
        $serverFalse = City_PAS2::where('name', $city)
            ->where('online', "false")->get();
        Log::debug("cityOnlineOrder serverFalse: " . json_encode($serverFalse));
        if (!$serverFalse->isEmpty()) {
            $serverArr = $serverFalse->toArray();

            foreach ($serverArr as $value) {
                $timeFive = self::hasPassedFiveMinutes($value['updated_at']);

                if ($timeFive) {
                    $cityRecord = City_PAS2::find($value["id"]);

                    if ($cityRecord) {
                        Log::debug("cityOnlineOrder city: " . json_encode($cityRecord));

                        $cityRecord->online = "true";
                        $cityRecord->save();
                    } else {
                        Log::warning("cityOnlineOrder: City record with id " . $value["id"] . " not found.");
                    }
                }
            }
        }

 //Получение доступного сервера

        $server = City_PAS2::where('name', $city)
            ->where('online', "true")->first();


        Log::debug("cityOnlineOrder" . $server);
        if (isset($server) && $server != null) {
            $serverArr = $server->toArray();

            Log::debug("cityOnlineOrder". "http://" . $serverArr["address"]);

            return "http://" . $server["address"];
        } else {
            return 400;
        }
    }


    /**
     * @throws \Exception
     */
    public function checkDomain($domain): bool
    {

        $domainFull = "http://" . $domain . "/api/version";
        Log::debug("checkDomain: " . $domainFull);
//        $curlInit = curl_init($domainFull);
//        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 2);
//        curl_setopt($curlInit, CURLOPT_HEADER, true);
//        curl_setopt($curlInit, CURLOPT_NOBODY, true);
//        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
//
//        $response = curl_exec($curlInit);

        $curlInit = curl_init($domainFull);
        curl_setopt_array($curlInit, array(
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ));
        $response = curl_exec($curlInit);
//dd($domainFull);
        $city = City_PAS2::where('address', $domain)->first();
        if (curl_errno($curlInit)) {

            return false;
        } else {

            return true;
        }

        curl_close($curlInit);
//        if ($response) {
//            $city = City_PAS2::where('address', $domain)->first();
//            $city->online = "true";
//            $city->save();
//            return true;
//        } else {
//            $city = City_PAS2::where('address', $domain)->first();
//            $city->online = "false";
//            $city->save();
//            return false;
//        }
    }

    public function checkDomains()
    {
        $city = City_PAS2::all()->toArray();
//dd($city);
        foreach ($city as $value) {
            $domainFull = "http://" . $value['address'] . "/api/time";

            $curlInit = curl_init($domainFull);
            curl_setopt_array($curlInit, array(
                CURLOPT_CONNECTTIMEOUT => 7,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ));
            $response = curl_exec($curlInit);
            if (curl_errno($curlInit) || !$response) {
                $alarmMessage = new TelegramController();
                $name = $value['name'];
                $messageAdmin = "Ошибка подключения к серверу города $name http://" . $value['address'] . ".";
                try {
                    $alarmMessage->sendAlarmMessage($messageAdmin);
//                    $alarmMessage->sendMeMessage($messageAdmin);
                } catch (Exception $e) {
                    $paramsCheck = [
                        'subject' => 'Ошибка в телеграмм',
                        'message' => $e,
                    ];
                    Mail::to('taxi.easy.ua.sup@gmail.com')->send(new Check($paramsCheck));
                };
                return false;
            }
            curl_close($curlInit);
        }

    }

    /**
     * @throws \Exception
     */
    protected function hasPassedFiveMinutes($updated_at): bool
    {
        $updated_at_datetime = new DateTimeImmutable($updated_at);
        $current_datetime = new DateTimeImmutable();

        $interval = $current_datetime->diff($updated_at_datetime);

        return ($interval->i >= 5);
    }

    public function versionAPICitiesUpdate()
    {
        $cities = City_PAS2::all()->toArray();
        foreach ($cities as $value) {
            $city = City_PAS2::where('name', $value["name"])->first();
            $url = "http://" . $city->address . '/api/version';
            $response = Http::get($url);
            $response_arr = json_decode($response, true);

            $city->versionApi = $response_arr["version"];
            $city->save();
        }
    }

    public function apiVersion($connectAPI)
    {
        $url = $connectAPI . '/api/version';
        $response = Http::get($url);
        $response_arr = json_decode($response, true);
    }

    public function maxPayValue($city): array
    {
        $city = City_PAS2::where('name', $city)->first()->toArray();

        return [
            'card_max_pay' => $city["card_max_pay"],
            'bonus_max_pay' => $city["bonus_max_pay"]
        ];
    }
    public function merchantFondy($city): array
    {
        $city = City_PAS2::where('name', $city)->first()->toArray();

        return [
            'card_max_pay' => $city["merchant_fondy"],
            'bonus_max_pay' => $city["fondy_key_storage"]
        ];
    }
}

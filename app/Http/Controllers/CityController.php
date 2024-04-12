<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Mail\Server;
use App\Models\City;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Diff\Exception;

class CityController extends Controller
{
//    public function cityAdd(Request $request)
//    {
//        $city = new City();
//        $city->name = $request->input('name');
//        $city->address = $request->input('address');
//        $city->login = $request->input('login');
//        $city->password = $request->input('password');
//        $city->password = "true";
//        $city->save();
//    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        return response()->json(City::get());
    }


    public function edit(
        $id,
        $name,
        $address,
        $login,
        $password,
        $online,
        $card_max_pay,
        $bonus_max_pay
    ) {
        $city = City::find($id);

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
        $city->save();

        return response()->json($city);
    }

    public function destroy($id)
    {
        City::find($id)->delete();
    }

    public function cityCreat(Request $req)
    {
        $city = new City();

        $city->name = $req->name;
        $city->address = $req->address;
        $city->login = $req->login;
        $city->password = $req->password;
        $city->online = "true";
        $city->save();
        return redirect()->route('admin');
    }

    public function cityAll($city): array
    {
        return City::where('name', $city)->get()->toArray();
    }

    /**
     * @throws \Exception
     */
    public function cityOnline($city)
    {

        $serverArr = self::cityAll($city);
//dd($serverArr);
        foreach ($serverArr as $value) {
            $timeFive = self::hasPassedFiveMinutes($value['updated_at']);
            $checking = self::checkDomain($value["address"]);
            $online = $value["online"];

            $city = City::where('address', $value["address"])->first();
//            dd($value["address"]);
//            dd( $timeFive);
            if ($online == "false") {
                if ($timeFive == true) {
                    if ($checking == true) {
                        $city->online = "true";
                        $city->save();
                        return "http://" . $value["address"];
                    } else {
//                        $city->online = "true";
//                        $city->save();
                        $city->online = "false";
                        $city->save();
                        $alarmMessage = new TelegramController();
                        $messageAdmin = "Нет подключения к серверу города $city->name http://" . $value["address"]. ".";
                        try {
                            $alarmMessage->sendAlarmMessage($messageAdmin);
                            $alarmMessage->sendMeMessage($messageAdmin);
                        } catch (Exception $e) {
                            $paramsCheck = [
                                'subject' => 'Ошибка в телеграмм',
                                'message' => $e,
                            ];
                            Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
                        };
                    }
                }
            }
            if ($online == "true") {
                if ($checking == true) {
                    return "http://" . $value["address"];
                } else {
                    $city->online = "false";
                    $city->save();
                    $alarmMessage = new TelegramController();
                    $messageAdmin = "Нет подключения к серверу города $city->name http://" . $value["address"]. ".";
                    try {
                        $alarmMessage->sendAlarmMessage($messageAdmin);
                        $alarmMessage->sendMeMessage($messageAdmin);
                    } catch (Exception $e) {
                        $paramsCheck = [
                            'subject' => 'Ошибка в телеграмм',
                            'message' => $e,
                        ];
                        Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
                    };
                }
            }


        }
        return 400;
    }


    /**
     * @throws \Exception
     */
    public function checkDomain($domain): bool
    {

        $domainFull = "http://" . $domain . "/api/version";
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
        $city = City::where('address', $domain)->first();
        if (curl_errno($curlInit)) {

            return false;
        } else {

            return true;
        }

        curl_close($curlInit);
//        if ($response) {
//            $city = City::where('address', $domain)->first();
//            $city->online = "true";
//            $city->save();
//            return true;
//        } else {
//            $city = City::where('address', $domain)->first();
//            $city->online = "false";
//            $city->save();
//            return false;
//        }
    }

    public function checkDomains()
    {
        $city = City::all()->toArray();
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
                    Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
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
        $cities = City::all()->toArray();
        foreach ($cities as $value) {
            $city = City::where('name', $value["name"])->first();
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
        $city = City::where('name', $city)->first();

        return [
            'card_max_pay' => $city->card_max_pay,
            'bonus_max_pay' => $city->bonus_max_pay
            ];
    }
    public function merchantFondy($city): array
    {
        $city = City::where('name', $city)->first();

        return [
            'merchant_fondy' => $city->merchant_fondy,
            'fondy_key_storage' => $city->fondy_key_storage
        ];
    }
}

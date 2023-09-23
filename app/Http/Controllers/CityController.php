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


    public function edit($id, $name, $address, $login, $password, $online)
    {
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
                        $city->online = "true";
                        $city->save();
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

//    protected function hasPassedThirtyMinutesOrFiveMinutes($updated_at): bool
//    {
//        // Создаем объект DateTimeImmutable для $updated_at
//        $updated_at_datetime = new DateTimeImmutable($updated_at);
//
//        // Получаем текущую дату и время в киевском времени
//        $current_datetime = new DateTimeImmutable(null, new DateTimeZone('Europe/Kiev'));
//
//        // Получаем текущее время в минутах с начала суток
//        $current_minutes = $current_datetime->format('H') * 60 + $current_datetime->format('i');
//
//        // Устанавливаем начальное и конечное время интервала
//        $start_time = $current_datetime->setTime(0, 0);
//        $end_time = $current_datetime->setTime(5, 0);
//
//        // Проверяем, находится ли $updated_at внутри интервала с 00:00 до 05:00
//        if ($updated_at_datetime >= $start_time && $updated_at_datetime <= $end_time) {
//            return true;
//        }
//
//        // Если текущее время находится в интервале с 00:00 до 05:00, то возвращаем false (5 минут)
//        if ($current_minutes >= 0 && $current_minutes < 300) {
//            return false;
//        }
//
//        // В остальных случаях возвращаем true (30 минут)
//        return true;
//    }


    /**
     * @throws \Exception
     */
    protected function hasPassedFive($updated_at): bool
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

}

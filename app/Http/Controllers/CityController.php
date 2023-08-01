<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Mail\Server;
use App\Models\City;
use DateTimeImmutable;
use Illuminate\Http\Request;
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
        $city  = new City();

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

    public function cityOffline($city)
    {
        $serverArrAll = self::cityAll($city);

        foreach ($serverArrAll as $value) {
            if (self::hasPassedFiveMinutes($value['updated_at'])) {
                self::checkDomain($value["address"]);
            }
        }


        return City::where('online', "false")->
        where('name', $city)->get()->toArray();
    }

    public function cityOnline($city)
    {

        $serverArrOffline = self::cityOffline($city);
        if (!empty($serverArrOffline)) {
            foreach ($serverArrOffline as $value) {
                if (self::hasPassedFiveMinutes($value["updated_at"])) {
                    $city = City::where('address', $value["address"])->first();
                    $city->online = "true";
                    $city->save();
                }
            }
        }
        $server = City::where('online', "true")-> where('name', $city)->first();

        if ($server != null) {
            return "http://" .  $server->address;
        } else {
            return 400;
        }
    }


    /**
     * @throws \Exception
     */
    private function checkDomain($domain)
    {
        $city = City::where('address', $domain)->first();
        if (self::hasPassedFiveMinutes($city->updated_at)) {
            $domainFull = "http://" . $domain . "/api/time";
            $curlInit = curl_init($domainFull);
            curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($curlInit, CURLOPT_HEADER, true);
            curl_setopt($curlInit, CURLOPT_NOBODY, true);
            curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($curlInit);
            curl_close($curlInit);
            if ($response) {
                $city->online = "true";
                $city->save();

            } else {
                $city->online = "false";
                $city->save();
                $alarmMessage = new TelegramController();
                $messageAdmin = "Ошибка подключения к серверу " . "http://" . $domain . ".";
                $paramsAdmin = [
                    'subject' => "Ошибка подключения к серверу",
                    'message' => "Ошибка подключения к серверу " . "http://" . $domain . "."
                ];

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
}

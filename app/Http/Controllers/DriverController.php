<?php

namespace App\Http\Controllers;

use App\Mail\Driver;
use App\Mail\JobDriver;
use App\Mail\Server;
use App\Models\Autos;
use App\Models\DriverHistory;
use App\Models\Drivers;
use App\Models\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class DriverController extends Controller
{
    public function index(): int
    {
        return 200;
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
            'color' => "Колор: " . $color,
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
            . "Колор: " . $color . ". "
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
//                        'color' => "Колор: " . $color,
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
//                        . "Колор: " . $color . ". "
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

}

<?php

namespace App\Http\Controllers;

use App\Mail\Driver;
use App\Mail\JobDriver;
use App\Mail\Server;
use App\Models\Autos;
use App\Models\Drivers;
use App\Models\Services;
use Illuminate\Http\Request;
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


        $keywords = preg_split("/[*]+/", $services);

        $services = Services::all()->toArray();

        foreach ($keywords as $value_key) {
            foreach ($services as $value_serv) {
                if ($value_key == $value_serv['name']) {
                    $subject = "Прошу розглянути мою кандидатуру для роботи водієм в службі таксі " . $value_serv['name'] . ".";
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
                        'number' => "Державий номер: " . $number
                    ];

                    Mail::to($value_serv['email'])->send(new JobDriver($params));
                }
            }
        }
    }
}

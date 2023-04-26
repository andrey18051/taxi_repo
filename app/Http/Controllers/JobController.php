<?php

namespace App\Http\Controllers;

use App\Mail\Admin;
use App\Mail\Driver;
use App\Rules\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class JobController extends Controller
{
    public function index()
    {
        return view("driver.callWork");
    }

    public function getInfo(Request $req)
    {
        $services = null;

        if ($req->Terminal == "on") {
            $params["Terminal"] = $req->Terminal;
            $services = $services . "Терминал*";
        }
        if ($req->TaxiEasyUa == "on") {
            $params["TaxiEasyUa"] = $req->TaxiEasyUa;
            $services = $services . "Таксі Лайт Юа*";
        }
        if ($req->UBER == "on") {
            $params["UBER"] = $req->UBER;
            $services = $services . "UBER*";
        }
        if ($req->UKLON == "on") {
            $params["UKLON"] = $req->UKLON;
            $services = $services . "UKLON*";
        }
        if ($req->BOLT == "on") {
            $params["BOLT"] = $req->BOLT;
            $services = $services . "BOLT*";
        }
        if ($req->OnTaxi == "on") {
            $params["OnTaxi"] = $req->OnTaxi;
            $services = $services . "OnTaxi*";
        }
        if ($req->taxi_838 == "on") {
            $params["taxi_838"] = $req->taxi_838;
            $services = $services . "838*";
        }
        if ($req->Lubimoe_Taxi == "on") {
            $params["Lubimoe_Taxi"] = $req->Lubimoe_Taxi;
            $services = $services . "Lubimoe Taxi*";
        }
        if ($req->taxi_3040 == "on") {
            $params["taxi_3040"] = $req->taxi_3040;
            $services = $services . "3040*";
        }
        if ($req->Maxim == "on") {
            $params["Maxim"] = $req->Maxim;
            $services = $services . "Максім*";
        }

        $message_error = "Надсилання заявки неможливе. Не заповнені поля: ";
        if (!isset($req->brand)) {
            $message_error = $message_error . "Марка ";
        }
        if (!isset($req->model)) {
            $message_error = $message_error . "Модель ";
        }

        if (!isset($req->color)) {
            $message_error = $message_error . "Колір ";
        }
        if (!isset($req->year)) {
            $message_error = $message_error . "Рік випуску ";
        }

        if (!isset($req->number)) {
            $message_error = $message_error . "Державний номер ";
        }
        if (!isset($req->first_name)) {
            $message_error = $message_error . "Ім'я ";
        }
        if (!isset($req->phone)) {
            $message_error = $message_error . "Телефон";
        }
        if ($services == null) {
            $message_error = $message_error . ' Повинна бути обрана хоча б одна служба таксі.';
        }
        $params["brand"] = $req->brand;
        $brand = $req->brand;
        $params["model"] = $req->model;
        $model = $req->model;
        $params["type"] = $req->type;
        $type = $req->type;
        $params["color"] = $req->color;
        $color = $req->color;
        $params["year"] = $req->year;
        $year = $req->year;
        $params["number"] = $req->number;
        $number = $req->number;
        $params["city"] = $req->city;
        $city = $req->city;
        $params["first_name"] = $req->first_name;
        $first_name = $req->first_name;
        if (isset($req->second_name)) {
            $params["second_name"] = $req->second_name;
            $second_name = $req->second_name;
        } else {
            $second_name = "не вказано";
        }
        if (isset($req->email)) {
            $params["email"] = $req->email;
            $email = $req->email;
        } else {
            $email = "не вказано";
        }
        $params["phone"] = $req->phone;
        $phone = $req->phone;


        if ($message_error != "Надсилання заявки неможливе. Не заповнені поля: ") {
            return view('driver.callWork', ['params' => $params,
                'info' => $message_error]);
        }

        $error = true;
        $secret = config('app.RECAPTCHA_SECRET_KEY');

        if (!empty($_GET['g-recaptcha-response'])) { //проверка на робота
            $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, 'secret=' . $secret . '&response=' . $_GET['g-recaptcha-response']);
            $out = curl_exec($curl);
            curl_close($curl);

            $out = json_decode($out);
            if ($out->success == true) {
                if ($email != "не вказано") {
                    $subject = 'Анкета водія';
                    $message = "Доброго часу доби, " . $params['first_name'] . "!" .
                        "Вашу заявку відправлено до вибраних Вами служб таксі. Чекайте на дзвінок або відповідь на вказану Вами електронну пошту. Будемо раді бачити Вас у нашій команді професіоналів.";
                    $params = [
                    'email' => $email,
                    'subject' => $subject,
                    'message' => $message,
                    ];
                    Mail::to($params["email"])->send(new Driver($params));
                }

                $url = "https://m.easy-order-taxi.site/api/driverAuto/" .
                    $city . "/" .
                    $first_name . "/" .
                    $second_name . "/" .
                    $email . "/" .
                    $phone . "/" .
                    $brand . "/" .
                    $model . "/" .
                    $type . "/" .
                    $color . "/" .
                    $year . "/" .
                    $number . "/" .
                    $services;

                Http::get($url);

                return redirect()->route('home-news')->with('success', $first_name .
                    "! Вашу заявку відправлено до вибраних Вами служб таксі. Чекайте на дзвінок або відповідь на вказану Вами електронну пошту. Будемо раді бачити Вас у нашій команді професіоналів.")
                     ->with('tel', "Для уточнення чекайте/або наберіть диспетчера:");

            }
        }
        if ($error) {
            return view('driver.callWork', ['params' => $params,
                'info' => 'Не пройдено перевірку на робота.']);
        }
    }
}

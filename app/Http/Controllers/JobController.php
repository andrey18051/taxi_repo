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

        if ($services == null) {
            return view('driver.callWork', ['params' => $params,
                'info' => 'Повинна бути обрана хоча б одна служба таксі.']);
        }
        $params["brand"] = $req->brand;
        $params["model"] = $req->model;
        $params["type"] = $req->type;
        $params["color"] = $req->color;
        $params["year"] = $req->year;
        $params["number"] = $req->number;
        $params["city"] = $req->city;
        $params["first_name"] = $req->first_name;
        if (isset($req->second_name)) {
            $params["second_name"] = $req->second_name;
            $second_name = $req->second_name;
        } else {
            $second_name = "не вказано";
        }
        if (isset($req->second_name)) {
            $params["email"] = $req->second_name;
            $email = $req->email;
        } else {
            $email = "не вказано";
        }
        $params["phone"] = $req->phone;


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
                $params["city"] . "/" .
                $params["first_name"] . "/" .
                $second_name . "/" .
                    $email . "/" .
                    $params["phone"] . "/" .
                    $params["brand"] . "/" .
                    $params["model"] . "/" .
                    $params["type"] . "/" .
                    $params["color"] . "/" .
                    $params["year"] . "/" .
                    $params["number"] . "/" .
                    $services;

                Http::get($url);

                return redirect()->route('home-news')->with('success', $params['first_name'] .
                    "!. Вашу заявку відправлено до вибраних Вами служб таксі. Чекайте на дзвінок або відповідь на вказану Вами електронну пошту. Будемо раді бачити Вас у нашій команді професіоналів.")
                     ->with('tel', "Для уточнення чекайте/або наберіть диспетчера:");

            }
        }
        if ($error) {
            return view('driver.callWork', ['params' => $params,
                'info' => 'Не пройдено перевірку на робота.']);
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TaxiController extends Controller
{
    /**
     * @return string
     */
    public function version()
    {
        $response = Http::get('http://31.43.107.151:7303/api/version');
        return $response->body();
    }

    /**
     * @return string
     */
    public function profile()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,
            ])->get('http://31.43.107.151:7303/api/clients/profile');
        return $response->body();
    }

    /**
     * @return string
     */
    public function addresses()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/client/addresses');
        return $response->body();
    }

    /**
     * @return string
     */
    public function lastaddresses()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization, ])->get('http://31.43.107.151:7303/api/clients/lastaddresses');
        return $response->body();
    }

    /**
     * @return string
     */
    public function tariffs()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/tariffs');
        return $response->body();
    }

    /**
     * @return string
     */
    public function ordershistory()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get('http://31.43.107.151:7303/api/clients/ordershistory');
        return $response->body();
    }

    /**
     * @return string
     */
    public function ordersreport()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,
            ])-> accept('application/json')->asForm()->get('http://31.43.107.151:7303/api/clients/ordersreport', [
             'dateFrom' => '2013.08.13',
             'dateTo' => '2013.08.13',
        ]);
        return $response->body();
    }

    /**
     * @return string
     */
    public function bonusreport()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,
            ])->get('http://31.43.107.151:7303/api/clients/bonusreport');
        return $response->body();
    }

    /**
     * @return int
     */
    public function profileput()
    {
        $username = '0936734488';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,])->put('http://31.43.107.151:7303/api/clients/profile', [
            'patch' => 'name, address',
            'user_first_name' => 'Mykyta',
            'user_middle_name' => 'Andriyovich',
            'user_last_name' => 'Korzhov',
            'route_address_from' => 'Scince avenu',
            'route_address_number_from' => '4B',
            'route_address_entrance_from' => '12',
            'route_address_apartment_from' => '1',
            ]);
        return $response->status();
    }

    /**
     * @return int
     */
    public function sendConfirmCode()
    {

        $response = Http::accept('application/json')->post('http://31.43.107.151:7303/api/account/register/sendConfirmCode', [
                'phone' => '0936734455',
            ]);
        return $response->body();
    }

    /**
     * @return string
     */
    public function register()
    {
             $response = Http::accept('application/json')->post('http://31.43.107.151:7303/api/account/register', [
            'phone' => '0936734455',
            'confirm_code' => '9183',
            'password' => '11223344',
            'confirm_password' => '11223344',
            'user_first_name' => 'Sergii',
        ]);
        return $response->body();
    }

    /**
     * @return string
     */
    public function cost()
    {
        $username = '0936734455';
        $password = hash('SHA512', '11223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);

        $response = Http::withHeaders([
            'Authorization' => $authorization,

            ])->post('http://31.43.107.151:7303/api/weborders/cost', [
            'user_full_name' => 'Иванов Александр',
            'user_phone' => '',
            'client_sub_card' => null,
            'required_time' => null,
            'reservation' => false,
            'route_address_entrance_from' => null,
            'comment' => '',
            'add_cost' => 12.0,
            'wagon' => false,
            'minibus' => false,
            'premium' => false,
            'flexible_tariff_name' => 'Базовый',
            'baggage' => false,
            'animal' => false,
            'conditioner' => true,
            'courier_delivery' => false,
            'route_undefined' => false,
            'terminal' => false,
            'receipt' => false,
            'route' => [
                ['name' => 'Казино Афина Плаза (Греческая пл. 3/4)'],
                ['name' => 'Казино Кристал (ДЕВОЛАНОВСКИЙ СПУСК 11)'],
            ],
            'taxiColumnId' => 0,
        ]);

        return $response->body() ;
    }
}

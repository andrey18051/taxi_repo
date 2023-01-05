<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Confirmation extends Controller
{
    /**
     * Верификация телефона
     * Получение кода подтверждения
     * @return string
     */
    public function approvedPhonesSendConfirmCode($phone)
    {
        $url = config('app.taxi2012Url') . '/api/approvedPhones/sendConfirmCode';
        $phone =
        $response = Http::post($url, [
            'phone' => '0936734488', //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
            'taxiColumnId' => 0 //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
        ]);
        return $response->status();
    }

    /**
     * Верификация телефона
     * Получение кода подтверждения
     * @return string
     */
    public function approvedPhones()
    {
        $url = config('app.taxi2012Url') . '/api/approvedPhones/';
        $response = Http::post($url, [
            'phone' => '0936734488', //Обязательный. Номер мобильного телефона
            'confirm_code' => '5945' //Обязательный. Код подтверждения.
        ]);
        return $response->status();
    }

}

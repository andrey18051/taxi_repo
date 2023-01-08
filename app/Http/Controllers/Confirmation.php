<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use function Complex\subtract;

class Confirmation extends Controller
{
    /**
     * Верификация телефона
     * Получение кода подтверждения
     * @return string
     */
    public function sendConfirmCode($phone)
    {
        $phone = substr($phone, 1);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/approvedPhones/sendConfirmCode';

        $response = Http::post($url, [
            'phone' => $phone, //Обязательный. Номер мобильного телефона, на который будет отправлен код подтверждения.
            'taxiColumnId' => 0 //Номер колоны, из которой отправляется SMS (0, 1 или 2, по умолчанию 0).
        ]);
        return $response->status();
        /* return 200;*/
    }

    /**
     * Верификация телефона
     * Получение кода подтверждения
     * @return string
     */
    public function approvedPhones($phone, $confirm_code)
    {
        $phone = substr($phone, 1);

        $connectAPI = WebOrderController::connectApi();
        if ($connectAPI == 400) {
            return redirect()->route('home-news')
                ->with('error', 'Вибачте. Помилка підключення до сервера. Спробуйте трохи згодом.');
        }
        $url = $connectAPI . '/api/approvedPhones/';
        $response = Http::post($url, [
            'phone' => $phone, //Обязательный. Номер мобильного телефона
            'confirm_code' =>  $confirm_code //Обязательный. Код подтверждения.
        ]);

        return $response->status();
/*
        return 200;*/
    }

}

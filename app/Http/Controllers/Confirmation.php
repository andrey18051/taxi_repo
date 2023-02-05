<?php

namespace App\Http\Controllers;

use App\Models\User;
use http\Message\Body;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use function Complex\subtract;

class Confirmation extends Controller
{
    /**
     * Проверка телефона пользователя в Базе
     */
    public function verifyPhoneInBase(Request $req)
    {
        //dd($req->phone);
        $user_phone = User::where('user_phone', $req->phone)->first();
        if ($user_phone) {
            return 200;
        } else {
            return 400;
        }
    }

    /**
     * Верификация телефона
     * Получение кода подтверждения
     * @return string
     */
    public static function sendConfirmCode($phone)
    {
//        return 200;
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
    //   dd($response->body());
        return $response->status();
    }

    /**
     * Верификация телефона
     * Получение кода подтверждения
     * @return string
     */
    public static function approvedPhones($phone, $confirm_code)
    {
//        return 200;
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
     //   dd($response);
        return $response->status();
    }

    public function verifySmsCode(Request $req)
    {
        $approvedPhones = self::approvedPhones($req->user_phone, $req->confirm_code);

        if ($approvedPhones != 200) {
            return view('auth.verifySMS', ['id' => $req->id, 'user_phone' => $req->user_phone, 'info' => "Помілка ввода кода."]);
        } else {
            $WebOrder = new WebOrderController();
            return $WebOrder->costWebOrder($req->id);
        }
    }
}

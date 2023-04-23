<?php

namespace App\Http\Controllers;

use App\Helpers\Telegram;
use App\Mail\PromoList;
use App\Models\Config;
use App\Models\Promo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class TelegramController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */


    public function emailTelegram(Request $req)
    {
        $req->validate([
            'emailTelegram' => ['email'],
        ]);

        $emailTelegram =  Config::where('id', 1)->first();

        $emailTelegram->emailTelegram = $req->emailTelegram;
        $emailTelegram->save();

        return redirect()->route('auth-telegram');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function redirectToTelegram()
    {
        return Socialite::driver('telegram')->redirect();
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */


    public function handleTelegramCallback()
    {
        $user = Socialite::driver('telegram')->user();
        $finduser = User::where('telegram_id', $user->id)->first();
        if ($finduser) {
            Auth::login($finduser);
            return redirect()->intended('/home-Combo');
        } else {
            $params ['name'] = $user['first_name'] . ' ' . $user['last_name'];
            $params ['telegram_id'] = $user['id'];
            return view('auth.registerTelegram', ['params' => $params, 'info' => 'Вкажіть адресу електронної пошти']);
        }
    }

    public function registerTelegram(Request $request)
    {
        $finduser = User::where('email', $request->email)->first();
        if ($finduser) {
            $finduser->telegram_id = $request->telegram_id;
            $finduser->save();
            Auth::login($finduser);
            return redirect()->intended('/home-Combo');
        } else {
            $newUser['name'] = $request->name;
            $newUser['email'] = $request->email ;
            $newUser['telegram_id'] = $request->telegram_id;
            $newUser['google_id'] = null;
            $newUser['facebook_id'] = null;
            $newUser['github_id'] = null;
            $newUser['linkedin_id'] = null;
            $newUser['twitter_id'] = null;
            $newUser['viber_id'] = null;
            return view('auth.registerSocial', ['newUser' => $newUser]);
        }
    }

    /**
     * @param Telegram $telegram
     * @return \Illuminate\Http\RedirectResponse
     */

    public function chatBotSendKeyboard(Telegram $telegram)
    {
        $user_name = Auth::user()->name;
        $message =  "Привіт $user_name 👋! Я віртуальний помічник служби Таксі Лайт Юа &#128661! Я розумію поки що трохи слів (наприклад - Привіт, трансфер, зустрич, робота), але я дуже швидко вчуся 😺";
        $telegram->sendMessage(Auth::user()->telegram_id, $message);
        $buttons = [
            'inline_keyboard' => [
                [
                    [
                        'text' => 'Трансфер 🏠',
                        'callback_data' => '1'
                    ],
                    [
                        'text' => 'Зустрич ✈',
                        'callback_data' => '2'
                    ],
                ],
                [
                    [
                        'text' => 'Послуги 🚕',
                        'callback_data' => '0'
                    ],

                    [
                        'text' => 'Робота в 🚕',
                        'callback_data' => '3'
                    ],
                ]

            ]
        ];
        $telegram->sendButtons(Auth::user()->telegram_id, 'Виберіть потрібне 🧭', json_encode($buttons));

        return redirect()->intended('/home-Combo');
    }

    public function setWebhook(Telegram $telegram)
    {
        $ch = $telegram->setWebhook();
        dd(json_decode($ch->body()));
    }

    public function getWebhook(Telegram $telegram)
    {
        $telegram->getWebhook();
    }

    public function getWebhookInfo(Telegram $telegram)
    {
        $ch = $telegram->getWebhookInfo();
        dd(json_decode($ch->body()));
    }

    public function sendDocument(Telegram $telegram)
    {
        $ch = $telegram->sendDocument(Auth::user()->telegram_id, 'questionnaire.docx');
        dd($ch->body());
    }

    public function sendAlarmMessage($message): \Illuminate\Http\Client\Response
    {
        $bot = '5875481045:AAE33BtWoSzilwWXGssmb4GIP27pxlvA9wo';
        return Http::post(Telegram::url . $bot . '/sendMessage', [
            'chat_id' => config('app.chat_id_alarm'),
            'text' => $message,
            'parse_mode' => 'html'
        ]);
    }

    public function sendAboutDriverMessage($chat_id, $message)
    {
        $bot = '5875481045:AAE33BtWoSzilwWXGssmb4GIP27pxlvA9wo';
        Http::post(Telegram::url . $bot . '/sendMessage', [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'html'
        ]);
        Http::post(Telegram::url . $bot . '/sendMessage', [
            'chat_id' => 120352595,
            'text' => $message,
            'parse_mode' => 'html'
        ]);
    }
}

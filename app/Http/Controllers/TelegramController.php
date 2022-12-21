<?php

namespace App\Http\Controllers;

use App\Helpers\Telegram;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
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
        $emailTelegram =  Config::where('id', 1)->first();
        try {
            $user = Socialite::driver('telegram')->user();

            $finduser = User::where('telegram_id', $user->id)->first();
            if ($finduser) {
                Auth::login($finduser);
                return redirect()->intended('/home-Combo');
            } else {
                try {
                    $finduser = User::where('email', $emailTelegram->EmailTelegram)->first();
                    $finduser->telegram_id = $user->id;
                    $finduser->save();
                    Auth::login($finduser);
                    $emailTelegram->EmailTelegram = null;
                    $emailTelegram->save();
                    return redirect()->intended('/home-Combo');
                } catch (Exception $e) {
                    $newUser['name'] = $user->name;
                    $newUser['email'] = $emailTelegram->EmailTelegram ;
                    $newUser['telegram_id'] = $user->id;
                    $newUser['google_id'] = null;
                    $newUser['facebook_id'] = null;
                    $newUser['github_id'] = null;
                    $newUser['linkedin_id'] = null;
                    $newUser['twitter_id'] = null;
                    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+|";
                    $newUser['password'] = substr(str_shuffle($chars), 0, 8);

                    $emailTelegram->EmailTelegram = null;
                    $emailTelegram->save();

                    return view('auth.registerSocial', ['newUser' => $newUser]);
                }

            }
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }

    /**
     * @param Telegram $telegram
     * @return \Illuminate\Http\RedirectResponse
     */

    public function chatBotSendKeyboard(Telegram $telegram)
    {
//    $telegram->sendMessage(env('REPORT_TELEGRAM_ID'), 'Привіт, Я віртуальний помічник Служби Таксі Лайт Юа!');
        $buttons = [
        'inline_keyboard' => [
            [
                [
                    'text' => 'Замовити таксі за адресою',
                    'url' => 'https://m.easy-order-taxi.site/home-Combo'
                ],
            ],
            [
                [
                    'text' => 'Замовити таксі по мапі',
                    'url' => 'https://m.easy-order-taxi.site/home-Map-Combo'
                ],
            ],
            [
                [
                    'text' => 'Надіслати повідомлення адміністратору',
                    'url' => 'https://m.easy-order-taxi.site/feedback'
                ],
            ],
            [
                [
                    'text' => 'Усі послуги',
                    'url' => 'https://m.easy-order-taxi.site'
                ],
            ],
            [
                [
                    'text' => 'Екстренна допомога',
                    'url' => 'https://m.easy-order-taxi.site/callBackForm'
                ],
            ],
        ]
        ];
        $telegram->sendButtons(Auth::user()->telegram_id, 'Привіт, Я віртуальний помічник Служби Таксі Лайт Юа!', json_encode($buttons));

        $buttons = [
        'inline_keyboard' => [
            [
                [
                    'text' => 'Бориспіль',
                    'url' => 'https://m.easy-order-taxi.site/transfer/Аэропорт%20Борисполь%20терминал%20Д/taxi.transferBorispol'
                ],
                [
                    'text' => 'Жуляни',
                    'url' => 'https://m.easy-order-taxi.site/transfer/Аэропорт%20Жуляны%20новый%20%28ул.Медовая%202%29/taxi.transferJulyany'
                ],
            ],
            [
                [
                    'text' => 'Південний вокзал',
                    'url' => 'https://m.easy-order-taxi.site/transfer/ЖД%20Южный/taxi.transferUZ'
                ],
                [
                    'text' => 'Автовокзал',
                    'url' => 'https://m.easy-order-taxi.site/transfer/Центральный%20автовокзал%20%28у%20шлагбаума%20пл.Московская%203%29/taxi.transferAuto'
                ],
            ],
        ]
        ];
        $telegram->sendButtons(Auth::user()->telegram_id, 'Замовити трансфер', json_encode($buttons));

        $buttons = [
        'inline_keyboard' => [
            [
                [
                    'text' => 'Бориспіль',
                    'url' => 'https://m.easy-order-taxi.site/transferfrom/Аэропорт%20Борисполь%20терминал%20Д/taxi.transferFromBorispol'
                ],
                [
                    'text' => 'Жуляни',
                    'url' => 'https://m.easy-order-taxi.site/transferfrom/Аэропорт%20Жуляны%20новый%20%28ул.Медовая%202%29/taxi.transferFromJulyany'
                ],
            ],
            [
                [
                    'text' => 'Південний вокзал',
                    'url' => 'https://m.easy-order-taxi.site/transferfrom/ЖД%20Южный/taxi.transferFromUZ'
                ],
                [
                    'text' => 'Автовокзал',
                    'url' => 'https://m.easy-order-taxi.site/transferfrom/Центральный%20автовокзал%20%28у%20шлагбаума%20пл.Московская%203%29/taxi.transferFromAuto'
                ],
            ],
        ]
    ];
        $telegram->sendButtons(Auth::user()->telegram_id, 'Замовити зустрич', json_encode($buttons));

        return redirect()->intended('/home-Combo');
    }

   /* public function setWebhook(Telegram $telegram)
    {
        $ch = $telegram->setWebhook();
        return $ch;
    }*/

}

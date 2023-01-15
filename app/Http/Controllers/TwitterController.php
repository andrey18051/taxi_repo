<?php

namespace App\Http\Controllers;

use App\Mail\PromoList;
use App\Models\Promo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class TwitterController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function redirectToTwitter()
    {
        return Socialite::driver('twitter')->redirect();
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function handleTwitterCallback()
    {
        try {
            $user = Socialite::driver('twitter')->user();
            $finduser = User::where('twitter_id', $user->id)->first();
            if ($finduser) {
                Auth::login($finduser);
                return redirect()->intended('/home-Combo');
            } else {
                $finduser = User::where('email', $user->email)->first();
                if ($finduser) {
                    $finduser->twitter_id = $user->id;
                    $finduser->save();
                    Auth::login($finduser);
                    return redirect()->intended('/home-Combo');
                } else {
                    //Создание промокода 5% при первой регистрации
                    PromoController::promoCodeNew($user->email);

                    $newUser['name'] = $user->name;
                    $newUser['email'] = $user->email;
                    $newUser['twitter_id'] = $user->id;
                    $newUser['google_id'] = null;
                    $newUser['facebook_id'] = null;
                    $newUser['github_id'] = null;
                    $newUser['linkedin_id'] = null;
                    $newUser['telegram_id'] = null;
                    $newUser['viber_id'] = null;
                    return view('auth.registerSocial', ['newUser' => $newUser]);
                }

            }
        } catch (Exception $e) {
            return view('auth.register', ['info' => 'Помілка реєстрації']);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Mail\PromoList;
use App\Models\Promo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class GithubController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function redirectToGithub()
    {
        return Socialite::driver('github')->redirect();
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function handleGithubCallback()
    {
        try {
            $user = Socialite::driver('github')->user();
            $finduser = User::where('github_id', $user->id)->first();
            if ($finduser) {
                Auth::login($finduser);
                return redirect()->intended('/home-Combo');
            } else {
                $finduser = User::where('email', $user->email)->first();
                if ($finduser) {
                    $finduser->github_id = $user->id;
                    $finduser->save();
                    Auth::login($finduser);
                    return redirect()->intended('/home-Combo');
                } else {
                    //Создание промокода 5% при первой регистрации
                    PromoController::promoCodeNew($user->email);

                    $newUser['name'] = $user->name;
                    $newUser['email'] = $user->email;
                    $newUser['github_id'] = $user->id;
                    $newUser['facebook_id'] = null;
                    $newUser['google_id'] = null;
                    $newUser['linkedin_id'] = null;
                    $newUser['twitter_id'] = null;
                    $newUser['telegram_id'] = null;
                    return view('auth.registerSocial', ['newUser' => $newUser]);
                }

            }
        } catch (Exception $e) {
            return view('auth.register', ['info' => 'Помілка реєстрації']);
        }
    }
}

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


class GoogleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Create a new controller instance.
     *
     */

    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
            $finduser = User::where('google_id', $user->id)->first();
            if ($finduser) {
                Auth::login($finduser);
                return redirect()->intended('/home-Combo');
            } else {
                $finduser = User::where('email', $user->email)->first();
                if ($finduser) {
                    $finduser->google_id = $user->id;
                    $finduser->save();
                    Auth::login($finduser);
                    return redirect()->intended('/home-Combo');
                } else {
                    $newUser['name'] = $user->name;
                    $newUser['email'] = $user->email;
                    $newUser['google_id'] = $user->id;
                    $newUser['facebook_id'] = null;
                    $newUser['linkedin_id'] = null;
                    $newUser['github_id'] = null;
                    $newUser['twitter_id'] = null;
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

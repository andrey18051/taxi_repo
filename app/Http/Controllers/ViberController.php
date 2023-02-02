<?php

namespace App\Http\Controllers;

use App\Helpers\Viber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class ViberController extends Controller
{
    public function setWebhook(Viber $viber)
    {
        $ch = $viber->setWebhook();
        dd(json_decode($ch->body()));
    }

    public function getAccountInfo(Viber $viber)
    {
        $ch = $viber->getAccountInfo();
        dd(json_decode($ch->body()));
    }

    public function getUserDetails(Viber $viber, $user_id)
    {
       // $user_id = 'tL0zpzMcNDlklD9V5dqEKg=';
        $ch = $viber->getUserDetails($user_id);
        $viberUser = json_decode($ch->body(), true);
    //    dd($viberUser['user']['id'], $viberUser['user']['name']);
        return $viberUser['user']['id'];
    }

    public function sendMessage(Viber $viber, $user_id, $message)
    {
        $viber->sendMessage($user_id, $message);
    }

    public function sendKeyboard(Viber $viber, $user_id, $message, $keyboard)
    {
        $viber->sendKeyboard($user_id, $message, $keyboard);
    }

    public function handleViberCallback($user_id, $name, $phone)
    {
        $finduser = User::where('viber_id', $user_id)->first();
        if ($finduser) {
            Auth::login($finduser);
            return redirect()->intended('/home-Combo');
        } else {
            $params ['name'] = $name;
            $params ['viber_id'] = $user_id;
            $params ['phone'] = $phone;
            return view('auth.registerViber', ['params' => $params, 'info' => 'Вкажіть адресу електронної пошти']);
        }
    }

    public function registerViber(Request $request)
    {
        $finduser = User::where('email', $request->email)->first();
        if ($finduser) {
            $finduser->viber_id = $request->viber_id;
            $finduser->save();
            Auth::login($finduser);
            return redirect()->intended('/home-Combo');
        } else {
            //Создание промокода 5% при первой регистрации
            PromoController::promoCodeNew($request->email);
            $newUser['name'] = $request->name;
            $newUser['email'] = $request->email ;
            $newUser['viber_id'] = $request->viber_id;
            $newUser['phone'] = $request->phone;
            $newUser['google_id'] = null;
            $newUser['facebook_id'] = null;
            $newUser['github_id'] = null;
            $newUser['linkedin_id'] = null;
            $newUser['twitter_id'] = null;
            $newUser['telegram_id'] = null;
            return view('auth.registerSocial', ['newUser' => $newUser]);
        }
    }



}

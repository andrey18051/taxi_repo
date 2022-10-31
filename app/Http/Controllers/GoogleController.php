<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
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
     * @return void
     */

    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
            $finduser = User::where('google_id', $user->id)->first();
            if ($finduser) {
                Auth::login($finduser);
                return redirect()->intended('/homeWelcome');
            } else {
                try {
                    $finduser = User::where('email', $user->email)->first();
                    $finduser->google_id = $user->id;
                    $finduser->save();
                    Auth::login($finduser);
                    return redirect()->intended('/homeWelcome');
                }
                catch (Exception $e) {
                    $newUser['name'] = $user->name;
                    $newUser['email'] = $user->email;
                    $newUser['google_id'] = $user->id;

                    /* $newUser = User::create([
                         'name' => $user->name,
                         'email' => $user->email,
                         'google_id' => $user->id,
                         'password' => encrypt('123456dummy'),
                         'password_taxi' => Crypt::encryptString('123456dummy')
                     ]);
                     Auth::login($newUser);*/
                    return view('auth.registerSocial', ['newUser' => $newUser]);
                }

            }
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}

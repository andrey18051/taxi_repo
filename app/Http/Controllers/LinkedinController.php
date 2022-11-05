<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class LinkedinController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function redirectToLinkedin()
    {
        return Socialite::driver('linkedin')->redirect();
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */


    public function handleLinkedinCallback()
    {
        try {
            $user = Socialite::driver('linkedin')->user();
            $finduser = User::where('linkedin_id', $user->id)->first();
            if ($finduser) {
                Auth::login($finduser);
                return redirect()->intended('/homeWelcome');
            } else {
                try {
                    $finduser = User::where('email', $user->email)->first();
                    $finduser->linkedin_id = $user->id;
                    $finduser->save();
                    Auth::login($finduser);
                    return redirect()->intended('/homeWelcome');
                } catch (Exception $e) {
                    $newUser['name'] = $user->name;
                    $newUser['email'] = $user->email;
                    $newUser['linkedin_id'] = $user->id;
                    $newUser['google_id'] = null;
                    $newUser['facebook_id'] = null;
                    return view('auth.registerSocial', ['newUser' => $newUser]);
                }

            }
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}

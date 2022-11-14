<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
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
                try {
                    $finduser = User::where('email', $user->email)->first();
                    $finduser->github_id = $user->id;
                    $finduser->save();
                    Auth::login($finduser);
                    return redirect()->intended('/home-Combo');
                } catch (Exception $e) {
                    $newUser['name'] = $user->name;
                    $newUser['email'] = $user->email;
                    $newUser['github_id'] = $user->id;
                    $newUser['facebook_id'] = null;
                    $newUser['google_id'] = null;
                    $newUser['linkedin_id'] = null;
                    $newUser['twitter_id'] = null;
                    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+|";
                    $newUser['password'] = substr(str_shuffle($chars), 0, 8);
                    return view('auth.registerSocial', ['newUser' => $newUser]);
                }

            }
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}

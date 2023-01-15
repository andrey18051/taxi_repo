<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PromoList;
use App\Models\Promo;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use App\Rules\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'user_phone' => ['required', new PhoneNumber()],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        //Создание промокода 5% при первой регистрации
        $promoCodeNew = substr($data['email'], 0, strripos($data['email'], '@'));
        $findPromo = Promo::where('promoCode', $promoCodeNew)->first();
        if (empty($findPromo)) {
            $promo = new Promo();
            $promo->promoCode = $promoCodeNew;
            $promo->promoSize = 5;
            $promo->promoRemark = 'Первая регистрация';
            $promo->save();

            $subject = "Реєстрація успішна";
            $message = "Отримайте бонус-код за реєстрацію на нашему сайті: $promoCodeNew. (Він стане доступний після авторизації). Приємних поїздок!";
            $paramsMail = [
                'subject' => $subject,
                'message' => $message,
            ];
            Mail::to($data['email'])->send(new PromoList($paramsMail));
        }
        return User::create([
            'name' => $data['name'],
            'user_phone' => $data['user_phone'],
            'email' => $data['email'],
            'google_id' => $data['google_id'],
            'facebook_id' => $data['facebook_id'],
            'linkedin_id' => $data['linkedin_id'],
            'github_id' => $data['github_id'],
            'twitter_id' => $data['twitter_id'],
            'telegram_id' => $data['telegram_id'],
            'viber_id' => $data['viber_id'],
            'password' => Hash::make($data['password']),
            'password_taxi' => Crypt::encryptString($data['password'])
        ]);
    }
}

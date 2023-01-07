<?php

namespace App\Http\Controllers;

use App\Mail\PromoList;
use App\Models\Promo;
use App\Models\PromoUse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PromoController extends Controller
{
    /**
     * @param $promoCode
     * @return float|int
     */
    public function promoSize($promoCode)
    {
        //Получили промокод из базы
        $promoArr = Promo::where('promoCode', $promoCode)->first();

        //Проверка на использование
        $promoCode_use = PromoUse::where('promo_id', $promoArr->id)->first();

        //Если есть запись об использовании промокода проверка на пользователя
        if ($promoCode_use !== null && Auth::user()->id != $promoCode_use->user_id) {
            $promUse = new PromoUse();
            $promUse->user_id = Auth::user()->id;
            $promUse->promo_id = $promoArr->id;
            $promUse->save();
            return $promoArr->promoSize / 100;
        }

        //Если нет записи об использовании промокода
        if ($promoCode_use == null) {
            $promUse = new PromoUse();
            $promUse->user_id = Auth::user()->id;
            $promUse->promo_id = $promoArr->id;
            $promUse->save();
            return $promoArr->promoSize / 100;
        }

        //Запрет повторного использования кода
        return 0;
    }

    /**
     * @param Request $req
     */
    public function promoCreat(Request $req)
    {
        $req->validate([
            'promoCode' => ['unique:promos'],
        ]);
        $promo = new Promo();
        $promo->promoCode = $req->promoCode;
        $promo->promoSize = $req->promoSize;
        $promo->promoRemark = $req->promoRemark;
        $promo->save();

        return view('admin.promo');
    }

    /**
     * Создание промокода 5% при первой регистрации
     * @param $email
     */
    public function promoCodeNew($email)
    {
        $promoCodeNew = substr($email, 0, strripos($email, '@'));
        $findPromo = Promo::where('promoCode', $promoCodeNew)->first();

        if (empty($findPromo)) {
            $promo = new Promo();
            $promo->promoCode = $promoCodeNew;
            $promo->promoSize = 5;
            $promo->promoRemark = 'Первая регистрация';
            $promo->save();

            $subject = "Ваш бонус-код";
            $message = "Отримайте бонус-код за реєстрацію на нашему сайті: $promoCodeNew. (Він стане доступний після авторизації). Приємних поїздок!";

            $paramsMail = [
                'subject' => $subject,
                'message' => $message,
            ];
            Mail::to($email)->send(new PromoList($paramsMail));
        }
    }
}

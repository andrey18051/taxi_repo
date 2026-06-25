<?php

namespace App\Helpers;

use App\Models\Orderweb;

class WfpOrderPaymentContextHelper
{
    public static function resolveApplication(Orderweb $orderweb): string
    {
        switch ($orderweb->comment) {
            case 'taxi_easy_ua_pas1':
                return 'PAS1';
            case 'taxi_easy_ua_pas2':
                return 'PAS2';
            case 'taxi_easy_ua_pas4':
                return 'PAS4';
            default:
                return 'PAS5';
        }
    }

    public static function resolveCity(Orderweb $orderweb): string
    {
        switch ($orderweb->city) {
            case 'city_kiev':
                return 'Kyiv City';
            case 'city_cherkassy':
                return 'Cherkasy Oblast';
            case 'city_odessa':
                if ($orderweb->server === 'http://188.190.245.102:7303' || $orderweb->server === 'my_server_api') {
                    return 'OdessaTest';
                }

                return 'Odessa';
            case 'city_zaporizhzhia':
                return 'Zaporizhzhia';
            case 'city_dnipro':
                return 'Dnipropetrovsk Oblast';
            case 'city_lviv':
                return 'Lviv';
            case 'city_ivano_frankivsk':
                return 'Ivano_frankivsk';
            case 'city_vinnytsia':
                return 'Vinnytsia';
            case 'city_poltava':
                return 'Poltava';
            case 'city_sumy':
                return 'Sumy';
            case 'city_kharkiv':
                return 'Kharkiv';
            case 'city_chernihiv':
                return 'Chernihiv';
            case 'city_rivne':
                return 'Rivne';
            case 'city_ternopil':
                return 'Ternopil';
            case 'city_khmelnytskyi':
                return 'Khmelnytskyi';
            case 'city_zakarpattya':
                return 'Zakarpattya';
            case 'city_zhytomyr':
                return 'Zhytomyr';
            case 'city_kropyvnytskyi':
                return 'Kropyvnytskyi';
            case 'city_mykolaiv':
                return 'Mykolaiv';
            case 'city_chernivtsi':
                return 'Chernivtsi';
            case 'city_lutsk':
                return 'Lutsk';
            default:
                return 'all';
        }
    }
}

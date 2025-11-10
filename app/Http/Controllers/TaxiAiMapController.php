<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaxiAiMapController extends Controller
{
    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function costMapExecute(Request $request)
    {
        Log::info('costMapExecute Request:', [
            'headers' => $request->headers->all(),
            'all_data' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl()
        ]);
        // Логируем входящие данные запроса
        Log::info('📦 costMapExecute REQUEST DATA:', [
            'origin_coordinates' => [
                'latitude' => $request->input('originLatitude', '46.4311896709615'),
                'longitude' => $request->input('originLongitude', '30.7634880146577')
            ],
            'destination_coordinates' => [
                'latitude' => $request->input('toLatitude', '46.3890993667171'),
                'longitude' => $request->input('toLongitude', '30.7504999628167')
            ],
            'route' => [
                'start' => $request->input('routefrom', 'ул. Аркадийское плато (Гагаринское плато), д.5|2, город Одесса'),
                'finish' => $request->input('routeto', 'ул. 16-я станция Большого Фонтана пляж, д.27|24, город Одесса')
            ],
            'user_info' => [
                'display_name' => $request->input('displayName', 'username'),
                'email' => $request->input('userEmail', 'andrey18051@gmail.com'),
                'phone' => $request->input('phone', '+380936734488'),
                'version_app' => $request->input('versionApp', 'last_version')
            ],
            'order_details' => [
                'tariff' => $request->input('tariff', 'Start'),
                'payment_type' => $request->input('payment_type', 'nal_payment'),
                'client_cost' => $request->input('clientCost', '+380936734488'),
                'additional_cost' => $request->input('add_cost', '0'),
                'required_time' => $request->input('required_time', '01.01.1970 00:00'),
                'comment' => $request->input('comment', 'no_comment'),
                'date' => $request->input('date', 'no_date')
            ],
            'system_info' => [
                'city' => $request->input('city', 'OdessaTest'),
                'application' => $request->input('application', 'PAS2'),
                'wfp_invoice' => $request->input('wfpInvoice', ''),
                'services' => $request->input('services', '')
            ]
        ]);


        $originLatitude = $request->input('originLatitude', '46.4311896709615');
        $originLongitude =  $request->input('originLongitude', '30.7634880146577');
        $toLatitude = $request->input('toLatitude', '46.3890993667171');
        $toLongitude = $request->input('toLongitude', '30.7504999628167');
        $tariff = $request->input('tariff', ' ');
        $phone = $request->input('phone', '+380936734488');
        $displayName = $request->input('displayName', 'username');
        $versionApp = $request->input('versionApp', 'last_version');
        $userEmail = $request->input('userEmail', 'andrey18051@gmail.com');
        $payment_type = $request->input('payment_type', 'nal_payment');
        $user = $displayName  . $versionApp . "*" . $userEmail . "*" . $payment_type;
        $time = $request->input('required_time', 'no_time');
        $comment = $request->input('comment', 'no_comment');
        $date = $request->input('date', 'no_date');
        $start = $request->input('routefrom', 'ул. Аркадийское плато (Гагаринское плато), д.5|2, город Одесса');
        $finish = $request->input('routeto', 'ул. 16-я станция Большого Фонтана пляж, д.27|24, город Одесса');
        $wfpInvoice = $request->input('wfpInvoice', "*");
        $services = $request->input('services', 'no_extra_charge_codes');
        $city = $request->input('city', 'OdessaTest');
        $application =  $request->input('application', 'PAS2');

        // Логируем входящие данные запроса с результатами присваивания
        Log::info('📦 CREATE ORDER REQUEST DATA:', [
            'origin_coordinates' => [
                'latitude' => $originLatitude,
                'longitude' => $originLongitude
            ],
            'destination_coordinates' => [
                'latitude' => $toLatitude,
                'longitude' => $toLongitude
            ],
            'route' => [
                'start' => $start,
                'finish' => $finish
            ],
            'user_info' => [
                'display_name' => $displayName,
                'email' => $userEmail,
                'phone' => $phone,
                'version_app' => $versionApp,
                'user_string' => $user,
                'payment_type' => $payment_type
            ],
            'order_details' => [
                'tariff' => $tariff,
                'required_time' => $time,
                'comment' => $comment,
                'date' => $date
            ],
            'system_info' => [
                'city' => $city,
                'application' => $application,
                'wfp_invoice' => $wfpInvoice,
                'services' => $services
            ]
        ]);
        $controller = new AndroidTestOSMController();
        $response = $controller->costSearchMarkersTime(
            $originLatitude,
            $originLongitude,
            $toLatitude,
            $toLongitude,
            $tariff,
            $phone,
            $user,
            $time,
            $date,
            $services,
            $city,
            $application
        );
        Log::info('📤 Ответ Android API: ' . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return  $response;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\IP;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Stevebauman\Location\Facades\Location;

class IPController extends Controller
{
    /**
     * @param $page
     */

    public function getIP($page)
    {
        // Если это Kafka consumer - не логируем IP
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            return;
        }

        /* IP::where('IP_ADDR', '31.202.139.47')->delete();*/
        $remoteAddr = self::getClientIp();

        if ($remoteAddr !== '31.202.139.47') {
            $IP = new IP();
            $IP->IP_ADDR = $remoteAddr;
            $IP->page = 'https://m.easy-order-taxi.site' . $page;
            $IP->save();
        }
    }
    public function getClientIp(): string
    {
        if (php_sapi_name() === 'cli' || !isset($_SERVER['REMOTE_ADDR'])) {
            return 'kafka-consumer-' . (gethostname() ?: 'unknown');
        }

        return $_SERVER['REMOTE_ADDR'];
    }
    public function ipCity(): \Illuminate\Http\JsonResponse
    {
        $remoteAddr = self::getClientIp();
        $LocationData = Location::get($remoteAddr);
//        $LocationData = Location::get("94.158.152.248"); //Odessa
//        $LocationData = Location::get("185.237.74.247"); //Kyiv City
//        $LocationData = Location::get("146.158.30.190"); //Dnipropetrovsk Oblast
//        $LocationData = Location::get("91.244.56.202"); //Cherkasy Oblast
        return response()->json(['response' => $LocationData->regionName]);
    }

    public function ipCityOne($ip): \Illuminate\Http\JsonResponse
    {
        $client_ip = getenv("REMOTE_ADDR");
        $LocationData = Location::get($client_ip);
//        dd($LocationData);
//        $url = "//api.ip2location.io/?key=" . config('app.keyIP2Location') . '&ip=' . $ip;
//        https://api.ip2whois.com/v2?key=F9B017964A5A721A183DAFEDAE47F94E&ip=37.73.155.251
//        https://api.ip2location.io/?key=F9B017964A5A721A183DAFEDAE47F94E&ip=31.202.139.47

//        dd($url);
//        $response = Http::get($url);
//        dd($response->body());
//        dd($LocationData );
//        $LocationData = Location::get("94.158.152.248"); //Odessa
//        $LocationData = Location::get("185.237.74.247"); //Kyiv City
//        $LocationData = Location::get("146.158.30.190"); //Dnipropetrovsk Oblast
//        $LocationData = Location::get("91.244.56.202"); //Cherkasy Oblast
        if ($LocationData->countryCode != "UA") {
            return response()->json(['response' => "foreign countries"]);
        } else {
            return response()->json(['response' => $LocationData->regionName]);
        }
    }
    public function ipCityPush()
    {
        $remoteAddr = self::getClientIp();
        $LocationData = Location::get($remoteAddr);
//        dd($LocationData);
//        $url = "//api.ip2location.io/?key=" . config('app.keyIP2Location') . '&ip=' . $ip;
//        https://api.ip2whois.com/v2?key=F9B017964A5A721A183DAFEDAE47F94E&ip=37.73.155.251
//        https://api.ip2location.io/?key=F9B017964A5A721A183DAFEDAE47F94E&ip=31.202.139.47

//        dd($url);
//        $response = Http::get($url);
//        dd($response->body());
//        dd($LocationData );
//        $LocationData = Location::get("94.158.152.248"); //Odessa
//        $LocationData = Location::get("185.237.74.247"); //Kyiv City
//        $LocationData = Location::get("146.158.30.190"); //Dnipropetrovsk Oblast
//        $LocationData = Location::get("91.244.56.202"); //Cherkasy Oblast

//        return $LocationData->regionName;
        return $LocationData->toArray();
    }

    public function countryName($ip): \Illuminate\Http\JsonResponse
    {
        // Use the provided $ip parameter instead of getenv("REMOTE_ADDR")
        $LocationData = Location::get($ip);

        // Check if $LocationData is valid
        if ($LocationData && isset($LocationData->countryCode)) {
            return response()->json(['response' => $LocationData->countryCode]);
        }

        // Return a fallback response if location data is not available
        return response()->json(['response' => 'Unknown'], 404);
    }

    public function address(): \Illuminate\Http\JsonResponse
    {
        $remoteAddr = self::getClientIp();
        $LocationData = Location::get($remoteAddr);
//                $LocationData = Location::get("94.158.152.248"); //Odessa
//        $LocationData = Location::get("146.158.30.190"); //Dnipropetrovsk Oblast
//                $LocationData = Location::get("185.237.74.247"); //Kyiv City
//                $LocationData = Location::get("81.90.230.250"); // Zaporizhzhia

        return response()->json(['response' => $LocationData->countryName]);
    }
}

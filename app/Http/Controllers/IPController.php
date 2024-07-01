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
           /* IP::where('IP_ADDR', '31.202.139.47')->delete();*/
        if (getenv("REMOTE_ADDR") !== '31.202.139.47') {
            $IP =  new IP();
            $IP->IP_ADDR = getenv("REMOTE_ADDR");
//            $IP->page = 'https://m.easy-order-taxi.site' . $page;
            $IP->page = 'https://m.easy-order-taxi.site' . $page;
            $IP->save();
        }
    }

    public function ipCity(): \Illuminate\Http\JsonResponse
    {
        $LocationData = Location::get(getenv("REMOTE_ADDR"));
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
        $ip = getenv("REMOTE_ADDR");
        $LocationData = Location::get($ip);
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

        $LocationData = Location::get(getenv("REMOTE_ADDR"));
        return response()->json(['response' => $LocationData->countryCode]);
    }

    public function address(): \Illuminate\Http\JsonResponse
    {
        $LocationData = Location::get(getenv("REMOTE_ADDR"));
//                $LocationData = Location::get("94.158.152.248"); //Odessa
//        $LocationData = Location::get("146.158.30.190"); //Dnipropetrovsk Oblast
//                $LocationData = Location::get("185.237.74.247"); //Kyiv City
//                $LocationData = Location::get("81.90.230.250"); // Zaporizhzhia

        return response()->json(['response' => $LocationData->countryName]);
    }
}

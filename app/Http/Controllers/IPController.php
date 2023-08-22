<?php

namespace App\Http\Controllers;

use App\Models\IP;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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
        return response()->json(['response' => $LocationData->regionName]);
    }

    public function address(): \Illuminate\Http\JsonResponse
    {
        $LocationData = Location::get(getenv("REMOTE_ADDR"));
//                $LocationData = Location::get("94.158.152.248"); //Odessa
//        $LocationData = Location::get("146.158.30.190"); //Dnipropetrovsk Oblast
//                $LocationData = Location::get("185.237.74.247"); //Kyiv City
//                $LocationData = Location::get("81.90.230.250"); // Zaporizhzhia
        return response()->json(['response' => $LocationData]);
    }
}

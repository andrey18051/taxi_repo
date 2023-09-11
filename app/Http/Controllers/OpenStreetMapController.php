<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OpenStreetMapController extends Controller
{
    public function reverse ($originLatitude, $originLongitude)
    {
        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$originLatitude&lon=$originLongitude";

        $response = Http::get($url);
        $response_arr = json_decode($response, true);
        if (empty($response_arr)) {
            return 404;
        } else {
//            dd($response_arr);
            if($response_arr["category"] == "building") {
                return $response_arr["address"]["road"] . " буд. " . $response_arr["address"]["house_number"] .", місто " . $response_arr["address"]["city"];
            } elseif ($response_arr["category"] == "amenity") {
                return $response_arr["address"]["amenity"] . " " . $response_arr["address"]["road"] .", місто " . $response_arr["address"]["city"];
            } else {
                return $response_arr["display_name"];
            }
        }
    }
}

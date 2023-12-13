<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OpenStreetMapController extends Controller
{
    public function reverse($originLatitude, $originLongitude): string
    {
        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$originLatitude&lon=$originLongitude";

        $response = Http::get($url);
        $response_arr = json_decode($response, true);

        if (empty($response_arr)) {
            $r = 50;
            $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
                . $originLongitude
                . "," . $originLatitude
                . "&r=" . $r . "&l=1&key="
                . config("app.keyVisicom");

            $response = Http::get($url);
            $response_arr_from = json_decode($response, true);

            if ($response_arr_from != null) {
                return $response_arr_from["properties"]["street_type"]
                    . $response_arr_from["properties"]["street"]
                    . ", буд." . $response_arr_from["properties"]["name"]
                    . ", " . $response_arr_from["properties"]["settlement_type"]
                    . " " . $response_arr_from["properties"]["settlement"];
            } else {
                return "Точка на карте";
            }
        } else {
            if ($response_arr["category"] == "building") {
                return $response_arr["address"]["road"] . " буд. " . $response_arr["address"]["house_number"] .", місто " . $response_arr["address"]["city"];
            } else {
                $r = 50;
                $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
                    . $originLongitude
                    . "," . $originLatitude
                    . "&r=" . $r . "&l=1&key="
                    . config("app.keyVisicom");

                $response = Http::get($url);
                $response_arr_from = json_decode($response, true);

                if ($response_arr_from != null) {
                    return $response_arr_from["properties"]["street_type"]
                        . $response_arr_from["properties"]["street"]
                        . ", буд." . $response_arr_from["properties"]["name"]
                        . ", " . $response_arr_from["properties"]["settlement_type"]
                        . " " . $response_arr_from["properties"]["settlement"];
                } else {
                    return "Точка на карте";
                }
            }
        }
    }

    /**
     * @param $originLatitude
     * @param $originLongitude
     * @return string[]
     */
    public function reverseAddress($originLatitude, $originLongitude)
    {
        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$originLatitude&lon=$originLongitude";

        $response = Http::get($url);
        $response_arr = json_decode($response, true);

        if (empty($response_arr)) {
            $r = 50;
            $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
                . $originLongitude
                . "," . $originLatitude
                . "&r=" . $r . "&l=1&key="
                . config("app.keyVisicom");

            $response = Http::get($url);
            $response_arr_from = json_decode($response, true);

            if ($response_arr_from != null) {
                return ["result" => $response_arr_from["properties"]["street_type"]
                    . $response_arr_from["properties"]["street"]
                    . ", буд." . $response_arr_from["properties"]["name"]
                    . ", " . $response_arr_from["properties"]["settlement_type"]
                    . " " . $response_arr_from["properties"]["settlement"]];
            } else {
                return ["result" => "404"];
            }
        } else {
            if ($response_arr["category"] == "building") {
                return ["result" =>$response_arr["address"]["road"] . " буд. " . $response_arr["address"]["house_number"] .", місто " . $response_arr["address"]["city"]];
            } else {
                $r = 50;
                $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
                    . $originLongitude
                    . "," . $originLatitude
                    . "&r=" . $r . "&l=1&key="
                    . config("app.keyVisicom");

                $response = Http::get($url);
                $response_arr_from = json_decode($response, true);

                if ($response_arr_from != null) {
                    return ["result" =>$response_arr_from["properties"]["street_type"]
                        . $response_arr_from["properties"]["street"]
                        . ", буд." . $response_arr_from["properties"]["name"]
                        . ", " . $response_arr_from["properties"]["settlement_type"]
                        . " " . $response_arr_from["properties"]["settlement"]];
                } else {
                    return ["result" =>"404"];
                }
            }
        }
    }
}

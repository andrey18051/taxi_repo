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
            $r = 200;
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
                $address_string = $response_arr["address"]["road"] . " буд. " . $response_arr["address"]["house_number"];
                if (isset($response_arr["address"]["city"])) {
                    $address_string .= ", місто " . $response_arr["address"]["city"];
                }
                return $address_string;
            } else {
                $r = 200;
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
//        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$originLatitude&lon=$originLongitude&accept-language=en";

        $response = Http::get($url);
        $response_arr = json_decode($response, true);

        if (empty($response_arr)) {
            $r = 200;
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
                $r = 200;
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

    public function reverseAddressLocal($originLatitude, $originLongitude, $local): array
    {
//        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$originLatitude&lon=$originLongitude";
        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$originLatitude&lon=$originLongitude&accept-language=$local";

        $response = Http::get($url);
        $response_arr = json_decode($response, true);

        switch ($local) {
            case "ru":
                $city_text = "город ";
                $building_text = "д.";
                break;
            case "en":
                $city_text = "city ";
                $building_text = "build.";
                break;
            default:
                $city_text = "місто ";
                $building_text = "буд.";
        }

        if (empty($response_arr)) {
            $r = 200;
            $url = "https://api.visicom.ua/data-api/5.0/$local/geocode.json?categories=adr_address&near="
                . $originLongitude
                . "," . $originLatitude
                . "&r=" . $r . "&l=1&key="
                . config("app.keyVisicom");

            $response = Http::get($url);
            $response_arr_from = json_decode($response, true);

            if ($response_arr_from != null) {
                return ["result" => $response_arr_from["properties"]["street_type"]
                    . $response_arr_from["properties"]["street"]
                    . ", $building_text" . $response_arr_from["properties"]["name"]
                    . ", " . $response_arr_from["properties"]["settlement_type"]
                    . " " . $response_arr_from["properties"]["settlement"]];
            } else {
                return ["result" => "404"];
            }
        } else {
            if ($response_arr["category"] == "building") {
                return ["result" =>$response_arr["address"]["road"] . " $building_text" . $response_arr["address"]["house_number"] .", $city_text" . $response_arr["address"]["city"]];
            } else {
                $r = 200;
                $url = "https://api.visicom.ua/data-api/5.0/$local/geocode.json?categories=adr_address&near="
                    . $originLongitude
                    . "," . $originLatitude
                    . "&r=" . $r . "&l=1&key="
                    . config("app.keyVisicom");

                $response = Http::get($url);
                $response_arr_from = json_decode($response, true);

                if ($response_arr_from != null) {
                    return ["result" =>$response_arr_from["properties"]["street_type"]
                        . $response_arr_from["properties"]["street"]
                        . ", $building_text" . $response_arr_from["properties"]["name"]
                        . ", " . $response_arr_from["properties"]["settlement_type"]
                        . " " . $response_arr_from["properties"]["settlement"]];
                } else {
                    return ["result" =>"404"];
                }
            }
        }
    }
}

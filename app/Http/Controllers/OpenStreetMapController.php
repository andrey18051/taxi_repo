<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenStreetMapController extends Controller
{
    public function reverse($originLatitude, $originLongitude): string
    {
        // Осуществляем запрос к OpenStreetMap (OSM)
        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$originLatitude&lon=$originLongitude";
        $response = Http::get($url);

        // Проверка на успешность запроса
        if ($response->successful()) {
            $response_arr = json_decode($response, true);

            if (!empty($response_arr) && isset($response_arr["address"])) {
                if ($response_arr["category"] == "building") {
                    $address_string = $response_arr["address"]["road"] . " буд. " . $response_arr["address"]["house_number"];
                    if (isset($response_arr["address"]["city"])) {
                        $address_string .= ", місто " . $response_arr["address"]["city"];
                    }
                    return $address_string;
                }
            }
        }

        // Если OSM не вернул нужную информацию, используем Visicom API
        $r = 300;
        $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near="
            . $originLongitude
            . "," . $originLatitude
            . "&r=" . $r . "&l=1&key="
            . config("app.keyVisicom");

        $response = Http::get($url);

        // Проверка на успешность запроса
        if ($response->successful()) {
            $response_arr_from = json_decode($response, true);
            if ($response_arr_from != null && isset($response_arr_from["properties"])) {
                $address_string = $response_arr_from["properties"]["street_type"]
                    . $response_arr_from["properties"]["street"]
                    . ", буд." . $response_arr_from["properties"]["name"]
                    . ", " . $response_arr_from["properties"]["settlement_type"]
                    . " " . $response_arr_from["properties"]["settlement"];
                return $address_string;
            }
        }

        // Если и Visicom не вернул адрес, возвращаем стандартное сообщение
        return "Точка на карте";
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
            $r = 300;
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
                $r = 300;
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

//    public function reverseAddressLocal($originLatitude, $originLongitude, $local): array
//    {
////        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$originLatitude&lon=$originLongitude";
//        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$originLatitude&lon=$originLongitude&accept-language=$local";
//
//        $response = Http::get($url);
//        $response_arr = json_decode($response, true);
//
//        switch ($local) {
//            case "ru":
//                $city_text = "город ";
//                $building_text = "д.";
//                break;
//            case "en":
//                $city_text = "city ";
//                $building_text = "build.";
//                break;
//            default:
//                $city_text = "місто ";
//                $building_text = "буд.";
//        }
//
//        if (empty($response_arr)) {
//            $r = 300;
//            $url = "https://api.visicom.ua/data-api/5.0/$local/geocode.json?categories=adr_address&near="
//                . $originLongitude
//                . "," . $originLatitude
//                . "&r=" . $r . "&l=1&key="
//                . config("app.keyVisicom");
//
//            $response = Http::get($url);
//            $response_arr_from = json_decode($response, true);
//
//            if ($response_arr_from != null) {
//                return ["result" => $response_arr_from["properties"]["street_type"]
//                    . $response_arr_from["properties"]["street"]
//                    . ", $building_text" . $response_arr_from["properties"]["name"]
//                    . ", " . $response_arr_from["properties"]["settlement_type"]
//                    . " " . $response_arr_from["properties"]["settlement"]];
//            } else {
//                return ["result" => "Точка на карте"];
//            }
//        } else {
//            if ($response_arr["category"] == "building") {
//                if (isset($response_arr["address"]["city"])) {
//                    return ["result" =>$response_arr["address"]["road"] . " $building_text" . $response_arr["address"]["house_number"] .", $city_text" . $response_arr["address"]["city"]];
//                } else {
//                    return ["result" =>$response_arr["address"]["road"] . " $building_text" . $response_arr["address"]["house_number"] .", $city_text"];
//                }
//            } else {
//                $r = 300;
//                $url = "https://api.visicom.ua/data-api/5.0/$local/geocode.json?categories=adr_address&near="
//                    . $originLongitude
//                    . "," . $originLatitude
//                    . "&r=" . $r . "&l=1&key="
//                    . config("app.keyVisicom");
//
//                $response = Http::get($url);
//                $response_arr_from = json_decode($response, true);
//
//                if ($response_arr_from != null) {
//                    return ["result" =>$response_arr_from["properties"]["street_type"]
//                        . $response_arr_from["properties"]["street"]
//                        . ", $building_text" . $response_arr_from["properties"]["name"]
//                        . ", " . $response_arr_from["properties"]["settlement_type"]
//                        . " " . $response_arr_from["properties"]["settlement"]];
//                } else {
//                    return ["result" => "Точка на карте"];
//                }
//            }
//        }
//    }

//    public function reverseAddressLocal($originLatitude, $originLongitude, $local): array
//    {
//        $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$originLatitude&lon=$originLongitude&accept-language=$local";
//
//        Log::info("OSM Request URL: $url");
//
//        try {
//            $response = Http::get($url);
//            $response_arr = json_decode($response, true);
//            Log::info("OSM Response: " . json_encode($response_arr));
//
//            if (!empty($response_arr) && $response_arr["category"] == "building") {
//                $city_text = $local === "ru" ? "город " : ($local === "en" ? "city " : "місто ");
//                $building_text = $local === "ru" ? "д." : ($local === "en" ? "build." : "буд.");
//
//                if (isset($response_arr["address"]["city"])) {
//                    return ["result" => $response_arr["address"]["road"] . " $building_text" . $response_arr["address"]["house_number"] . ", $city_text" . $response_arr["address"]["city"]];
//                } else {
//                    return ["result" => $response_arr["address"]["road"] . " $building_text" . $response_arr["address"]["house_number"]];
//                }
//            } else {
//                return ["result" => "Точка на карте"];
//            }
//        } catch (\Exception $e) {
//            Log::error("OSM Error: " . $e->getMessage());
//            return ["result" => "Ошибка OSM: " . $e->getMessage()];
//        }
//    }
//    public function reverseWithVisicom($originLatitude, $originLongitude, $local)
//    {
//        $r = 300; // Радиус поиска в метрах
//        $url = "https://api.visicom.ua/data-api/5.0/$local/geocode.json?categories=adr_address&near="
//            . $originLongitude
//            . "," . $originLatitude
//            . "&r=" . $r . "&l=1&key=" . config("app.keyVisicom");
//
//        Log::info("Visicom Request URL: $url");
//
//        try {
//            $response = Http::get($url);
//            $response_arr_from = json_decode($response, true);
//            Log::info("444 Visicom Response: " . json_encode($response_arr_from));
//
//            if (!empty($response_arr_from) && isset($response_arr_from["properties"])) {
//                $city_text = $local === "ru" ? "город " : ($local === "en" ? "city " : "місто ");
//                $building_text = $local === "ru" ? "д." : ($local === "en" ? "build." : "буд.");
//
//                return ["result" => $response_arr_from["properties"]["street_type"]
//                    . $response_arr_from["properties"]["street"]
//                    . ", $building_text" . $response_arr_from["properties"]["name"]
//                    . ", " . $response_arr_from["properties"]["settlement_type"]
//                    . " " . $response_arr_from["properties"]["settlement"]];
//            }
//            if (empty($response_arr_from)) {
//                $result = "Точка на карте";
//                Log::warning("Empty response from Visicom or missing properties");
//                return ["result" => $result];
//            }
//        } catch (\Exception $e) {
//            Log::error("Visicom Error: " . $e->getMessage());
//            $result = "Точка на карте";
//            return ["result" => $result];
//        }
//
//        $result = "Точка на карте";
//        return ["result" => $result];
//    }
    public function reverseWithVisicom($originLatitude, $originLongitude, $local): array
    {
        $r = 300; // Радиус поиска в метрах
        $url = "https://api.visicom.ua/data-api/5.0/$local/geocode.json?categories=adr_address&near="
            . $originLongitude
            . "," . $originLatitude
            . "&r=" . $r . "&l=1&key="
            . config("app.keyVisicom");
//        $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near=30.51043,50.45358&r=50&l=1&key=". config("app.keyVisicom");

        Log::info("Visicom Request URL: $url");

        try {
            $response = Http::get($url);
            $response_arr_from = json_decode($response, true);
            Log::info("Visicom Response: " . json_encode($response_arr_from));

            if (!empty($response_arr_from)) {
                $city_text = $local === "ru" ? "город " : ($local === "en" ? "city " : "місто ");
                $building_text = $local === "ru" ? "д." : ($local === "en" ? "build." : "буд.");

                return ["result" => $response_arr_from["properties"]["street_type"]
                    . $response_arr_from["properties"]["street"]
                    . ", $building_text" . $response_arr_from["properties"]["name"]
                    . ", " . $response_arr_from["properties"]["settlement_type"]
                    . " " . $response_arr_from["properties"]["settlement"]];
            } else {
                return ["result" => "Точка на карте"];
            }
        } catch (\Exception $e) {
            Log::error("Visicom Error: " . $e->getMessage());
            return ["result" => "Ошибка Visicom: " . $e->getMessage()];
        }
    }

    public function reverseAddressLocal($originLatitude, $originLongitude, $local)
    {
        $radius = 300; // Радиус поиска в метрах
        $url = "https://overpass-api.de/api/interpreter?data=[out:json];node(around:$radius,$originLatitude,$originLongitude);out;";

        Log::info("Overpass API Request URL: $url");

        try {
            $response = Http::get($url);
            $response_arr = json_decode($response, true);
            Log::info("wwww Overpass API Response: " . json_encode($response_arr));

            if (!empty($response_arr['elements'])) {
                $nodes = $response_arr['elements'];
                $validNode = null;

                // Поиск первого узла с непустыми данными
                foreach ($nodes as $node) {
                    if (!empty($node['tags']['addr:street'])) {
                        $validNode = $node;
                        break;
                    }
                }

                if ($validNode) {
                    $address = $validNode['tags'];

                    $city_text = $local === "ru" ? "город " : ($local === "en" ? "city " : "місто ");
                    $building_text = $local === "ru" ? "д." : ($local === "en" ? "build." : "буд.");

                    $road = $address['addr:street'] ?? "Неизвестная улица";
                    $house = $address['addr:housenumber'] ?? "Неизвестная";

                    // Попробуем извлечь город из нескольких источников
                    $city = $address['addr:city']
                        ?? $address['is_in']
                        ?? $address['addr:place']
                        ?? "Неизвестная"; // Резервное значение, если город не найден

                    Log::info("Extracted city: $city from node: " . json_encode($validNode));

                    if ($house != "Неизвестная") {
                        if ($city != "Неизвестная") {
                            $result = "$road, $building_text $house, $city_text $city";
                        } else {
                            $result = "$road, $building_text $house";
                        }
                        return ["result" => $result];
                    }


                }
        } else {
                Log::error("Адрес не найден в радиусе $radius метров");
            }
        } catch (\Exception $e) {
            Log::error("Overpass API Error: " . $e->getMessage());
        }

        $r = 300; // Радиус поиска в метрах
        $url = "https://api.visicom.ua/data-api/5.0/$local/geocode.json?categories=adr_address&near="
            . $originLongitude
            . "," . $originLatitude
            . "&r=" . $r . "&l=1&key="
            . config("app.keyVisicom");
//        $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near=30.51043,50.45358&r=50&l=1&key=". config("app.keyVisicom");

        Log::info("Visicom Request URL: $url");

        try {
            $response = Http::get($url);
            $response_arr_from = json_decode($response, true);
            Log::info("Visicom Response: " . json_encode($response_arr_from));

            if (!empty($response_arr_from)) {
                $city_text = $local === "ru" ? "город " : ($local === "en" ? "city " : "місто ");
                $building_text = $local === "ru" ? "д." : ($local === "en" ? "build." : "буд.");

                return ["result" => $response_arr_from["properties"]["street_type"]
                    . $response_arr_from["properties"]["street"]
                    . ", $building_text" . $response_arr_from["properties"]["name"]
                    . ", " . $response_arr_from["properties"]["settlement_type"]
                    . " " . $response_arr_from["properties"]["settlement"]];
            } else {
                return ["result" => "Точка на карте"];
            }
        } catch (\Exception $e) {
            Log::error("Visicom Error: " . $e->getMessage());
            return ["result" => "Точка на карте"];
        }
    }

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Exceptions\HttpResponseException;

class OpenStreetMapController extends Controller
{
    /**
     * Обратное геокодирование с кэшем (для расчёта стоимости — не блокировать внешний API).
     */
    public static function reverseCached(float $latitude, float $longitude): string
    {
        $latKey = round($latitude, 5);
        $lonKey = round($longitude, 5);
        $cacheKey = "osm_reverse:{$latKey}:{$lonKey}";

        return Cache::remember($cacheKey, 86400, function () use ($latitude, $longitude) {
            return (new self)->reverse($latitude, $longitude);
        });
    }

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
            $response_arr_from = $response->json();
            Log::info("Visicom Response: status=" . $response->status() . " " . json_encode($response_arr_from));

            if ($response->successful()
                && is_array($response_arr_from)
                && isset($response_arr_from['properties'])) {
                $city_text = $local === "ru" ? "город " : ($local === "en" ? "city " : "місто ");
                $building_text = $local === "ru" ? "д." : ($local === "en" ? "build." : "буд.");
                $props = $response_arr_from['properties'];

                return ["result" => ($props['street_type'] ?? '')
                    . ($props['street'] ?? '')
                    . ", $building_text" . ($props['name'] ?? '')
                    . ", " . ($props['settlement_type'] ?? '')
                    . " " . ($props['settlement'] ?? '')];
            }

            return ["result" => "Точка на карте"];
        } catch (\Exception $e) {
            Log::error("Visicom Error: " . $e->getMessage());
            return ["result" => "Ошибка Visicom: " . $e->getMessage()];
        }
    }

    public function reverseAddressLocal($originLatitude, $originLongitude, $local)
    {
        $nominatim = $this->reverseFromNominatim((float) $originLatitude, (float) $originLongitude, (string) $local);
        if ($nominatim !== null) {
            return ['result' => $nominatim];
        }

        $visicom = $this->reverseWithVisicom($originLatitude, $originLongitude, $local);
        if (!empty($visicom['result']) && $visicom['result'] !== 'Точка на карте') {
            return $visicom;
        }

        return ['result' => 'Точка на карте'];
    }

    /**
     * Обратное геокодирование через Nominatim (требует User-Agent).
     */
    private function reverseFromNominatim(float $latitude, float $longitude, string $local): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'TaxiEasyUa/1.0 (taxi.easy.ua.sup@gmail.com)',
            ])->timeout(10)->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'jsonv2',
                'lat' => $latitude,
                'lon' => $longitude,
                'accept-language' => $local,
            ]);

            Log::info('Nominatim reverse: status=' . $response->status());

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            if (!is_array($data) || empty($data['address'])) {
                return null;
            }

            $address = $data['address'];
            $road = $address['road'] ?? $address['pedestrian'] ?? $address['footway'] ?? null;
            $house = $address['house_number'] ?? null;
            $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? null;
            $cityText = $local === 'ru' ? 'город ' : ($local === 'en' ? 'city ' : 'місто ');
            $buildingText = $local === 'ru' ? 'д.' : ($local === 'en' ? 'build.' : 'буд.');

            if ($road !== null && $house !== null) {
                if ($city !== null) {
                    return $road . ', ' . $buildingText . ' ' . $house . ', ' . $cityText . $city;
                }

                return $road . ', ' . $buildingText . ' ' . $house;
            }

            if (!empty($data['display_name'])) {
                return $data['display_name'];
            }
        } catch (\Exception $e) {
            Log::error('Nominatim Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Проверить запрос к API Visicom.
     */
    public function checkVisicomRequest()
    {
        $url = "https://api.visicom.ua/data-api/5.0/uk/geocode.json?categories=adr_address&near=30.51043,50.45358&r=50&l=1&key=" . config("app.keyVisicom");

        $response = Http::get($url);

        if ($response->successful() && $response->json('type') === 'Feature') {
            $messageAdmin = "Проверка ключа Визикома успешна: " . json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $messageAdmin = "Ошибка проверки ключа Визикома: " . json_encode([
                    'error' => 'Invalid response from Visicom API',
                    'details' => $response->json(),
                    'status' => $response->status(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        (new MessageSentController)->sentMessageAdmin($messageAdmin);
    }

}

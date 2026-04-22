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
            $IP->email = null;
            $IP->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
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


    public function saveIPWithEmail($page, $email)
    {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            return response()->json(['error' => 'No remote address'], 400);
        }

        // Приоритет для CloudFlare
        $remoteAddr = $_SERVER['HTTP_CF_CONNECTING_IP'] ??  // CloudFlare реальный IP
            $_SERVER['HTTP_X_REAL_IP'] ??          // Nginx
            ($_SERVER['HTTP_X_FORWARDED_FOR'] ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : null) ??
            $_SERVER['REMOTE_ADDR'];

        $remoteAddr = trim($remoteAddr);

        // Валидация формата email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Invalid email format'], 400);
        }

        $IP = new IP();
        $IP->IP_ADDR = $remoteAddr;  // Теперь здесь будет реальный IP клиента
        $IP->email = $email;
        $IP->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $IP->page = $page;

        if ($IP->save()) {
            return response()->json(['success' => true, 'message' => 'Data saved successfully'], 200);
        }

        return response()->json(['error' => 'Failed to save data'], 500);
    }
    public function debugCloudFlareHeaders()
    {
        $headers = [
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
            'HTTP_CF_CONNECTING_IP' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,  // Реальный IP клиента
            'HTTP_CF_RAY' => $_SERVER['HTTP_CF_RAY'] ?? null,
            'HTTP_CF_VISITOR' => $_SERVER['HTTP_CF_VISITOR'] ?? null,
            'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'HTTP_X_REAL_IP' => $_SERVER['HTTP_X_REAL_IP'] ?? null,
        ];

        return response()->json($headers);
    }
    /**
     * Получить реальный IP клиента
     */
    private function getRealClientIp()
    {
        // Приоритет заголовков для получения реального IP
        $headers = [
            'HTTP_X_REAL_IP',        // Nginx proxy (самый надежный)
            'HTTP_X_FORWARDED_FOR',  // Стандартный прокси
            'HTTP_CF_CONNECTING_IP', // CloudFlare
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // Если в X-Forwarded-For несколько IP, берем первый (реальный клиент)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // Пропускаем Docker gateway IP (172.17.0.1)
                if ($ip !== '172.17.0.1' && filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        // Если все заголовки дали 172.17.0.1 - возвращаем его
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

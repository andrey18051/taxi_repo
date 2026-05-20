<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Throwable;

class WeatherController extends Controller
{
    public function temp()
    {
        try {
            $url = 'https://api.openweathermap.org/data/2.5/weather?q=Киев&appid=f5790978f87a638e2eee88a858c03ec4&units=metric&lang=ru';
            $httpResponse = Http::timeout(5)->get($url);
            if (!$httpResponse->successful()) {
                return '—';
            }
            $response = json_decode($httpResponse->body());
            if (!is_object($response) || !isset($response->main->temp)) {
                return '—';
            }

            return $response->main->temp;
        } catch (Throwable $e) {
            return '—';
        }
    }
}

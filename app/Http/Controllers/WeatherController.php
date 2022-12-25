<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeatherController extends Controller
{
    /**
     * @return \Illuminate\Http\Client\Response
     */
    public function temp()
    {
        $url = "https://api.openweathermap.org/data/2.5/weather?q=Киев&appid=f5790978f87a638e2eee88a858c03ec4&units=metric&lang=ru";
        $response = json_decode(Http::get($url));

        return $response->main->temp;
    }
}

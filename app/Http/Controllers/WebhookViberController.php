<?php

namespace App\Http\Controllers;

use App\Helpers\Viber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookViberController extends Controller
{
    public function index(Request $request, Viber $viber)
    {
        //Log::debug($request->input('user')['id']);
        //   Log::debug($request->all());
        $viber->sendMessage($request->input('user')['id'], 'Привіт, Я чат бот служби Таксі Лайт Юа 🚕! Я ще мало що вмію, але скоро навчуся бути корисним 😊');
    }
}

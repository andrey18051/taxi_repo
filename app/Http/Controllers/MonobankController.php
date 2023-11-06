<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonobankController extends Controller
{
    public function redirectUrl()
    {
        return view('mono.redirect');
    }
    public function webHookUrl(Request $request)
    {
        Log::debug($request->all);
    }

    public function errorView()
    {
        return view('fondy.error');
    }
    public function subscriptionView()
    {
        return view('fondy.subscription');
    }
    public function chargebackCallBack(Request $request)
    {
        Log::debug($request->all);
    }
}

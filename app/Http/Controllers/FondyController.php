<?php

namespace App\Http\Controllers;

use App\Models\Orderweb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FondyController extends Controller
{
    public function successView()
    {
        return view('fondy.success');
    }
    public function errorView()
    {
        return view('fondy.error');
    }
    public function subscriptionView()
    {
        return view('fondy.subscription');
    }
    public function callBack(Request $request)
    {
        Log::debug($request->all);
    }
    public function chargebackCallBack(Request $request)
    {
        Log::debug($request->all);
    }

    public function orderIdMemory($fondy_order_id, $uid)
    {
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
        $orderweb->fondy_order_id = $fondy_order_id;
        $orderweb->save();
    }
}

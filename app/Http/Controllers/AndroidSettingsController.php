<?php

namespace App\Http\Controllers;

use App\Models\AndroidSettings;
use Illuminate\Http\Request;

class AndroidSettingsController extends Controller
{
    public function getPaySystem(): array
    {
        $pay_settings = AndroidSettings::find("1");
        return ['pay_system' => $pay_settings->pay_system];
    }

    public function setPaySystem(Request $request)
    {
        $pay_settings = AndroidSettings::find("1");
        $pay_settings->pay_system = $request->pay_system;
        $pay_settings->save();
        return redirect()->route('index-black');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\IP;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class IPController extends Controller
{


    public function getIP()
    {
        $IP =  new IP();
        $IP->IP_ADDR = getenv("REMOTE_ADDR");
        $IP->page = 'https://m.easy-order-taxi.site';
        $IP->save();
    }

    public function getIPhomeCombo()
    {
        $IP =  new IP();
        $IP->IP_ADDR = getenv("REMOTE_ADDR");
        $IP->page = 'https://m.easy-order-taxi.site/home-Combo';
        $IP->save();
    }

    public function getIPhomeMapCombo()
    {
        $IP =  new IP();
        $IP->IP_ADDR = getenv("REMOTE_ADDR");
        $IP->page = 'https://m.easy-order-taxi.site/home-Map-Combo';
        $IP->save();
    }

}

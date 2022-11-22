<?php

namespace App\Http\Controllers;

use App\Models\IP;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class IPController extends Controller
{


    public function getIP($page)
    {
        $IP =  new IP();
        $IP->IP_ADDR = getenv("REMOTE_ADDR");
        $IP->page = 'https://m.easy-order-taxi.site' . $page;
        $IP->save();
    }
}

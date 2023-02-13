<?php

namespace App\Http\Controllers;

use App\Models\IP;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class IPController extends Controller
{
    /**
     * @param $page
     */

    public function getIP($page)
    {
           /* IP::where('IP_ADDR', '31.202.139.47')->delete();*/
        if (getenv("REMOTE_ADDR") !== '31.202.139.47') {
            $IP =  new IP();
            $IP->IP_ADDR = getenv("REMOTE_ADDR");
            $IP->page = 'https://m.easy-order-taxi.site' . $page;
            $IP->save();
        }
    }
}

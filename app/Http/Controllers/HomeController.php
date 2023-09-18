<?php

namespace App\Http\Controllers;

use App\Models\BlackList;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $listEmail = User::select('email')->get();
        $i = 0;
        foreach ($listEmail as $value) {
            $emailArray[$i++] = $value['email'];
        }

        $listBlack = BlackList::all()->toArray();
//        dd($listBlack);
        if ($listBlack == null) {
            $blackArray[0] = "no_email" ;
        } else {
            $blackList = BlackList::select('email')->get();
            $i = 0;
            foreach ($blackList as $value) {
                $blackArray[$i++] = $value['email'];
            }
        }


        return view('home', ['emailArray' => $emailArray, 'blackArray' => $blackArray]);
    }
}

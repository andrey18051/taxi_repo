<?php

namespace App\Http\Controllers;

use App\Models\BlackList;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class BlackListController extends Controller
{
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


        return view('admin.blacklist', ['emailArray' => $emailArray, 'blackArray' => $blackArray]);
    }

    public function addToBlacklist(Request $req)
    {
        $blackList = BlackList::where('email', $req->email)->first();
        if ($blackList == null) {
            $blackList = new BlackList();
            $blackList->email = $req->email;
            $blackList->save();
            return redirect()->route('index-black');
        }
    }

    public function deleteFromBlacklist(Request $req)
    {
        BlackList::where('email', $req->email)->delete();
        return redirect()->route('index-black');

    }
}

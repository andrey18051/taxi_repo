<?php

namespace App\Http\Controllers;

use App\Models\AndroidSettings;
use App\Models\BlackList;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $pay_settings = AndroidSettings::find("1");

        return view('admin.blacklist', ['emailArray' => $emailArray, 'blackArray' => $blackArray, 'pay_system' => $pay_settings->pay_system]);
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
        Log::info("deleteFromBlacklist function called with email: " . $req->email);

        // Attempting to delete the email from the blacklist
        $deletedRows = BlackList::where('email', $req->email)->delete();

        if ($deletedRows > 0) {
            Log::info("Successfully deleted email from blacklist: " . $req->email);
        } else {
            Log::warning("No entry found for email in blacklist: " . $req->email);
        }

        Log::info("Redirecting to the blacklist index page.");
        return redirect()->route('index-black');
    }

    public function addAndroidToBlacklist($email)
    {
        $blackList = BlackList::where('email', $email)->first();
        if ($blackList == null) {
            $blackList = new BlackList();
            $blackList->email = $email;
            $blackList->save();
        }
    }
    public function deleteAndroidFromBlacklist($email)
    {
        BlackList::where('email', $email)->delete();
    }
}

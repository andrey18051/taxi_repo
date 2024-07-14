<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserLocalApp;
use App\Models\UserTokenFmsS;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class UserLocalAppController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($email, $app, $local)
    {
        $user = User::where("email", $email)->first();
        $userLocal = UserLocalApp::where("user_id", $user->id)->first();
        if ($userLocal == null) {
            $userLocal = new UserLocalApp();
        }
        $userLocal->user_id = $user->id;
        switch ($app) {
            case "PAS1":
                $userLocal->local_app_pas_1 = $local;
                break;
            case "PAS2":
                $userLocal->local_app_pas_2 = $local;
                break;
            default:
                $userLocal->local_app_pas_4 = $local;
        }
        $userLocal->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

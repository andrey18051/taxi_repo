<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserTokenFmsS;
use Illuminate\Http\Request;

class UserTokenFmsController extends Controller
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
     */
    public function store($email, $app, $token)
    {
        $user = User::where("email", $email)->first();
        $userToken = UserTokenFmsS::where("user_id", $user->id)->first();
        if ($userToken == null) {
            $userToken = new UserTokenFmsS();
        }
        $userToken->user_id = $user->id;
        switch ($app) {
            case "PAS1":
                $userToken->token_app_pas_1 = $token;
                break;
            case "PAS2":
                $userToken->token_app_pas_2 = $token;
                break;
            default:
                $userToken->token_app_pas_4 = $token;
        }
        $userToken->save();
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

<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserTokenFmsS;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        if ($user) {
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
    }

    public function storeLocal($email, $app, $token, $local)
    {
        $user = User::where("email", $email)->first();
        if ($user) {
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
            (new UserLocalAppController)->store($email, $app, $local);
        }
    }

    /**
     * @param $title
     * @param $body
     * @param $app
     * @param $to
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage($body, $app, $user_id)
    {
        $userToken = UserTokenFmsS::where("user_id", $user_id)->first();
//        dd($userToken);
        if ($userToken != null) {
            switch ($app) {
                case "PAS1":
                    $to = $userToken->token_app_pas_1;
                    $firebaseApiKey = config("app.FIREBASE_API_KEY_PAS_1");
                    break;
                case "PAS2":
                    $to = $userToken->token_app_pas_2;
                    $firebaseApiKey = config("app.FIREBASE_API_KEY_PAS_2");
                    break;
                default:
                    $to = $userToken->token_app_pas_4;
                    $firebaseApiKey = config("app.FIREBASE_API_KEY_PAS_4");
            }

            $url  = "https://fcm.googleapis.com/v1/projects/taxieasyuaback4app/messages:send";
            $response = Http::withHeaders([
                'Authorization' => "Bearer ya29.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",
                'Content-Type' => 'application/json',
            ])->post($url, [
                'to' => $to, // FCM токен получателя
                'notification' => [
                    'title' => "title",
                    'body' => $body,
                ],
                'data' => "", // Дополнительные данные (если есть)
            ]);

            return response()->json([
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        }
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

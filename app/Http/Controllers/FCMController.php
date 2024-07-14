<?php

namespace App\Http\Controllers;

use App\Models\UserTokenFmsS;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FCMController extends Controller
{
    public function getUserByEmail($email, $app)
    {
        switch ($app) {
            case "PAS1":
                $firebaseAuth = app('firebase.auth')['app1'];
                break;
            case "PAS2":
                $firebaseAuth = app('firebase.auth')['app2'];
                break;
            default:
                $firebaseAuth = app('firebase.auth')['app4'];
        }
        switch ($app) {
            case "PAS1":
                $firebaseMessaging = app('firebase.messaging')['app1'];
                break;
            case "PAS2":
                $firebaseMessaging = app('firebase.messaging')['app2'];
                break;
            default:
                $firebaseMessaging = app('firebase.messaging')['app4'];
        }
        try {
            $user = $firebaseAuth->getUserByEmail($email);
            return $user;

//            $token = $firebaseMessaging->getDeviceToken($user->uid);
//            return $token;
        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
            return null;
        }
    }


    public function sendNotification($body, $app, $user_id)
    {
        $userToken = UserTokenFmsS::where("user_id", $user_id)->first();

        if ($userToken != null) {
            switch ($app) {
                case "PAS1":
                    $to = $userToken->token_app_pas_1;
                    $firebaseMessaging = app('firebase.messaging')['app1'];
                    break;
                case "PAS2":
                    $to = $userToken->token_app_pas_2;
                    $firebaseMessaging = app('firebase.messaging')['app2'];
                    break;
                default:
                    $to = $userToken->token_app_pas_4;
                    $firebaseMessaging = app('firebase.messaging')['app4'];
            }

            $message = CloudMessage::withTarget('token', $to)
                ->withNotification(Notification::create("Повідомлення", $body))
                ->withData(['key' => 'value']);

            $firebaseMessaging->send($message);

            return response()->json(['message' => 'Notification sent']);
        }

        return response()->json(['message' => 'User token not found'], 404);
    }
}



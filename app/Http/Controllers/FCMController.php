<?php

namespace App\Http\Controllers;

use App\Models\UserTokenFmsS;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FCMController extends Controller
{
    public function sendNotification($body, $app, $user_id)
    {
        $userToken = UserTokenFmsS::where("user_id", $user_id)->first();

        if ($userToken != null) {
            switch ($app) {
                case "PAS1":
                    $to = $userToken->token_app_pas_1;
                    break;
                case "PAS2":
                    $to = $userToken->token_app_pas_2;
                    break;
                default:
                    $to = $userToken->token_app_pas_4;
            }

            $messaging = app('firebase.messaging'); // Получите экземпляр firebase.messaging напрямую

            $message = CloudMessage::withTarget('token', $to)
                ->withNotification(Notification::create("Повідомлення", $body))
                ->withData(['key' => 'value']);

            $messaging->send($message);

            return response()->json(['message' => 'Notification sent']);
        }

        return response()->json(['message' => 'User token not found'], 404);
    }
}



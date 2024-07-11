<?php

namespace App\Http\Controllers;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FCMController extends Controller
{
    public function sendNotification()
    {
        $firebase = app('firebase');
        $messaging = $firebase->getMessaging();

        $message = CloudMessage::withTarget('token', 'your-recipient-token')
            ->withNotification(Notification::create('Заголовок', 'Сообщение'))
            ->withData(['key' => 'value']);

        $messaging->send($message);

        return response()->json(['message' => 'Notification sent']);
    }
}


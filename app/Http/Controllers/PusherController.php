<?php

namespace App\Http\Controllers;

use App\Events\OrderStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;


class PusherController extends Controller
{
//    public function testPush ($order_uid): array
//    {
//        Log::info("OrderStatusUpdated event: " . $order_uid);  // Логируем событие
//        event(new OrderStatusUpdated($order_uid));
//        Log::info("Broadcasting event: " . $order_uid);
//        return ["result" => "ok"];
//    }


    public function testPush($order_uid)
    {
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            ['cluster' => env('PUSHER_APP_CLUSTER')]
        );

        // Отправка события на канал
        $pusher->trigger('teal-towel-48', 'order-status-updated', ['message' => 'Order status updated for order UID: ' . $order_uid]);

        return response()->json(['result' => 'ok']);
    }

    public function test()
    {
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            ['cluster' => env('PUSHER_APP_CLUSTER')]
        );

        dd($pusher);  // Выведет информацию о объекте, если библиотека установлена
    }

    public function  sentUid($order_uid): \Illuminate\Http\JsonResponse
    {
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            ['cluster' => env('PUSHER_APP_CLUSTER')]
        );

        // Отправка события на канал
        $pusher->trigger('teal-towel-48', 'order-status-updated', ['order_uid' =>  $order_uid]);
        $messageAdmin = "Отправлен номер нового заказа в ПАС после пересоздания: " . $order_uid;
        (new MessageSentController)->sentMessageAdmin($messageAdmin);
        return response()->json(['result' => 'ok']);
    }

    public function  sentUidApp($order_uid, $app): \Illuminate\Http\JsonResponse
    {
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            ['cluster' => env('PUSHER_APP_CLUSTER')]
        );

        // Отправка события на канал
        Log::info("Pusher отправляет событие: order-status-updated-" . $app . " в канал teal-towel-48");

        $pusher->trigger('teal-towel-48', 'order-status-updated-'. $app, ['order_uid' =>  $order_uid]);
        $messageAdmin = "Отправлен номер нового заказа в $app после пересоздания: " . $order_uid;
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        return response()->json(['result' => 'ok']);
    }
}

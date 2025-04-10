<?php

namespace App\Http\Controllers;

use App\Events\OrderStatusUpdated;
use App\Models\DoubleOrder;
use App\Models\Orderweb;
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
        try {
            $pusher = new \Pusher\Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                [
                    'cluster' => env('PUSHER_APP_CLUSTER'),
                    'useTLS' => true
                ]
            );

            $response = $pusher->trigger('teal-towel-48', 'order-status-updated', [
                'message' => 'Order status updated for order UID: ' . $order_uid
            ]);

            return response()->json(['result' => $response ? 'ok' : 'fail']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
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
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        return response()->json(['result' => 'ok']);
    }

    public function sentUidAppEmail(
        $order_uid,
        $app,
        $email
    ): \Illuminate\Http\JsonResponse
    {
        try {
            // Инициализация Pusher
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                ['cluster' => env('PUSHER_APP_CLUSTER')]
            );

            // Подготовка данных для отправки
            $data = [
                'order_uid' => $order_uid,
                'app' => $app,
                'email' => $email,
            ];

            // Отправка события через Pusher
            $channel = 'teal-towel-48'; // Замените на нужный канал
            $event = 'order-status-updated-'. $app . "-$email";    // Замените на нужное событие

            $pusher->trigger($channel, $event, $data);

            Log::info("Событие $event успешно отправлено через Pusher для $email в $app. UID заказа: $order_uid");

            $messageAdmin = "Событие $event отправлен пользователю $email номер нового заказа в $app после пересоздания: $order_uid";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            return response()->json(['result' => 'ok' . $order_uid]);

        } catch (\Exception $e) {
            Log::error("Ошибка при отправке события через Pusher: {$e->getMessage()}", [
                'order_uid' => $order_uid,
                'app' => $app,
                'email' => $email
            ]);
            $messageAdmin = "Ошибка при отправке события через Pusher: {$e->getMessage()}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            return response()->json([
                'result' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sentUidAppEmailPayType(
        $order_uid,
        $app,
        $email,
        $pay_system
    ): \Illuminate\Http\JsonResponse
    {
        try {
            // Инициализация Pusher
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                ['cluster' => env('PUSHER_APP_CLUSTER')]
            );

            // Подготовка данных для отправки
            $data = [
                'order_uid' => $order_uid,
                'app' => $app,
                'email' => $email,
                'paySystemStatus' => $pay_system
            ];

            // Отправка события через Pusher
            $channel = 'teal-towel-48'; // Замените на нужный канал
            $event = 'order-status-updated-'. $app . "-$email";    // Замените на нужное событие

            $pusher->trigger($channel, $event, $data);

            Log::info("Событие $event успешно отправлено через Pusher для $email в $app. UID заказа: $order_uid");

            $messageAdmin = "Событие $event отправлен пользователю $email номер нового заказа в $app после пересоздания: $order_uid";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            return response()->json(['result' => 'ok' . $order_uid]);

        } catch (\Exception $e) {
            Log::error("Ошибка при отправке события через Pusher: {$e->getMessage()}", [
                'order_uid' => $order_uid,
                'app' => $app,
                'email' => $email
            ]);
            $messageAdmin = "Ошибка при отправке события через Pusher: {$e->getMessage()}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            return response()->json([
                'result' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendOrder($costMap, $app, $email): \Illuminate\Http\JsonResponse
    {
        try {
            // Initialize Pusher or any other service
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                ['cluster' => env('PUSHER_APP_CLUSTER')]
            );


            // Prepare the data for sending
            $data = $costMap;

            // Send the event via Pusher
            $channel = 'teal-towel-48'; // Замените на нужный канал
            $event = 'order-'. $app . "-$email";    // Замените на нужное событие

            $pusher->trigger($channel, $event, $data);

            Log::info("Event $event sent successfully via Pusher with order UID: {$costMap['dispatching_order_uid']}");

            // Optional: Send message to admin or log
            $messageAdmin = "Event $event sent for order UID: {$costMap['dispatching_order_uid']}";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            // Return a success response
            return response()->json(['result' => 'ok', 'order_uid' => $costMap['dispatching_order_uid']], 200);

        } catch (\Exception $e) {
            Log::error("Error sending Pusher event: {$e->getMessage()}", [
                'order_cost' => $costMap['order_cost'] ?? 'N/A',
                'dispatching_order_uid' => $costMap['dispatching_order_uid'] ?? 'N/A',
            ]);

            // Optional: Send error message to admin
            $messageAdmin = "Error sending Pusher event: {$e->getMessage()}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            return response()->json([
                'result' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function sendDoubleStatus(
        $response,
        $app,
        $email,
        $sticker
    ): \Illuminate\Http\JsonResponse
    {
        try {
            // Initialize Pusher or any other service
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                ['cluster' => env('PUSHER_APP_CLUSTER')]
            );


            // Prepare the data for sending
            $data = $response;

            // Send the event via Pusher
            $channel = 'teal-towel-48'; // Замените на нужный канал
            $event = 'orderResponseEvent-'. $app . "-$email";    // Замените на нужное событие

            $messageAdmin = "$sticker Событие sendDoubleStatus $event.  Данные: \n $data";

            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            $pusher->trigger($channel, $event, $data);


            // Return a success response
            return response()->json(['result' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::error("Error sending Pusher event: {$e->getMessage()}", [
                'order_cost' => $costMap['order_cost'] ?? 'N/A',
                'dispatching_order_uid' => $costMap['dispatching_order_uid'] ?? 'N/A',
            ]);

            // Optional: Send error message to admin
            $messageAdmin = "Error sending Pusher event: {$e->getMessage()}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            return response()->json([
                'result' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendOrderStartExecution(
        $doubleOrderId
    ): \Illuminate\Http\JsonResponse
    {
        try {
            $doubleOrderRecord = DoubleOrder::findOrFail($doubleOrderId);

            $responseBonusStr = $doubleOrderRecord->responseBonusStr;
            $responseBonus = json_decode($responseBonusStr, true);
            $order = $responseBonus['dispatching_order_uid'];

            $order = (new MemoryOrderChangeController)->show($order);
            $orderweb = Orderweb::where("dispatching_order_uid", $order)->first();

            $email = $orderweb->email;
            switch ($orderweb->comment) {
                case 'taxi_easy_ua_pas1':
                    $app = "PAS1";
                    break;
                case 'taxi_easy_ua_pas2':
                    $app = "PAS2";
                    break;
                default:
                    $app = "PAS4";
            }
            try {
                // Initialize Pusher or any other service
                $pusher = new Pusher(
                    env('PUSHER_APP_KEY'),
                    env('PUSHER_APP_SECRET'),
                    env('PUSHER_APP_ID'),
                    ['cluster' => env('PUSHER_APP_CLUSTER')]
                );


                // Prepare the data for sending
                $data = ["eventStartExecution" => $doubleOrderId];

                // Send the event via Pusher
                $channel = 'teal-towel-48'; // Замените на нужный канал
                $event = 'orderStartExecution-'. $app . "-$email";    // Замените на нужное событие
                $messageAdmin = "Событие $event отправлен пользователю $email запущена вилка в $app ";
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);

                $pusher->trigger($channel, $event, $data);


                // Return a success response
                return response()->json(['result' => 'ok'], 200);

            } catch (\Exception $e) {
                Log::error("Error sending Pusher event: {$e->getMessage()}");

                // Optional: Send error message to admin
                $messageAdmin = "Error sending Pusher event: {$e->getMessage()}";
                (new MessageSentController)->sentMessageAdmin($messageAdmin);

                return response()->json([
                    'result' => 'failed',
                    'error' => $e->getMessage()
                ], 500);
            }
            return response()->json(['message' => 'Order execution started', 'data' => $responseBonusStr]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Double order not found'], 404);
        }
    }





    public function  sentCostApp($order_cost, $app): \Illuminate\Http\JsonResponse
    {
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            ['cluster' => env('PUSHER_APP_CLUSTER')]
        );

        // Отправка события на канал
        Log::info("Pusher отправляет событие: order-status-updated-" . $app . " в канал teal-towel-48");

        $pusher->trigger('teal-towel-48', 'order-cost-'. $app, ['order_cost' =>  $order_cost]);
        $messageAdmin = "Отправлена стоимость нового заказа в $app: " . $order_cost;
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        return response()->json(['result' => 'ok']);
    }

    public function  sentCostAppEmail($order_cost, $app, $email): \Illuminate\Http\JsonResponse
    {
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            ['cluster' => env('PUSHER_APP_CLUSTER')]
        );

        // Отправка события на канал
        Log::info("Pusher отправляет событие: order-status-updated-" . $app . " в канал teal-towel-48");

        $pusher->trigger('teal-towel-48', 'order-cost-'. $app . "-" . $email, ['order_cost' =>  $order_cost]);
        $messageAdmin = "Отправлена стоимость нового заказа  клиенту $email в $app: " . $order_cost;
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        return response()->json(['result' => 'ok']);
    }

    /**
     * @throws \Pusher\PusherException
     * @throws \Pusher\ApiErrorException
     */
    public function  sentStatusWfp(
        $transactionStatus,
        $uid,
        $app,
        $email
    ): \Illuminate\Http\JsonResponse
    {
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            ['cluster' => env('PUSHER_APP_CLUSTER')]
        );

        // Отправка события на канал
        Log::info("Pusher отправляет событие: order-status-updated-" . $app . " в канал teal-towel-48");

        $data = [
            'transactionStatus' =>  $transactionStatus,
            'uid' =>$uid
        ];

        $pusher->trigger('teal-towel-48', 'transactionStatus-'. $app . "-" . $email, $data);
        $messageAdmin = "Отправлен transactionStatus  клиенту $email в $app: " . $transactionStatus;
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        return response()->json(['result' => 'ok']);
    }

    /**
     * @throws \Pusher\PusherException
     * @throws \Pusher\ApiErrorException
     */
    public function  sentCanceledStatus(
        $app,
        $email,
        $uid_bonusOrderHold
    ): \Illuminate\Http\JsonResponse
    {
        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            ['cluster' => env('PUSHER_APP_CLUSTER')]
        );

        $data = [
            'canceled' =>  "canceled",
            'uid' =>  $uid_bonusOrderHold,
        ];
        // Отправка события на канал
        Log::info("Pusher отправляет событие: eventCanceled-" . $app . " в канал teal-towel-48");

        $pusher->trigger('teal-towel-48', 'eventCanceled-'. $app . "-" . $email, $data);
        $messageAdmin = "Отправлен eventCanceled  клиенту $email в $app: ";
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        return response()->json(['result' => 'ok']);
    }
}

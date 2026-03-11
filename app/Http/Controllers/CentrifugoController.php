<?php

namespace App\Http\Controllers;

use App\Models\DoubleOrder;
use App\Models\Orderweb;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CentrifugoController extends Controller
{
    protected $apiUrl;
    protected $apiKey;
    protected $channel;


    public function __construct()
    {
        $this->apiUrl = 'http://localhost:8008/api';
        $this->apiKey = '0oBHyGSqni09Pzk-Hx5bHxdhjWPI1cV8Or-1UFF0IRtSgumKHqBEHaBWLps6KHu9_1SE-ZCyCfCHnr3f8IhSmQ';
        $this->channel = 'teal-towel-48';
    }

    /**
     * Отправка события в Centrifugo
     */
    private function trigger(string $event, array $data): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'apikey ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->withoutVerifying()
                ->post($this->apiUrl . '/publish', [
                    'channel' => $this->channel,
                    'data' => [
                        'event' => $event,
                        'data' => $data
                    ]
                ]);

            if ($response->successful()) {
                Log::info("Centrifugo event sent: {$event}", $data);
                return ['success' => true, 'result' => 'ok'];
            }

            Log::error('Centrifugo API error', [
                'event' => $event,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return ['success' => false, 'error' => 'API error'];

        } catch (\Exception $e) {
            Log::error('Centrifugo connection error', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Тестовый метод
     */
    public function test()
    {
        return response()->json([
            'api_url' => $this->apiUrl,
            'api_key' => substr($this->apiKey, 0, 5) . '...',
            'channel' => $this->channel
        ]);
    }

    /**
     * Отправка UID заказа
     */
    public function sentUid(string $order_uid): JsonResponse
    {
        $result = $this->trigger('order-status-updated', [
            'order_uid' => $order_uid
        ]);

        $messageAdmin = "Отправлен номер нового заказа в ПАС после пересоздания: " . $order_uid;
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        return response()->json(['result' => $result['success'] ? 'ok' : 'fail']);
    }

    /**
     * Отправка UID для конкретного приложения
     */
    public function sentUidApp(string $order_uid, string $app): JsonResponse
    {
        $event = 'order-status-updated-' . $app;

        Log::info("Centrifugo отправляет событие: {$event} в канал {$this->channel}");

        $result = $this->trigger($event, [
            'order_uid' => $order_uid
        ]);

        $messageAdmin = "Отправлен номер нового заказа в {$app} после пересоздания: " . $order_uid;
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        return response()->json(['result' => $result['success'] ? 'ok' : 'fail']);
    }

    /**
     * Отправка UID для приложения и email
     */
    public function sentUidAppEmail(string $order_uid, string $app, string $email): JsonResponse
    {
        try {
            $event = 'order-status-updated-' . $app . "-" . $email;

            $data = [
                'order_uid' => $order_uid,
                'app' => $app,
                'email' => $email,
            ];

            $result = $this->trigger($event, $data);

            Log::info("Событие {$event} успешно отправлено через Centrifugo для {$email} в {$app}. UID заказа: {$order_uid}");

            $messageAdmin = "Событие {$event} отправлен пользователю {$email} номер нового заказа в {$app} после пересоздания: {$order_uid}";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            return response()->json(['result' => 'ok' . $order_uid]);

        } catch (\Exception $e) {
            Log::error("Ошибка при отправке события через Centrifugo: {$e->getMessage()}", [
                'order_uid' => $order_uid,
                'app' => $app,
                'email' => $email
            ]);

            $messageAdmin = "Ошибка при отправке события через Centrifugo: {$e->getMessage()}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            return response()->json([
                'result' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отправка UID с типом оплаты
     */
    public function sentUidAppEmailPayType(string $order_uid, string $app, string $email, string $pay_system): JsonResponse
    {
        try {
            $event = 'order-status-updated-' . $app . "-" . $email;

            $data = [
                'order_uid' => $order_uid,
                'app' => $app,
                'email' => $email,
                'paySystemStatus' => $pay_system
            ];

            $result = $this->trigger($event, $data);

            Log::info("Событие {$event} успешно отправлено через Centrifugo для {$email} в {$app}. UID заказа: {$order_uid}");

            $messageAdmin = "Событие {$event} отправлен пользователю {$email} номер нового заказа в {$app} после пересоздания: {$order_uid}";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            return response()->json(['result' => 'ok' . $order_uid]);

        } catch (\Exception $e) {
            Log::error("Ошибка при отправке события через Centrifugo: {$e->getMessage()}", [
                'order_uid' => $order_uid,
                'app' => $app,
                'email' => $email
            ]);

            $messageAdmin = "Ошибка при отправке события через Centrifugo: {$e->getMessage()}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            return response()->json([
                'result' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отправка Double UID с типом оплаты
     */
    public function sentUidDoubleAppEmailPayType(string $order_uid, string $app, string $email, string $pay_system): JsonResponse
    {
        try {
            $event = 'orderDouble-status-updated-' . $app . "-" . $email;

            $data = [
                'orderDouble' => $order_uid,
                'app' => $app,
                'email' => $email,
                'paySystemStatus' => $pay_system
            ];

            $result = $this->trigger($event, $data);

            Log::info("Событие {$event} успешно отправлено через Centrifugo для {$email} в {$app}. UID заказа: {$order_uid}");

            $messageAdmin = "Событие {$event} отправлен пользователю {$email} номер нового заказа в {$app} после пересоздания: {$order_uid}";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            return response()->json(['result' => 'ok' . $order_uid]);

        } catch (\Exception $e) {
            Log::error("Ошибка при отправке события через Centrifugo: {$e->getMessage()}", [
                'order_uid' => $order_uid,
                'app' => $app,
                'email' => $email
            ]);

            $messageAdmin = "Ошибка при отправке события через Centrifugo: {$e->getMessage()}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            return response()->json([
                'result' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отправка статуса черного списка
     */
    public function sentActivateBlackUser(string $active, string $email): JsonResponse
    {
        try {
            $event = 'black-user-status--' . $email;

            $data = [
                'active' => $active,
                'email' => $email,
            ];

            $result = $this->trigger($event, $data);

            Log::info("Событие {$event} успешно отправлено через Centrifugo для {$email}. active: {$active}");

            $messageAdmin = "Событие {$event} успешно отправлено через Centrifugo для {$email}. active: {$active}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            return response()->json(['result' => 'ok' . $active]);

        } catch (\Exception $e) {
            Log::error("Ошибка при отправке события через Centrifugo: {$e->getMessage()}", [
                'active' => $active,
                'email' => $email,
            ]);

            $messageAdmin = "Ошибка при отправке события через Centrifugo: {$e->getMessage()}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            return response()->json([
                'result' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отправка заказа
     */
    public function sendOrder(array $costMap, string $app, string $email): JsonResponse
    {
        try {
            $event = 'order-' . $app . "-" . $email;

            $result = $this->trigger($event, $costMap);

            Log::info("Событие {$event} отправлено через Centrifugo с UID заказа: {$costMap['dispatching_order_uid']}");

            $messageAdmin = "Событие {$event} отправлено для UID заказа: {$costMap['dispatching_order_uid']}";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            return response()->json([
                'result' => 'ok',
                'order_uid' => $costMap['dispatching_order_uid']
            ], 200);

        } catch (\Exception $e) {
            Log::error("Ошибка отправки Centrifugo события: {$e->getMessage()}", [
                'order_uid' => $costMap['dispatching_order_uid'] ?? 'N/A',
            ]);

            $messageAdmin = "Ошибка отправки Centrifugo события: {$e->getMessage()}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            return response()->json([
                'result' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отправка авто-заказа
     */
    public function sendAutoOrder(array $costMap, string $app, string $email): JsonResponse
    {
        try {
            $event = 'orderAuto-' . $app . "-" . $email;

            $result = $this->trigger($event, $costMap);

            Log::info("Событие {$event} отправлено через Centrifugo с UID заказа: {$costMap['dispatching_order_uid']}");

            $messageAdmin = "Событие {$event} отправлено для UID заказа: {$costMap['dispatching_order_uid']}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            return response()->json([
                'result' => 'ok',
                'order_uid' => $costMap['dispatching_order_uid']
            ], 200);

        } catch (\Exception $e) {
            Log::error("Ошибка отправки Centrifugo события: {$e->getMessage()}", [
                'order_uid' => $costMap['dispatching_order_uid'] ?? 'N/A',
            ]);

            $messageAdmin = "Ошибка отправки Centrifugo события: {$e->getMessage()}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            return response()->json([
                'result' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отправка статуса стоимости
     */
    public function sentCostAppEmail(string $order_cost, string $app, string $email): JsonResponse
    {
        $event = 'order-cost-' . $app . "-" . $email;

        $result = $this->trigger($event, [
            'order_cost' => $order_cost
        ]);

        $messageAdmin = "Отправлена стоимость нового заказа клиенту {$email} в {$app}: " . $order_cost;
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        $user = User::where("email", $email)->first();
        if ($user) {
            // (new FCMController)->sendNotificationOrderCost($order_cost, $app, $user->id);
        }

        return response()->json(['result' => 'ok']);
    }

    /**
     * Отправка статуса транзакции
     */
    public function sentStatusWfp(string $transactionStatus, string $uid, string $app, string $email): JsonResponse
    {
        $event = 'transactionStatus-' . $app . "-" . $email;

        $data = [
            'transactionStatus' => $transactionStatus,
            'uid' => $uid
        ];

        $result = $this->trigger($event, $data);

        $messageAdmin = "Отправлен transactionStatus клиенту {$email} в {$app}: " . $transactionStatus;
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        return response()->json(['result' => 'ok']);
    }

    /**
     * Отправка статуса отмены
     */
    public function sentCanceledStatus(string $app, string $email, string $uid_bonusOrderHold): JsonResponse
    {
        $event = 'eventCanceled-' . $app . "-" . $email;

        $data = [
            'canceled' => "canceled",
            'uid' => $uid_bonusOrderHold,
        ];

        $result = $this->trigger($event, $data);

        $messageAdmin = "Отправлен eventCanceled клиенту {$email} в {$app}";
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        return response()->json(['result' => 'ok']);
    }

    /**
     * Отправка старта выполнения заказа
     */
    public function sendOrderStartExecution(int $doubleOrderId): JsonResponse
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
                case 'taxi_easy_ua_pas4':
                    $app = "PAS4";
                    break;
                default:
                    $app = "PAS5";
            }

            $event = 'orderStartExecution-' . $app . "-" . $email;

            $data = ["eventStartExecution" => $doubleOrderId];

            $result = $this->trigger($event, $data);

            $messageAdmin = "Событие {$event} отправлен пользователю {$email} запущена вилка в {$app}";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            return response()->json(['result' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::error("Error sending Centrifugo event: {$e->getMessage()}");

            $messageAdmin = "Error sending Centrifugo event: {$e->getMessage()}";
            (new MessageSentController)->sentMessageAdmin($messageAdmin);

            return response()->json([
                'result' => 'failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

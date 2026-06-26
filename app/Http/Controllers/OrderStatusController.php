<?php

namespace App\Http\Controllers;

use App\City\SimpleCashlessDispatchStatusSync;
use App\Models\ExecutionStatus;
use App\Models\Orderweb;
use App\Models\Uid_history;
use App\Services\OrderCarInfoHelper;
use App\Services\OrderStatusMessageResolver;
use App\Services\DispatchOrderCancelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderStatusController extends Controller
{


    public function __construct()
    {
        // В Laravel лучше использовать storage_path() для путей

    }

    private function log(string $message): void
    {
        // Используем встроенный в Laravel механизм логирования
        Log::info($message);

    }

    public function getOrderStatusMessage(string $currentState, string $nextState, int $closeReason = -1): string
    {
        $message = '';

        $this->log("Processing state transition: $currentState -> $nextState (closeReason: $closeReason)");

        switch ($currentState) {
            case 'SearchesForCar':
                switch ($nextState) {
                    case 'WaitingCarSearch':
                    case 'Canceled':
                    case 'CostCalculation':
                        $message = 'Поиск авто';
                        break;
                }
                break;

            case 'WaitingCarSearch':
                switch ($nextState) {
                    case 'Canceled':
                        $message = 'Поиск авто';
                        break;
                    case 'CarFound':
                        $message = 'Авто найдено';
                        break;
                }
                break;

            case 'CarFound':
                switch ($nextState) {
                    case 'CarFound':
                    case 'Running':
                    case 'CostCalculation':
                    case 'Canceled':
                        $message = 'Авто найдено';
                        break;
                }
                break;

            case 'Running':
                switch ($nextState) {
                    case 'CarFound':
                    case 'Running':
                    case 'CostCalculation':
                        $message = 'Авто найдено';
                        break;
                    case 'Executed':
                        $message = 'Заказ выполнен';
                        break;
                }
                break;

            case 'CostCalculation':
                switch ($nextState) {
                    case 'CarFound':
                    case 'Running':
                        $message = 'Авто найдено';
                        break;
                    case 'Canceled':
                    case 'CostCalculation':
                        $message = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
                        break;
                    case 'Executed':
                        $message = 'Заказ выполнен';
                        break;
                    case 'SearchesForCar':
                    case 'WaitingCarSearch':
                        $message = 'Поиск авто';
                        break;
                }
                break;

            case 'Canceled':
                switch ($nextState) {
                    case 'SearchesForCar':
                    case 'WaitingCarSearch':
                        $message = 'Поиск авто';
                        break;
                    case 'CarFound':
                    case 'Running':
                        $message = 'Авто найдено';
                        break;
                    case 'CostCalculation':
                    case 'Canceled':
                        $message = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
                        break;
                }
                break;

            case 'Executed':
                switch ($nextState) {
                    case 'SearchesForCar':
                    case 'WaitingCarSearch':
                    case 'CarFound':
                    case 'Running':
                    case 'CostCalculation':
                        $message = 'Заказ выполнен';
                        break;
                }
                break;

            default:
                $message = 'Неизвестное состояние';
                $this->log("Warning: Unknown current state: $currentState");
                break;
        }

        if ($message) {
            $this->log("Result: $message");
        } else {
            $this->log("Warning: No message determined for transition $currentState -> $nextState");
        }

        return $message;
    }

//    public function getOrderStatusMessageResult($currentOrderInput, $nextOrderInput): string
//    {
//        $message = '';
//
//        // Преобразуем входные данные в массивы
//        $currentOrder = is_string($currentOrderInput) ? json_decode($currentOrderInput, true) : $currentOrderInput;
//        $nextOrder = is_string($nextOrderInput) ? json_decode($nextOrderInput, true) : $nextOrderInput;
//
//        // Проверяем, что преобразование прошло успешно и данные являются массивами
//        if (!is_array($currentOrder) || !is_array($nextOrder)) {
//            $this->log("Error: Input parameters must be valid JSON strings or arrays");
//            return 'Ошибка обработки состояния';
//        }
//
//        // Извлекаем состояния
//        $currentState = $currentOrder['execution_status'] ?? '';
//        $nextState = $nextOrder['execution_status'] ?? '';
//
//        // Определяем closeReason: берем из того заказа, где status = Canceled, иначе -1
//        $closeReason = ($currentState === 'Canceled') ? ($currentOrder['close_reason'] ?? -1) :
//            ($nextState === 'Canceled') ? ($nextOrder['close_reason'] ?? -1) : -1;
//
//        $this->log("Processing state transition: $currentState -> $nextState (closeReason: $closeReason)");
//
//        // Логика на основе таблицы
//        switch ($currentState) {
//            case 'SearchesForCar':
//                switch ($nextState) {
//                    case 'SearchesForCar':
//                    case 'WaitingCarSearch':
//                    case 'Canceled':
//                    case 'CostCalculation':
//                        $message = 'Поиск авто';
//                        break;
//                    case 'CarFound':
//                    case 'Running':
//                        $message = 'Авто найдено';
//                        break;
//                }
//                break;
//
//            case 'WaitingCarSearch':
//                switch ($nextState) {
//                    case 'SearchesForCar':
//                    case 'WaitingCarSearch':
//                    case 'CostCalculation':
//                    case 'Canceled':
//                        $message = 'Поиск авто';
//                        break;
//                    case 'CarFound':
//                    case 'Running':
//                        $message = 'Авто найдено';
//                        break;
//                }
//                break;
//
//            case 'Running':
//            case 'CarFound':
//                switch ($nextState) {
//                    case 'SearchesForCar':
//                    case 'WaitingCarSearch':
//                    case 'CarFound':
//                    case 'Running':
//                    case 'Canceled':
//                    case 'CostCalculation':
//                        $message = 'Авто найдено';
//                        break;
//                }
//                break;
//
//            case 'CostCalculation':
//                switch ($nextState) {
//                    case 'SearchesForCar':
//                    case 'WaitingCarSearch':
//                        $message = 'Поиск авто';
//                        break;
//                    case 'CarFound':
//                    case 'Running':
//                        $message = 'Авто найдено';
//                        break;
//                    case 'CostCalculation':
//                    case 'Canceled':
//                        $message = ($closeReason != -1) ? 'Заказ снят' : 'Поиск авто';
//                        break;
//                    case 'Executed':
//                        $message = 'Заказ выполнен';
//                        break;
//                }
//                break;
//
//            case 'Canceled':
//                switch ($nextState) {
//                    case 'SearchesForCar':
//                    case 'WaitingCarSearch':
//                        $message = 'Поиск авто';
//                        break;
//                    case 'CarFound':
//                    case 'Running':
//                        $message = 'Авто найдено';
//                        break;
//                    case 'CostCalculation':
//                    case 'Canceled':
//                        $message = ($closeReason != -1) ? 'Заказ снят' : 'Поиск авто';
//                        break;
//                }
//                break;
//
//            case 'Executed':
//                switch ($nextState) {
//                    case 'SearchesForCar':
//                    case 'WaitingCarSearch':
//                    case 'CarFound':
//                    case 'Running':
//                    case 'CostCalculation':
//                        $message = 'Заказ выполнен';
//                        break;
//                }
//                break;
//
//            default:
//                $message = 'Неизвестное состояние';
//                $this->log("Warning: Unknown current state: $currentState");
//                break;
//        }
//
//        if ($message) {
//            $this->log("Result: $message");
//        } else {
//            $this->log("Warning: No message determined for transition $currentState -> $nextState");
//        }
//
//        return $message;
//    }

    public function testOrderStatusMessageResultWithRealData($currentOrderInput, $nextOrderInput): \Illuminate\Http\JsonResponse
    {
        $currentOrderBase = is_string($currentOrderInput) ? json_decode($currentOrderInput, true) : $currentOrderInput;
        $nextOrderBase = is_string($nextOrderInput) ? json_decode($nextOrderInput, true) : $nextOrderInput;

        if (!is_array($currentOrderBase) || !is_array($nextOrderBase)) {
            $this->log("Error: Input parameters must be valid JSON strings or arrays");
            return response()->json(['error' => 'Invalid input'], 400);
        }

        $statusSequence = [
            'SearchesForCar',
            'WaitingCarSearch',
            'CarFound',
            'Running',
            'CostCalculation',
            'Canceled',
            'Executed'
        ];

        $possibleCloseReasons = [-1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9];

        $this->log("Тест начат с реальными данными:");
        $this->log("Исходный наличный: " . json_encode($currentOrderBase));
        $this->log("Исходный БЕЗНАЛ: " . json_encode($nextOrderBase));

        $results = [];

        foreach ($statusSequence as $currentStatus) {
            foreach ($statusSequence as $nextStatus) {
                foreach ($possibleCloseReasons as $currentCloseReason) {
                    foreach ($possibleCloseReasons as $nextCloseReason) {
                        $currentOrder = $currentOrderBase;
                        $nextOrder = $nextOrderBase;

                        $currentOrder['execution_status'] = $currentStatus;
                        $currentOrder['close_reason'] = $currentCloseReason;
                        $nextOrder['execution_status'] = $nextStatus;
                        $nextOrder['close_reason'] = $nextCloseReason;

                        $this->log("\nТестируем комбинацию:");
                        $this->log("наличный: status={$currentOrder['execution_status']}, close_reason={$currentOrder['close_reason']}");
                        $this->log("БЕЗНАЛ: status={$nextOrder['execution_status']}, close_reason={$nextOrder['close_reason']}");

                        $result = $this->getOrderStatusMessageResult($currentOrder, $nextOrder);

                        if ($result === null) {
                            $this->log("Ошибка: результат обработки null");
                            continue;
                        }

                        // Определяем, какой заказ выбран
                        $selectedOrderSource = ($result === $currentOrder) ? 'наличный' :
                            (($result === $nextOrder) ? 'БЕЗНАЛ' : 'Неизвестный источник');

                        // Логируем результат с указанием источника
                        $this->log("Выбранный заказ: status={$result['execution_status']}, close_reason={$result['close_reason']} (Источник: $selectedOrderSource)");

                        $operatorAction = $this->simulateOperatorAction($result);

                        $results[] = [
                            'current_order' => [
                                'status' => $currentStatus,
                                'close_reason' => $currentCloseReason
                            ],
                            'next_order' => [
                                'status' => $nextStatus,
                                'close_reason' => $nextCloseReason
                            ],
                            'selected_order' => $result,
                            'operator_action' => $operatorAction,
                            'selected_source' => $selectedOrderSource // Добавляем в результат
                        ];
                    }
                }
            }
        }

        $this->log("Тест завершен");
        return response()->json(['results' => $results]);
    }

    public function getOrderStatusMessageResult($nalOrderInput, $cardOrderInput)
    {
        $nalOrder = json_decode($nalOrderInput, true);
        $cardOrder = json_decode($cardOrderInput, true);

        $nalState = $nalOrder['execution_status'] ?? 'SearchesForCar';
        $cardState = $cardOrder['execution_status'] ?? 'SearchesForCar';

        $resolved = $this->resolveLegStatuses($nalState, $cardState, $nalOrder, $cardOrder);
        $action = $resolved['action'];
        $response = $resolved['response_leg'] === 'card' ? $cardOrderInput : $nalOrderInput;
        $response = $this->addActionToResponse($response, $action);

        $messageAdmin = "getOrderStatusMessageResult action: {$action}, nalState: $nalState, cardState: $cardState";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        return $response;
    }

    /**
     * @return array{action: string, response_leg: string, close_reason: int, orderweb_close_reason: string}
     */
    private function resolveLegStatuses(
        string $nalState,
        string $cardState,
        ?array $nalOrder,
        ?array $cardOrder
    ): array {
        return (new OrderStatusMessageResolver())->resolve($nalState, $cardState, $nalOrder, $cardOrder);
    }

    private function applyResolvedStatusToOrderweb(Orderweb $orderweb, array $resolved, string $action): void
    {
        if ($action === OrderStatusMessageResolver::ACTION_CANCELED) {
            self::applyCanceledOrderweb($orderweb);

            return;
        }

        $activeActions = [
            OrderStatusMessageResolver::ACTION_SEARCH,
            OrderStatusMessageResolver::ACTION_CAR_FOUND,
            OrderStatusMessageResolver::ACTION_AT_ADDRESS,
            OrderStatusMessageResolver::ACTION_IN_ROUTE,
        ];

        if (in_array($action, $activeActions, true)) {
            $orderweb->closeReason = $resolved['orderweb_close_reason'];
            if ($action === OrderStatusMessageResolver::ACTION_SEARCH) {
                $orderweb->auto = null;
            }
        }

        if ($action === OrderStatusMessageResolver::ACTION_COMPLETED) {
            $orderweb->closeReason = '104';
        }
    }

//    public function getOrderStatusMessageResultPush($dispatching_order_uid)
//    {
//
//        $startTime = time(); // Запоминаем начальное время
//
//        do {
//            // Попробуем найти запись
//            $dispatching_order_uid = (new MemoryOrderChangeController)->show($dispatching_order_uid);
//            $uid_history = Uid_history::where("uid_bonusOrderHold", $dispatching_order_uid)->first();
//
//            if ($uid_history) {
//                // Если запись найдена, выходим из цикла
//                $nalOrderInput = $uid_history->double_status;
//                $cardOrderInput = $uid_history->bonus_status;
//                break;
//            } else {
//                $uid_history = Uid_history::where("uid_doubleOrder", $dispatching_order_uid)->first();
//
//                if ($uid_history) {
//                    // Если запись найдена, выходим из цикла
//                    $nalOrderInput = $uid_history->double_status;
//                    $cardOrderInput = $uid_history->bonus_status;
//                    $dispatching_order_uid = $uid_history->uid_bonusOrder;
//                    break;
//                }
//            }
//
//            // Ждём одну секунду перед следующим проверочным циклом
//            sleep(1);
//        } while (time() - $startTime < 60); // Проверяем, не прошло ли 60 секунд
//
//        if ($uid_history) {
//            $messageAdmin = "getOrderStatusMessageResultPush: nal: $nalOrderInput, card: $cardOrderInput";
//            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
//
//            $nalOrder = json_decode($nalOrderInput, true);
//            $cardOrder = json_decode($cardOrderInput, true);
//
//            $nalState = $nalOrder['execution_status'] ?? 'SearchesForCar';
//            $cardState = $cardOrder['execution_status'] ?? 'SearchesForCar';
//
//            $autoInfoNal =  $nalOrder['order_car_info']  ?? null;
//            $autoInfoCard =  $cardOrder['order_car_info']  ?? null;
//
//            $messageAdmin = "getOrderStatusMessageResultPush real: nalState: $nalState, cardState: $cardState";
//            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
//
//            $orderweb = Orderweb::where("dispatching_order_uid", $dispatching_order_uid)->first();
//
////            if ($orderweb && isset($orderweb->comment) && isset($orderweb->email)) {
//            if ($orderweb) {
//                $orderweb->auto = $autoInfoNal ?? $autoInfoCard ?? null;
////                switch ($orderweb->comment) {
////                    case 'taxi_easy_ua_pas1':
////                        $app = "PAS1";
////                        break;
////                    case 'taxi_easy_ua_pas2':
////                        $app = "PAS2";
////                        break;
////                    default:
////                        $app = "PAS4";
////                }
////                $email = $orderweb->email;
//                // Блок 1: Состояния "Поиск авто"
//                if (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch']) &&
//                    in_array($cardState, ['SearchesForCar', 'WaitingCarSearch'])) {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'SearchesForCar' && $cardState === 'CostCalculation') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'CostCalculation' && $cardState === 'SearchesForCar') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif ($nalState === 'Canceled' && $cardState === 'SearchesForCar') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif ($nalState === 'SearchesForCar' && $cardState === 'Canceled') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'Canceled' && $cardState === 'WaitingCarSearch') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif ($nalState === 'WaitingCarSearch' && $cardState === 'Canceled') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'CostCalculation' && in_array($cardState, ['SearchesForCar', 'WaitingCarSearch'])){
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch']) && $cardState === 'CostCalculation') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//
//                // Блок 2: Состояния "Авто найдено"
//                elseif ($nalState === 'SearchesForCar' && in_array($cardState, ['CarFound', 'Running'])) {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'SearchesForCar') {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'WaitingCarSearch' && in_array($cardState, ['CarFound', 'Running'])) {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'WaitingCarSearch') {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'CarFound' && in_array($cardState, ['CarFound', 'Running'])) {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif ($nalState === 'Running' && $cardState === 'CarFound') {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'Running' && $cardState === 'Running') {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif ($nalState === 'Canceled' && in_array($cardState, ['CarFound', 'Running'])) {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'Canceled') {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'CostCalculation' && in_array($cardState, ['CarFound', 'Running'])) {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'CostCalculation') {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//
//                // Блок 3: Состояния "Заказ выполнен"
//                elseif ($nalState === 'Executed' && in_array($cardState, ['SearchesForCar', 'WaitingCarSearch', 'CarFound', 'Running'])) {
//                    $action = 'Заказ выполнен';
//                    $orderweb->closeReason = "0";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch', 'CarFound', 'Running']) && $cardState === 'Executed') {
//                    $action = 'Заказ выполнен';
//                    $orderweb->closeReason = "0";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif ($nalState === 'Executed' && $cardState === 'CostCalculation') {
//                    $action = 'Заказ выполнен';
//                    $orderweb->closeReason = "0";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'CostCalculation' && $cardState === 'Executed') {
//                    $action = 'Заказ выполнен';
//                    $orderweb->closeReason = "0";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                // Блок 4: Состояния "Заказ снят" с проверкой close_reason
//                elseif ($nalState === 'Canceled' && $cardState === 'CostCalculation') {
//                    $closeReason = $nalOrder['close_reason'] ?? -1;
//                    $action = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
//                    $orderweb->closeReason = $closeReason;
//                    if ($closeReason == "-1") {
//                        $orderweb->auto = null;
//                    }
//                    $response = $nalOrderInput; // НАЛ
//
//                }
//                elseif ($nalState === 'CostCalculation' && $cardState === 'Canceled') {
//                    $closeReason = $cardOrder['close_reason'] ?? -1;
//                    $action = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
//                    $orderweb->closeReason = $closeReason;
//                    if ($closeReason == "-1") {
//                        $orderweb->auto = null;
//                    }
//                    $response = $cardOrderInput; // БЕЗНАЛ
//
//                }
//                elseif ($nalState === 'CostCalculation' && $cardState === 'CostCalculation') {
//                    $closeReasonNal = $nalOrder['close_reason'] ?? -1;
//                    $closeReasonCard = $cardOrder['close_reason'] ?? -1;
//                    if($closeReasonNal != -1 && $closeReasonCard != -1) {
//                        $action = 'Заказ снят';
//                        $orderweb->closeReason = "1";
//                    } else {
//                        $action = 'Поиск авто';
//                        $orderweb->auto = null;
//                        $orderweb->closeReason = "-1";
//                    }
//
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif ($nalState === 'Canceled' && $cardState === 'Canceled') {
//                    $closeReasonNal = $nalOrder['close_reason'] ?? -1;
//                    $closeReasonCard = $cardOrder['close_reason'] ?? -1;
//                    if($closeReasonNal != -1 && $closeReasonCard != -1) {
//                        $action = 'Заказ снят';
//                        $orderweb->closeReason = "1";
//                    } else {
//                        $action = 'Поиск авто';
//                        $orderweb->auto = null;
//                        $orderweb->closeReason = "-1";
//                    }
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                } else {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput;
//                }
//                $orderweb->save();
//
//                $response = $this->addActionToResponseUid($response, $action, $dispatching_order_uid);
//
//                $messageAdmin = "getOrderStatusMessageResultPush response: {$response}";
//                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
//
//                $response_arr = json_decode($response, true);
//                if (isset($response_arr["order_car_info"]) && $response_arr["order_car_info"] != null) {
//                    $orderweb->auto = $response_arr["order_car_info"];
//                    $orderweb->closeReason = -1;
//                } else if (isset($response_arr["action"]) && $response_arr["action"] == "Заказ снят") {
//                    $orderweb->closeReason = 1;
//                } else {
//
//                    $orderweb->closeReason = $response_arr["close_reason"] ?? -1; // Значение по умолчанию, если close_reason тоже отсутствует
//                }
//
//                $orderweb->save();
//
//                $messageAdmin = "getOrderStatusMessageResultPush action: {$action}, nalState: $nalState, cardState: $cardState";
//                (new MessageSentController)->sentMessageAdmin($messageAdmin);
////
//        $messageAdmin = "getOrderStatusMessageResult response: dispatching_order_uid ". $response ;
//        (new MessageSentController)->sentMessageAdmin($messageAdmin);
//
////                (new PusherController)->sendDoubleStatus($response, $app, $email, "2222 getOrderStatusMessageResult ");
//                return $response;
//            }
//        }
//
//
//
//
//
//
//
//
//
//
//
//    }

//    public function getOrderStatusMessageResultPush($dispatching_order_uid)
//    {
//
//        $startTime = time(); // Запоминаем начальное время
//
//        do {
//            // Попробуем найти запись
//            $dispatching_order_uid = (new MemoryOrderChangeController)->show($dispatching_order_uid);
//            $uid_history = Uid_history::where("uid_bonusOrderHold", $dispatching_order_uid)->first();
//
//            if ($uid_history) {
//                // Если запись найдена, выходим из цикла
//                $nalOrderInput = $uid_history->double_status;
//                $cardOrderInput = $uid_history->bonus_status;
//                break;
//            } else {
//
//                $uid_history = Uid_history::where("uid_bonusOrder", $dispatching_order_uid)->first();
//
//                if ($uid_history) {
//                    // Если запись найдена, выходим из цикла
//                    $nalOrderInput = $uid_history->double_status;
//                    $cardOrderInput = $uid_history->bonus_status;
//                    $dispatching_order_uid = $uid_history->uid_bonusOrder;
//                    break;
//                }
//                $uid_history = Uid_history::where("uid_doubleOrder", $dispatching_order_uid)->first();
//
//                if ($uid_history) {
//                    // Если запись найдена, выходим из цикла
//                    $nalOrderInput = $uid_history->double_status;
//                    $cardOrderInput = $uid_history->bonus_status;
//                    $dispatching_order_uid = $uid_history->uid_bonusOrder;
//                    break;
//                }
//            }
//
//            // Ждём одну секунду перед следующим проверочным циклом
//            sleep(1);
//        } while (time() - $startTime < 60); // Проверяем, не прошло ли 60 секунд
//
//        if ($uid_history) {
//            $messageAdmin = "getOrderStatusMessageResultPush: nal: $nalOrderInput, card: $cardOrderInput";
//            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
//
//            if ($uid_history->cancel == "1") {
//                $response = $cardOrderInput; // БЕЗНАЛ
//                $action = 'Заказ снят';
//                $response = $this->addActionToResponseUid($response, $action, $dispatching_order_uid);
//
//                $messageAdmin = "getOrderStatusMessageResultPush response: {$response}";
//                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
//                return $response;
//            }
//
//
//            $nalOrder = json_decode($nalOrderInput, true);
//            $cardOrder = json_decode($cardOrderInput, true);
//
//            $nalState = $nalOrder['execution_status'] ?? 'SearchesForCar';
//            $cardState = $cardOrder['execution_status'] ?? 'SearchesForCar';
//
//            $autoInfoNal =  $nalOrder['order_car_info']  ?? null;
//            $autoInfoCard =  $cardOrder['order_car_info']  ?? null;
//
//            $messageAdmin = "getOrderStatusMessageResultPush real: nalState: $nalState, cardState: $cardState";
//            (new MessageSentController)->sentMessageAdminLog($messageAdmin);
//
//            $orderweb = Orderweb::where("dispatching_order_uid", $dispatching_order_uid)->first();
//
//            if ($orderweb) {
//
//
//                $orderweb->auto = $autoInfoNal ?? $autoInfoCard ?? null;
//
//                // Блок 1: Состояния "Поиск авто"
//                if (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch']) &&
//                    in_array($cardState, ['SearchesForCar', 'WaitingCarSearch'])) {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'SearchesForCar' && $cardState === 'CostCalculation') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'CostCalculation' && $cardState === 'SearchesForCar') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif ($nalState === 'Canceled' && $cardState === 'SearchesForCar') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif ($nalState === 'SearchesForCar' && $cardState === 'Canceled') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'Canceled' && $cardState === 'WaitingCarSearch') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif ($nalState === 'WaitingCarSearch' && $cardState === 'Canceled') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'CostCalculation' && in_array($cardState, ['SearchesForCar', 'WaitingCarSearch'])){
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch']) && $cardState === 'CostCalculation') {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//
//                // Блок 2: Состояния "Авто найдено"
//                elseif ($nalState === 'SearchesForCar' && in_array($cardState, ['CarFound', 'Running'])) {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'SearchesForCar') {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'WaitingCarSearch' && in_array($cardState, ['CarFound', 'Running'])) {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'WaitingCarSearch') {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'CarFound' && in_array($cardState, ['CarFound', 'Running'])) {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif ($nalState === 'Running' && $cardState === 'CarFound') {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'Running' && $cardState === 'Running') {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif ($nalState === 'Canceled' && in_array($cardState, ['CarFound', 'Running'])) {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'Canceled') {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'CostCalculation' && in_array($cardState, ['CarFound', 'Running'])) {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'CostCalculation') {
//                    $action = 'Авто найдено';
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput; // НАЛ
//                }
//
//                // Блок 3: Состояния "Заказ выполнен"
//                elseif ($nalState === 'Executed' && in_array($cardState, ['SearchesForCar', 'WaitingCarSearch', 'CarFound', 'Running'])) {
//                    $action = 'Заказ выполнен';
//                    $orderweb->closeReason = "0";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch', 'CarFound', 'Running']) && $cardState === 'Executed') {
//                    $action = 'Заказ выполнен';
//                    $orderweb->closeReason = "0";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                elseif ($nalState === 'Executed' && $cardState === 'CostCalculation') {
//                    $action = 'Заказ выполнен';
//                    $orderweb->closeReason = "0";
//                    $response = $nalOrderInput; // НАЛ
//                }
//                elseif ($nalState === 'CostCalculation' && $cardState === 'Executed') {
//                    $action = 'Заказ выполнен';
//                    $orderweb->closeReason = "0";
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                }
//                // Блок 4: Состояния "Заказ снят" с проверкой close_reason
//                elseif ($nalState === 'Canceled' && $cardState === 'CostCalculation') {
//                    $closeReason = $nalOrder['close_reason'] ?? -1;
//                    $action = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
//                    $orderweb->closeReason = $closeReason;
//                    if ($closeReason == "-1") {
//                        $orderweb->auto = null;
//                    }
//                    $response = $nalOrderInput; // НАЛ
//
//                }
//                elseif ($nalState === 'CostCalculation' && $cardState === 'Canceled') {
//                    $closeReason = $cardOrder['close_reason'] ?? -1;
//                    $action = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
//                    $orderweb->closeReason = $closeReason;
//                    if ($closeReason == "-1") {
//                        $orderweb->auto = null;
//                    }
//                    $response = $cardOrderInput; // БЕЗНАЛ
//
//                }
//                elseif ($nalState === 'CostCalculation' && $cardState === 'CostCalculation') {
//                    $closeReasonNal = $nalOrder['close_reason'] ?? -1;
//                    $closeReasonCard = $cardOrder['close_reason'] ?? -1;
//                    if($closeReasonNal != -1 && $closeReasonCard != -1) {
//                        $action = 'Заказ снят';
//                        $orderweb->closeReason = "1";
//                    } else {
//                        $action = 'Поиск авто';
//                        $orderweb->auto = null;
//                        $orderweb->closeReason = "-1";
//                    }
//
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                } elseif ($nalState === 'Canceled' && $cardState === 'Canceled') {
//                    $closeReasonNal = $nalOrder['close_reason'] ?? -1;
//                    $closeReasonCard = $cardOrder['close_reason'] ?? -1;
//                    if ($closeReasonNal != -1 && $closeReasonCard != -1) {
//                        $action = 'Заказ снят';
//                        $orderweb->closeReason = "1";
//                    } else {
//                        $action = 'Поиск авто';
//                        $orderweb->auto = null;
//                        $orderweb->closeReason = "-1";
//                    }
//                    $response = $cardOrderInput; // БЕЗНАЛ
//                } else {
//                    $action = 'Поиск авто';
//                    $orderweb->auto = null;
//                    $orderweb->closeReason = "-1";
//                    $response = $nalOrderInput;
//                }
//                $orderweb->save();
//
//                $response = $this->addActionToResponseUid($response, $action, $dispatching_order_uid);
//
//                $messageAdmin = "getOrderStatusMessageResultPush response: {$response}";
//                (new MessageSentController)->sentMessageAdminLog($messageAdmin);
//
//                $response_arr = json_decode($response, true);
//                if (isset($response_arr["order_car_info"]) && $response_arr["order_car_info"] != null) {
//                    $orderweb->auto = $response_arr["order_car_info"];
//                    $orderweb->closeReason = -1;
//                } else if (isset($response_arr["action"]) && $response_arr["action"] == "Заказ снят") {
//                    $orderweb->closeReason = 1;
//                } else {
//
//                    $orderweb->closeReason = $response_arr["close_reason"] ?? -1; // Значение по умолчанию, если close_reason тоже отсутствует
//                }
//
//                $orderweb->save();
//
//                $messageAdmin = "getOrderStatusMessageResultPush action: {$action}, nalState: $nalState, cardState: $cardState";
//                (new MessageSentController)->sentMessageAdmin($messageAdmin);
////
//                $messageAdmin = "getOrderStatusMessageResult response: dispatching_order_uid ". $response ;
//                (new MessageSentController)->sentMessageAdmin($messageAdmin);
//
////                (new PusherController)->sendDoubleStatus($response, $app, $email, "2222 getOrderStatusMessageResult ");
//                return $response;
//            }
//        }
//    }

    public function cityAndApp($order) {
        $city = $order->city;
        Log::info("Город заказа: " . $city);

        // Выбор приложения по комментарию
        switch ($order->comment) {
            case "taxi_easy_ua_pas1":
                $application = "PAS1";
                break;
            case "taxi_easy_ua_pas2":
                $application = "PAS2";
                break;
            default:
                $application = "PAS4";
                break;
        }
        Log::info("Приложение выбрано: " . $application);

        // Переписываем город для определенных случаев
        $originalCity = $city;
        switch ($originalCity) {
            case "city_kiev":
                $city = "Kyiv City";
                break;
            case "city_cherkassy":
                $city = "Cherkasy Oblast";
                break;
            case "city_odessa":
                $city = "Odessa";
                break;
            case "city_zaporizhzhia":
                $city = "Zaporizhzhia";
                break;
            case "city_dnipro":
                $city = "Dnipropetrovsk Oblast";
                break;
            case "city_lviv":
                $city = "Lviv";
                break;
            case "city_ivano_frankivsk":
                $city = "Ivano_frankivsk";
                break;
            case "city_vinnytsia":
                $city = "Vinnytsia";
                break;
            case "city_poltava":
                $city = "Poltava";
                break;
            case "city_sumy":
                $city = "Sumy";
                break;
            case "city_kharkiv":
                $city = "Kharkiv";
                break;
            case "city_chernihiv":
                $city = "Chernihiv";
                break;
            case "city_rivne":
                $city = "Rivne";
                break;
            case "city_ternopil":
                $city = "Ternopil";
                break;
            case "city_khmelnytskyi":
                $city = "Khmelnytskyi";
                break;
            case "city_zakarpattya":
                $city = "Zakarpattya";
                break;
            case "city_zhytomyr":
                $city = "Zhytomyr";
                break;
            case "city_kropyvnytskyi":
                $city = "Kropyvnytskyi";
                break;
            case "city_mykolaiv":
                $city = "Mykolaiv";
                break;
            case "city_chernivtsi":
                $city = "Chernivtsi";
                break;
            case "city_lutsk":
                $city = "Lutsk";
                break;
            default:
                $city = "all";
        }

        Log::info("Город изменен с {$originalCity} на {$city}");

        return [
            'city'=> $city,
            'application'=> $application

        ];
    }

    /**
     * Один проход по Uid_history без ожидания на сервере.
     *
     * @return array{0: ?Uid_history, 1: ?string, 2: ?string, 3: string}
     */
    private function resolveUidHistoryForStatusPush(string $uid): array
    {
        $dispatching_order_uid = $uid;
        $columns = ['uid_bonusOrderHold', 'uid_bonusOrder', 'uid_doubleOrder'];

        foreach ($columns as $column) {
            $row = Uid_history::where($column, $uid)->first();
            if (!$row) {
                continue;
            }
            $nalOrderInput = $row->double_status;
            $cardOrderInput = $row->bonus_status;
            if (!empty($row->uid_bonusOrder)) {
                $dispatching_order_uid = $row->uid_bonusOrder;
            }
            if ($cardOrderInput !== null) {
                Log::info('resolveUidHistoryForStatusPush: ready', ['column' => $column]);
                return [$row, $nalOrderInput, $cardOrderInput, $dispatching_order_uid];
            }
        }

        return [null, null, null, $dispatching_order_uid];
    }

    /**
     * Одиночный безнал: live GET dispatch на каждый опрос (как uid_history у вилки).
     */
    private function buildSimpleCashlessLiveStatusPushResponse(Orderweb $orderweb, string $uid): ?\Illuminate\Http\JsonResponse
    {
        if (!SimpleCashlessDispatchStatusSync::shouldLiveSync($orderweb)) {
            return null;
        }

        $snapshot = SimpleCashlessDispatchStatusSync::fetchDispatchSnapshot($orderweb, $uid);
        if ($snapshot === null) {
            return null;
        }

        $executionStatus = (string) ($snapshot['execution_status'] ?? 'SearchesForCar');
        $resolved = $this->resolveLegStatuses(
            $executionStatus,
            $executionStatus,
            $snapshot,
            $snapshot
        );
        $action = $resolved['action'];

        if (self::isDispatchCanceledForClientCancel($snapshot, $uid)
            && !self::hasActiveDispatchLeg($snapshot, $snapshot)) {
            if (self::shouldSkipCancelForSupersededUid($orderweb, $uid)) {
                return null;
            }
            if (!self::shouldDeferDispatchCancelForGooglePayHold($orderweb, $snapshot, $uid)) {
                self::finalizeCanceledFromStatusPush($orderweb, $uid);
                $orderweb->save();

                return response()->json([
                    'action' => OrderStatusMessageResolver::ACTION_CANCELED,
                    'close_reason' => (int) ($snapshot['close_reason'] ?? 1),
                    'dispatching_order_uid' => $uid,
                    'uid' => $uid,
                    'execution_status' => 'Canceled',
                ]);
            }
            $snapshot['execution_status'] = 'SearchesForCar';
            $executionStatus = 'SearchesForCar';
            $resolved = $this->resolveLegStatuses(
                $executionStatus,
                $executionStatus,
                $snapshot,
                $snapshot
            );
            $action = $resolved['action'];
        }

        SimpleCashlessDispatchStatusSync::applySnapshotToOrderweb($orderweb, $snapshot);
        $this->applyResolvedStatusToOrderweb($orderweb, $resolved, $action);
        self::syncOrderwebAfterStatusPush($orderweb, $action, $snapshot, $snapshot, $uid);
        $orderweb->save();

        $dispatchJson = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
        $response = $this->addActionToResponseUid($dispatchJson, $action, $uid);
        if ($resolved['close_reason'] >= 0) {
            $response = $this->patchCloseReasonInResponse($response, $resolved['close_reason']);
        }

        Log::info('Simple cashless live dispatch status', [
            'uid' => $uid,
            'action' => $action,
            'execution_status' => $executionStatus,
        ]);

        $payload = json_decode($response, true);

        return is_array($payload)
            ? response()->json($payload)
            : response()->json(['action' => $action, 'uid' => $uid]);
    }

    /**
     * Ответ по кэшу orderweb, когда uid_history ещё недоступен.
     */
    private function buildCachedStatusPushResponse(Orderweb $orderweb, string $uid): \Illuminate\Http\JsonResponse
    {
        $closeReason = (string) $orderweb->closeReason;

        if (!OrderCarInfoHelper::isCachedStageCloseReason($closeReason)) {
            $carInfo = OrderCarInfoHelper::formatForApp($orderweb->auto);
            if ($carInfo !== null) {
                return response()->json(array_merge([
                    'action' => OrderStatusMessageResolver::ACTION_CAR_FOUND,
                    'close_reason' => 101,
                    'dispatching_order_uid' => $uid,
                    'uid' => $uid,
                    'time_to_start_point' => $orderweb->time_to_start_point,
                ], $carInfo));
            }

            return response()->json([
                'action' => OrderStatusMessageResolver::ACTION_SEARCH,
                'dispatching_order_uid' => $uid,
                'uid' => $uid,
            ]);
        }

        $responseArr = [
            'close_reason' => $orderweb->closeReason,
            'dispatching_order_uid' => $uid,
            'uid' => $uid,
            'action' => OrderCarInfoHelper::actionFromCloseReason($closeReason),
        ];

        $carInfo = OrderCarInfoHelper::formatForApp($orderweb->auto);
        if ($carInfo !== null) {
            $responseArr = array_merge($responseArr, $carInfo);
            $responseArr['time_to_start_point'] = $orderweb->time_to_start_point;
        }

        Log::info('Exiting function (cached orderweb)', $responseArr);

        return response()->json($responseArr);
    }

    private static function finalizeCanceledFromStatusPush(Orderweb $orderweb, string $uid): void
    {
        if (self::shouldSkipCancelForSupersededUid($orderweb, $uid)) {
            return;
        }
        self::applyCanceledOrderweb($orderweb);
        self::notifyForkOrderCanceledPush($orderweb, $uid);
    }

    /**
     * Add-cost recreation cancels the old dispatch uid while the same orderweb row
     * already points at the new uid — do not mark the live order canceled.
     */
    private static function shouldSkipCancelForSupersededUid(Orderweb $orderweb, string $uid): bool
    {
        if (!$orderweb->exists) {
            return false;
        }

        $orderweb->refresh();
        $currentUid = (string) $orderweb->dispatching_order_uid;
        $polledUid = (string) $uid;

        if ($currentUid !== $polledUid) {
            Log::info('Skip cancel: orderweb uid differs from polled uid (add-cost recreation)', [
                'polled_uid' => $polledUid,
                'orderweb_uid' => $currentUid,
                'order_id' => $orderweb->id,
            ]);

            return true;
        }

        $latestUid = (string) (new MemoryOrderChangeController())->findLatestOrderUid($polledUid);
        if ($latestUid !== $polledUid && $currentUid === $latestUid) {
            Log::info('Skip cancel: polled uid superseded by active order', [
                'polled_uid' => $polledUid,
                'latest_uid' => $latestUid,
                'order_id' => $orderweb->id,
            ]);

            return true;
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function getOrderStatusMessageResultPush($dispatching_order_uid)
    {
        Log::info('Entering getOrderStatusMessageResultPush', ['dispatching_order_uid' => $dispatching_order_uid]);

        // Попробуем найти запись
        $uid = (new MemoryOrderChangeController)->show($dispatching_order_uid);
        Log::debug('Updated dispatching_order_uid from MemoryOrderChangeController', ['dispatching_order_uid' => $dispatching_order_uid]);

        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
        Log::debug('Fetched orderweb', ['orderweb' => $orderweb ? $orderweb->toArray() : null]);

        if ($orderweb) {
            $isCanceledOrderweb = in_array((string) $orderweb->closeReason, ['1', '2', '3', '4', '5', '6', '7', '9'], true);
            if ($isCanceledOrderweb) {
                Log::info('Exiting function: orderweb canceled', [
                    'close_reason' => $orderweb->closeReason,
                    'dispatching_order_uid' => $uid,
                    'uid' => $uid,
                    'action' => OrderStatusMessageResolver::ACTION_CANCELED,
                ]);

                return response()->json([
                    'action' => OrderStatusMessageResolver::ACTION_CANCELED,
                    'close_reason' => (int) $orderweb->closeReason,
                    'dispatching_order_uid' => $uid,
                    'uid' => $uid,
                    'execution_status' => 'Canceled',
                ]);
            }
        }

        // Сначала uid_history — кэш closeReason не должен перекрывать актуальное состояние vod.
        [$uid_history, $nalOrderInput, $cardOrderInput, $dispatching_order_uid] = $this->resolveUidHistoryForStatusPush($uid);

        Log::debug('Uid_history lookup (non-blocking)', ['found' => $uid_history !== null]);

        if (!$uid_history || $cardOrderInput === null) {
            if ($orderweb) {
                $liveResponse = $this->buildSimpleCashlessLiveStatusPushResponse($orderweb, $uid);
                if ($liveResponse !== null) {
                    return $liveResponse;
                }
            }

            Log::info('Uid_history not ready, cached orderweb response', ['uid' => $uid]);
            if ($orderweb) {
                return $this->buildCachedStatusPushResponse($orderweb, $uid);
            }

            return response()->json(['action' => OrderStatusMessageResolver::ACTION_SEARCH]);
        }

        if ($uid_history) {
            Log::info('Processing uid_history', ['nalOrderInput' => $nalOrderInput, 'cardOrderInput' => $cardOrderInput]);

            if ($orderweb && self::shouldRefreshForkLegSnapshotsForStatusPush($uid_history, $orderweb)) {
                $this->refreshForkLegSnapshotsForStatusPush($uid_history, $orderweb);
                $uid_history->refresh();
                $nalOrderInput = $uid_history->double_status;
                $cardOrderInput = $uid_history->bonus_status;
            }

            $nalOrder = json_decode($nalOrderInput, true);
            $cardOrder = json_decode($cardOrderInput, true);



            Log::debug('Decoded orders', [
                'nalOrder' => $nalOrder,
                'cardOrder' => $cardOrder
            ]);

            $nalState = $nalOrder['execution_status'] ?? 'SearchesForCar';
            $cardState = $cardOrder['execution_status'] ?? 'SearchesForCar';
            Log::info('Extracted states', ['nalState' => $nalState, 'cardState' => $cardState]);

            $autoInfoNal = $nalOrder['order_car_info'] ?? null;
            $autoInfoCard = $cardOrder['order_car_info'] ?? null;
            Log::debug('Extracted auto info', ['autoInfoNal' => $autoInfoNal, 'autoInfoCard' => $autoInfoCard]);

            if (self::isExplicitForkCancelRequested($uid_history)) {
                if (self::areForkLegSnapshotsCancelSettled($cardOrder, $nalOrder)) {
                    $action = 'Заказ снят';
                    self::finalizeCanceledFromStatusPush($orderweb, $dispatching_order_uid);
                    $orderweb->save();
                    $response = $this->addActionToResponseUid($cardOrderInput, $action, $dispatching_order_uid);
                    Log::info('Order canceled (explicit fork cancel settled)', [
                        'action' => $action,
                        'dispatching_order_uid' => $dispatching_order_uid,
                        'cardState' => $cardState,
                        'nalState' => $nalState,
                    ]);
                    Log::info('Exiting function due to cancel', ['response' => $response]);

                    return $response;
                }

                Log::info('Fork explicit cancel pending dispatch settlement', [
                    'dispatching_order_uid' => $dispatching_order_uid,
                    'cardState' => $cardState,
                    'nalState' => $nalState,
                    'card_close_reason' => $cardOrder['close_reason'] ?? null,
                    'nal_close_reason' => $nalOrder['close_reason'] ?? null,
                ]);
            } elseif (!self::hasActiveDispatchLeg($cardOrder, $nalOrder)
                && ($uid_history->cancel == "1"
                || self::isDispatchOrderCanceled($cardOrder)
                || self::isDispatchOrderCanceled($nalOrder))) {
                $action = 'Заказ снят';
                self::finalizeCanceledFromStatusPush($orderweb, $dispatching_order_uid);
                $orderweb->save();
                $response = $this->addActionToResponseUid($cardOrderInput, $action, $dispatching_order_uid);
                Log::info('Order canceled', [
                    'action' => $action,
                    'dispatching_order_uid' => $dispatching_order_uid,
                    'cardState' => $cardState,
                    'nalState' => $nalState,
                ]);
                Log::info('Exiting function due to cancel', ['response' => $response]);
                return $response;
            }
            $newAuto = $autoInfoNal ?? $autoInfoCard ?? null;
            if ($newAuto !== null) {
                $orderweb->auto = $newAuto;
            }
            Log::debug('Set orderweb auto initially', ['auto' => $orderweb->auto]);

            $resolved = $this->resolveLegStatuses($nalState, $cardState, $nalOrder, $cardOrder);
            $action = $resolved['action'];
            $response = $resolved['response_leg'] === 'card' ? $cardOrderInput : $nalOrderInput;

            Log::info('Resolved status from uid_history', [
                'nalState' => $nalState,
                'cardState' => $cardState,
                'action' => $action,
                'close_reason' => $resolved['close_reason'],
            ]);

            $this->applyResolvedStatusToOrderweb($orderweb, $resolved, $action);


            $issetCheckOrderExists = (new FCMController)->checkOrderExists($orderweb->id);
            $issetCheckOrdersTaking = (new FCMController)->checkOrdersTaking($dispatching_order_uid);
            $issetCheckOrdersSector = (new FCMController)->checkOrdersSector($dispatching_order_uid);

            Log::info('[OrderSync] Проверка существования документа в Firestore', [
                'order_id' => $orderweb->id,
                'issetCheckOrderExists' => $issetCheckOrderExists,
                'issetCheckOrdersTaking' => $issetCheckOrdersTaking,
                'action' => $action,
                'dispatching_order_uid' => $dispatching_order_uid ?? null,
            ]);

//            if ($action == 'Авто найдено' && ($issetCheckOrderExists || $issetCheckOrdersTaking|| $issetCheckOrdersSector)) {
            if (in_array($action, ['Авто найдено', 'На месте', 'В пути'], true)) {

            Log::info('[OrderSync] Условие: Авто найдено и документ существует', [
                    'order_id' => $orderweb->id,
                    'auto' => $orderweb->auto,
                ]);

//                if ($orderweb->auto != null) {
                    $uid = $dispatching_order_uid;

                    Log::info('[OrderSync] Удаляем документ из Firestore', ['uid' => $uid]);
                    (new FCMController)->deleteDocumentFromFirestore($uid);


//                } else {
//                    Log::warning('[OrderSync] Авто найдено, но поле auto пустое', [
//                        'order_id' => $orderweb->id,
//                    ]);
//                }
            } else {
                if ($action == 'Заказ снят') {
                    Log::info('[OrderSync] 111 Удаляем документ из Firestore', ['uid' => $uid]);
                    (new FCMController)->deleteDocumentFromFirestore($uid);
                }
                Log::info('[OrderSync] Условие: Поиск авто и документа ещё нет. Создаём новый документ.', [
                    'order_id' => $orderweb->id,
                    'uid' => $dispatching_order_uid,
                    'issetCheckOrderExists' => $issetCheckOrderExists,
                    'issetCheckOrdersTaking' => $issetCheckOrdersTaking,
                    'issetCheckOrdersSector' => $issetCheckOrdersSector,
                ]);
                if ($action == 'Поиск авто' && !$issetCheckOrderExists && !$issetCheckOrdersTaking&& !$issetCheckOrdersSector) {
                    Log::info('[OrderSync] Условие: Поиск авто и документа ещё нет. Создаём новый документ.', [
                        'order_id' => $orderweb->id,
                        'uid' => $dispatching_order_uid
                    ]);

//                    (new FCMController)->writeDocumentToFirestore($dispatching_order_uid);
                    dispatch(
                        (new \App\Jobs\WriteDocumentToFirestore($dispatching_order_uid))
                            ->onQueue('high')
                    );

                } else {
                    Log::info('[OrderSync] Условие не выполнено', [
                        'order_id' => $orderweb->id,
                        'action' => $action,
                        'issetCheckOrderExists' => $issetCheckOrderExists,
                        'issetCheckOrdersTaking' => $issetCheckOrdersTaking
                    ]);
                }
            }


            Log::debug('Saving orderweb after action determination', ['orderweb' => $orderweb->toArray()]);
                    $orderweb->save();

                    $response = $this->addActionToResponseUid($response, $action, $dispatching_order_uid);
                    if ($resolved['close_reason'] >= 0) {
                        $response = $this->patchCloseReasonInResponse($response, $resolved['close_reason']);
                    }
                    Log::info('Added action to response', ['response' => $response, 'action' => $action]);

                    $response_arr = json_decode($response, true);
                    Log::debug('Decoded final response', ['response_arr' => $response_arr]);

                    if (isset($response_arr["order_car_info"]) && $response_arr["order_car_info"] != null) {
                        $orderweb->auto = $response_arr["order_car_info"];
                        Log::info('Updated orderweb auto from response', ['auto' => $orderweb->auto]);
                    }
                    self::syncOrderwebAfterStatusPush($orderweb, $action, $cardOrder, $nalOrder, $dispatching_order_uid);
                    Log::info('Synced orderweb after status push', [
                        'action' => $action,
                        'closeReason' => $orderweb->closeReason,
                        'cancel_timestamp' => $orderweb->cancel_timestamp,
                    ]);

                    Log::debug('Saving orderweb final', ['orderweb' => $orderweb->toArray()]);
                    $orderweb->save();

                    Log::info('Exiting function', ['response' => $response]);
                    return $response;



        } else {
            Log::warning('No uid_history found after timeout');
            return response()->json([
                'action' => "Поиск авто"
            ]);
        }

        Log::info('Exiting function without response');
        return response()->json([
            'action' => "Поиск авто"
        ]);
    }


    /**
     * @throws \Exception
     */
    public function getOrderStatusMessageResultPushOnPasInBackground($dispatching_order_uid)
    {

            // Попробуем найти запись
            $dispatching_order_uid = (new MemoryOrderChangeController)->show($dispatching_order_uid);

        Log::debug('Updated dispatching_order_uid from MemoryOrderChangeController', ['dispatching_order_uid' => $dispatching_order_uid]);

        $orderweb = Orderweb::where("dispatching_order_uid", $dispatching_order_uid)->first();
        Log::debug('Fetched orderweb', ['orderweb' => $orderweb ? $orderweb->toArray() : null]);

        [$uid_history, $nalOrderInput, $cardOrderInput, $dispatching_order_uid] = $this->resolveUidHistoryForStatusPush($dispatching_order_uid);

        if (!$uid_history || $cardOrderInput === null) {
            if ($orderweb) {
                $liveResponse = $this->buildSimpleCashlessLiveStatusPushResponse($orderweb, $dispatching_order_uid);
                if ($liveResponse !== null) {
                    return $liveResponse;
                }

                return $this->buildCachedStatusPushResponse($orderweb, $dispatching_order_uid);
            }

            return response()->json(['action' => OrderStatusMessageResolver::ACTION_SEARCH]);
        }

        if ($uid_history) {
            $messageAdmin = "getOrderStatusMessageResultPush: nal: $nalOrderInput, card: $cardOrderInput";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            $nalOrder = json_decode($nalOrderInput, true);
            $cardOrder = json_decode($cardOrderInput, true);

            $nalState = $nalOrder['execution_status'] ?? 'SearchesForCar';
            $cardState = $cardOrder['execution_status'] ?? 'SearchesForCar';

            $autoInfoNal =  $nalOrder['order_car_info']  ?? null;
            $autoInfoCard =  $cardOrder['order_car_info']  ?? null;

            $messageAdmin = "getOrderStatusMessageResultPush real: nalState: $nalState, cardState: $cardState";
            (new MessageSentController)->sentMessageAdminLog($messageAdmin);

            $orderweb = Orderweb::where("dispatching_order_uid", $dispatching_order_uid)->first();

//            if ($orderweb && isset($orderweb->comment) && isset($orderweb->email)) {
            if ($orderweb) {
                $newAuto = $autoInfoNal ?? $autoInfoCard ?? null;
                if ($newAuto !== null) {
                    $orderweb->auto = $newAuto;
                }
//                switch ($orderweb->comment) {
//                    case 'taxi_easy_ua_pas1':
//                        $app = "PAS1";
//                        break;
//                    case 'taxi_easy_ua_pas2':
//                        $app = "PAS2";
//                        break;
//                    default:
//                        $app = "PAS4";
//                }
//                $email = $orderweb->email;
                // Блок 1: Состояния "Поиск авто"
                if (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch']) &&
                    in_array($cardState, ['SearchesForCar', 'WaitingCarSearch'])) {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                    $response = $nalOrderInput; // НАЛ
                }
                elseif ($nalState === 'SearchesForCar' && $cardState === 'CostCalculation') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                    $response = $nalOrderInput; // НАЛ
                }
                elseif ($nalState === 'CostCalculation' && $cardState === 'SearchesForCar') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                    $response = $cardOrderInput; // БЕЗНАЛ
                }
                elseif ($nalState === 'Canceled' && $cardState === 'SearchesForCar') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                    $response = $cardOrderInput; // БЕЗНАЛ
                }
                elseif ($nalState === 'SearchesForCar' && $cardState === 'Canceled') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                    $response = $nalOrderInput; // НАЛ
                }
                elseif ($nalState === 'Canceled' && $cardState === 'WaitingCarSearch') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                    $response = $cardOrderInput; // БЕЗНАЛ
                }
                elseif ($nalState === 'WaitingCarSearch' && $cardState === 'Canceled') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                    $response = $nalOrderInput; // НАЛ
                }
                elseif ($nalState === 'CostCalculation' && in_array($cardState, ['SearchesForCar', 'WaitingCarSearch'])){
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                    $response = $cardOrderInput; // БЕЗНАЛ
                }
                elseif (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch']) && $cardState === 'CostCalculation') {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                    $response = $nalOrderInput; // НАЛ
                }

                // Блок 2: Состояния "Авто найдено"
                elseif ($nalState === 'SearchesForCar' && in_array($cardState, ['CarFound', 'Running'])) {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $cardOrderInput; // БЕЗНАЛ
                    (new UniversalAndroidFunctionController)->sendAutoOrderResponse($orderweb, "yes_mes");
                }
                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'SearchesForCar') {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $nalOrderInput; // НАЛ
                    (new UniversalAndroidFunctionController)->sendAutoOrderResponse($orderweb, "yes_mes");
                }
                elseif ($nalState === 'WaitingCarSearch' && in_array($cardState, ['CarFound', 'Running'])) {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $cardOrderInput; // БЕЗНАЛ
                    (new UniversalAndroidFunctionController)->sendAutoOrderResponse($orderweb, "yes_mes");
                }
                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'WaitingCarSearch') {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $nalOrderInput; // НАЛ
                    (new UniversalAndroidFunctionController)->sendAutoOrderResponse($orderweb, "yes_mes");
                }
                elseif ($nalState === 'CarFound' && in_array($cardState, ['CarFound', 'Running'])) {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $cardOrderInput; // БЕЗНАЛ
                    (new UniversalAndroidFunctionController)->sendAutoOrderResponse($orderweb, "yes_mes");
                }
                elseif ($nalState === 'Running' && $cardState === 'CarFound') {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $nalOrderInput; // НАЛ
                    (new UniversalAndroidFunctionController)->sendAutoOrderResponse($orderweb, "yes_mes");
                }
                elseif ($nalState === 'Running' && $cardState === 'Running') {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $cardOrderInput; // БЕЗНАЛ
                    (new UniversalAndroidFunctionController)->sendAutoOrderResponse($orderweb, "yes_mes");
                }
                elseif ($nalState === 'Canceled' && in_array($cardState, ['CarFound', 'Running'])) {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $cardOrderInput; // БЕЗНАЛ
                    (new UniversalAndroidFunctionController)->sendAutoOrderResponse($orderweb, "yes_mes");
                }
                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'Canceled') {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $nalOrderInput; // НАЛ
                    (new UniversalAndroidFunctionController)->sendAutoOrderResponse($orderweb, "yes_mes");
                }
                elseif ($nalState === 'CostCalculation' && in_array($cardState, ['CarFound', 'Running'])) {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $cardOrderInput; // БЕЗНАЛ
                    (new UniversalAndroidFunctionController)->sendAutoOrderResponse($orderweb, "yes_mes");
                }
                elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'CostCalculation') {
                    $action = 'Авто найдено';
                    $orderweb->closeReason = "-1";
                    $response = $nalOrderInput; // НАЛ
                    (new UniversalAndroidFunctionController)->sendAutoOrderResponse($orderweb, "yes_mes");
                }

                // Блок 3: Состояния "Заказ выполнен"
                elseif ($nalState === 'Executed' && in_array($cardState, ['SearchesForCar', 'WaitingCarSearch', 'CarFound', 'Running'])) {
                    $action = 'Заказ выполнен';
                    $orderweb->closeReason = "0";
                    $response = $nalOrderInput; // НАЛ
                }
                elseif (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch', 'CarFound', 'Running']) && $cardState === 'Executed') {
                    $action = 'Заказ выполнен';
                    $orderweb->closeReason = "0";
                    $response = $cardOrderInput; // БЕЗНАЛ
                }
                elseif ($nalState === 'Executed' && $cardState === 'CostCalculation') {
                    $action = 'Заказ выполнен';
                    $orderweb->closeReason = "0";
                    $response = $nalOrderInput; // НАЛ
                }
                elseif ($nalState === 'CostCalculation' && $cardState === 'Executed') {
                    $action = 'Заказ выполнен';
                    $orderweb->closeReason = "0";
                    $response = $cardOrderInput; // БЕЗНАЛ
                }
                // Блок 4: Состояния "Заказ снят" с проверкой close_reason
                elseif ($nalState === 'Canceled' && $cardState === 'CostCalculation') {
                    $closeReason = $nalOrder['close_reason'] ?? -1;
                    $action = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
                    $orderweb->closeReason = $closeReason;
                    if ($closeReason == "-1") {
                        $orderweb->auto = null;
                    }
                    $response = $nalOrderInput; // НАЛ

                }
                elseif ($nalState === 'CostCalculation' && $cardState === 'Canceled') {
                    $closeReason = $cardOrder['close_reason'] ?? -1;
                    $action = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
                    $orderweb->closeReason = $closeReason;
                    if ($closeReason == "-1") {
                        $orderweb->auto = null;
                    }
                    $response = $cardOrderInput; // БЕЗНАЛ

                }
                elseif ($nalState === 'CostCalculation' && $cardState === 'CostCalculation') {
                    $closeReasonNal = $nalOrder['close_reason'] ?? -1;
                    $closeReasonCard = $cardOrder['close_reason'] ?? -1;
                    if($closeReasonNal != -1 && $closeReasonCard != -1) {
                        $action = 'Заказ снят';
                        $orderweb->closeReason = "1";
                    } else {
                        $action = 'Поиск авто';
                        $orderweb->auto = null;
                        $orderweb->closeReason = "-1";
                    }

                    $response = $cardOrderInput; // БЕЗНАЛ
                }
                elseif ($nalState === 'Canceled' && $cardState === 'Canceled') {
                    $closeReasonNal = $nalOrder['close_reason'] ?? -1;
                    $closeReasonCard = $cardOrder['close_reason'] ?? -1;
                    if($closeReasonNal != -1 && $closeReasonCard != -1) {
                        $action = 'Заказ снят';
                        $orderweb->closeReason = "1";
                    } else {
                        $action = 'Поиск авто';
                        $orderweb->auto = null;
                        $orderweb->closeReason = "-1";
                    }
                    $response = $cardOrderInput; // БЕЗНАЛ
                } else {
                    $action = 'Поиск авто';
                    $orderweb->auto = null;
                    $orderweb->closeReason = "-1";
                    $response = $nalOrderInput;
                }
                $orderweb->save();

                $response = $this->addActionToResponseUid($response, $action, $dispatching_order_uid);

                $messageAdmin = "getOrderStatusMessageResultPush response: {$response}";
                (new MessageSentController)->sentMessageAdminLog($messageAdmin);

                $response_arr = json_decode($response, true);
                if (isset($response_arr["order_car_info"]) && $response_arr["order_car_info"] != null) {
                    $orderweb->auto = $response_arr["order_car_info"];
                    $orderweb->closeReason = -1;
                } else if (isset($response_arr["action"]) && $response_arr["action"] == "Заказ снят") {
                    $orderweb->closeReason = 1;
                } else {

                    $orderweb->closeReason = $response_arr["close_reason"] ?? -1; // Значение по умолчанию, если close_reason тоже отсутствует
                }

                $orderweb->save();

                $messageAdmin = "getOrderStatusMessageResultPush action: {$action}, nalState: $nalState, cardState: $cardState";
                (new MessageSentController)->sentMessageAdmin($messageAdmin);
//
        $messageAdmin = "getOrderStatusMessageResult response: dispatching_order_uid ". $response ;
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

//                (new PusherController)->sendDoubleStatus($response, $app, $email, "2222 getOrderStatusMessageResult ");
                return $response;
            }
        } else {
            Log::warning('No uid_history found after timeout');
            return response()->json([
                'action' => "Поиск авто"
            ]);
        }
    }

    private function patchCloseReasonInResponse($response, int $closeReason)
    {
        if ($closeReason < 0) {
            return $response;
        }
        $data = json_decode($response, true);
        if (!is_array($data)) {
            return $response;
        }
        $data['close_reason'] = $closeReason;

        return json_encode($data);
    }

    public function addActionToResponse($response, $action)
    {
        if (is_object($response)) {
            $data = $response->json();
        } else {
            // Если $response - это строка или массив
            $data = json_decode($response, true); // Преобразуем в массив
        }
        $data['action'] = $action;
        return json_encode($data);  // Возвращаем результат в виде JSON
    }

    public function addActionToResponseUid($response, $action, $dispatching_order_uid)
    {
        if (is_object($response)) {
            $data = $response->json();
        } else {
            // Если $response - это строка или массив
            $data = json_decode($response, true); // Преобразуем в массив
        }
        $data['action'] = $action;
        $data['uid'] = $dispatching_order_uid;
        return json_encode($data);  // Возвращаем результат в виде JSON
    }

    public function addCarInfoToResponseUid(
        $response,
        $order_car_info,
        $driver_phone,
        $time_to_start_point
    ) {
        if (is_object($response)) {
            $data = $response->json();
        } else {
            // Если $response - это строка или массив
            $data = json_decode($response, true); // Преобразуем в массив
        }
        $data['order_car_info'] = $order_car_info;
        $data['driver_phone'] = $driver_phone;
        $data['time_to_start_point'] = $time_to_start_point;

        return json_encode($data);  // Возвращаем результат в виде JSON
    }

    public function replaceFieldValueInResponse($response, $field, $newValue)
    {
        // Проверяем, что $response не null и содержит данные
        if (is_null($response)) {
            // Если $response null, возвращаем JSON с новым полем
            return json_encode([$field => $newValue]);
        }

        if (is_object($response)) {
            $data = $response->json();
        } else {
            // Если $response - строка, пытаемся декодировать в массив
            $data = is_string($response) ? json_decode($response, true) : $response;
        }

        // Проверяем, удалось ли получить массив
        if (!is_array($data)) {
            // Если $data не массив (например, некорректный JSON), возвращаем JSON с новым полем
            return json_encode([$field => $newValue]);
        }

        // Заменяем значение указанного поля
        $data[$field] = $newValue;

        return json_encode($data); // Возвращаем результат в виде JSON
    }
// Пример вызова теста с реальными данными
    public function runTestWithRealData()
    {
        $currentOrderExample = [
            'execution_status' => 'Running',
            'close_reason' => 0,
            'driver_phone' => '123-456-7890',
            'time_to_start_point' => '10 минут',
            'order_car_info' => 'Toyota Camry, белый, A123BC',
            'order_id' => 'ORD123'
        ];

        $nextOrderExample = [
            'execution_status' => 'Canceled',
            'close_reason' => 3,
            'driver_phone' => '098-765-4321',
            'time_to_start_point' => '15 минут',
            'order_car_info' => 'Honda Accord, черный, X789YZ',
            'order_id' => 'ORD456'
        ];

        $this->testOrderStatusMessageResultWithRealData($currentOrderExample, $nextOrderExample);
    }

    // Добавим метод для обработки HTTP-запросов
    public function showStatus(string $currentState, string $nextState, ?int $closeReason = -1)
    {
        // Валидация напрямую через встроенные проверки PHP
        if (empty($currentState) || !is_string($currentState)) {
            throw new \InvalidArgumentException('current_state must be a non-empty string');
        }

        if (empty($nextState) || !is_string($nextState)) {
            throw new \InvalidArgumentException('next_state must be a non-empty string');
        }

        $message = $this->getOrderStatusMessage($currentState, $nextState, $closeReason);

        return response()->json([

            'message' => $message,
            'дубль нал' => $currentState,
            'безнал' => $nextState,
            'close_reason' => $closeReason
        ]);
    }

    /**
     * Хотя бы одна нога (карта или нал) ещё активна на диспетчере.
     */
    /**
     * Явная отмена вилки (кнопка в приложении, Vod, webordersCancelDouble).
     */
    public static function isExplicitForkCancelRequested(?Uid_history $uid_history): bool
    {
        if ($uid_history === null) {
            return false;
        }

        return $uid_history->cancel === '1'
            || $uid_history->cancel === 1
            || $uid_history->cancel === true;
    }

    /**
     * Fork cancel is confirmed on dispatch when every leg is archived or close_reason=1.
     *
     * @param array<string, mixed>|null $cardOrder
     * @param array<string, mixed>|null $nalOrder
     */
    public static function areForkLegSnapshotsCancelSettled(?array $cardOrder, ?array $nalOrder): bool
    {
        $cancelService = new DispatchOrderCancelService();
        $legs = array_filter([$cardOrder, $nalOrder], static function ($leg) {
            return is_array($leg) && $leg !== [];
        });

        if ($legs === []) {
            return false;
        }

        foreach ($legs as $leg) {
            if (!$cancelService->isDispatchCancelSettled($leg)) {
                return false;
            }
        }

        return true;
    }

    public static function isForkOrderStillLive(?Uid_history $uid_history): bool
    {
        if ($uid_history === null) {
            return false;
        }
        $nalOrder = json_decode($uid_history->double_status, true);
        $cardOrder = json_decode($uid_history->bonus_status, true);

        return self::hasActiveDispatchLeg($cardOrder, $nalOrder);
    }

    /**
     * Push/Telegram «клиент отменил» — только при реальной отмене всего заказа, не при смене ноги вилки.
     */
    public static function shouldNotifyClientOrderCanceled(Orderweb $orderweb): bool
    {
        if (empty($orderweb->email)) {
            return false;
        }
        if (in_array((string) $orderweb->closeReason, ['101', '102', '103', '104'], true)) {
            return false;
        }

        $uid_history = Uid_history::where('uid_bonusOrderHold', $orderweb->dispatching_order_uid)->first();
        if (self::isForkOrderStillLive($uid_history)) {
            Log::info('Skip client cancel notify: fork order still live', [
                'uid' => $orderweb->dispatching_order_uid,
            ]);

            return false;
        }

        if (in_array((string) $orderweb->closeReason, ['-1', '0', '100'], true)) {
            return false;
        }

        return true;
    }

    public static function notifyForkOrderCanceledPush(Orderweb $orderweb, string $uid): void
    {
        if (!self::shouldNotifyClientOrderCanceled($orderweb)) {
            return;
        }

        try {
            $app = (new UniversalAndroidFunctionController)->appFinder($orderweb->comment);
            (new PusherController)->sentCanceledStatus($app, $orderweb->email, $uid);
            (new CentrifugoController)->sentCanceledStatus($app, $orderweb->email, $uid);
        } catch (\Throwable $e) {
            Log::error('notifyForkOrderCanceledPush failed: ' . $e->getMessage(), [
                'uid' => $uid,
                'order_id' => $orderweb->id,
            ]);
        }
    }

    public static function hasActiveDispatchLeg(?array $cardOrder, ?array $nalOrder): bool
    {
        $liveStatuses = [
            'SearchesForCar',
            'WaitingCarSearch',
            'CarFound',
            'Running',
            'WaitingAtAddress',
            'AtAddress',
            'InRoute',
        ];

        $cardStatus = (string) ($cardOrder['execution_status'] ?? '');
        $nalStatus = (string) ($nalOrder['execution_status'] ?? '');

        if (in_array($cardStatus, $liveStatuses, true) || in_array($nalStatus, $liveStatuses, true)) {
            return true;
        }

        if ($cardOrder !== null && $cardOrder !== []
            && !self::isDispatchOrderCanceled($cardOrder)
            && $cardStatus !== 'Canceled' && $cardStatus !== 'Executed' && $cardStatus !== 'CostCalculation') {
            return true;
        }

        if ($nalOrder !== null && $nalOrder !== []
            && !self::isDispatchOrderCanceled($nalOrder)
            && $nalStatus !== 'Canceled' && $nalStatus !== 'Executed' && $nalStatus !== 'CostCalculation') {
            return true;
        }

        return false;
    }

    /**
     * После опроса: синхронизировать orderweb с актуальным action из vod.
     */
    public static function syncOrderwebAfterStatusPush(
        Orderweb $orderweb,
        string $action,
        ?array $cardOrder = null,
        ?array $nalOrder = null,
        ?string $uidForCancelPush = null
    ): void {
        if ($action === OrderStatusMessageResolver::ACTION_CANCELED
            && !self::hasActiveDispatchLeg($cardOrder, $nalOrder)) {
            self::applyCanceledOrderweb($orderweb);
            if ($uidForCancelPush !== null) {
                self::notifyForkOrderCanceledPush($orderweb, $uidForCancelPush);
            }

            return;
        }

        if ($action === OrderStatusMessageResolver::ACTION_SEARCH) {
            $orderweb->closeReason = '-1';
            $orderweb->auto = null;
            $orderweb->cancel_timestamp = null;

            return;
        }

        $activeTripActions = [
            OrderStatusMessageResolver::ACTION_CAR_FOUND,
            OrderStatusMessageResolver::ACTION_AT_ADDRESS,
            OrderStatusMessageResolver::ACTION_IN_ROUTE,
        ];
        if (in_array($action, $activeTripActions, true) && self::hasActiveDispatchLeg($cardOrder, $nalOrder)) {
            $orderweb->cancel_timestamp = null;
        }
    }

    /**
     * Диспетчер иногда отдаёт execution_status=Canceled при close_reason=-1 (заказ ещё в поиске).
     */
    public static function normalizeFalseCanceledDispatchStatus(?array $snapshot): ?array
    {
        if ($snapshot === null || $snapshot === []) {
            return $snapshot;
        }
        if (self::isDispatchOrderCanceled($snapshot)) {
            return $snapshot;
        }
        $status = (string) ($snapshot['execution_status'] ?? '');
        if (strcasecmp($status, 'Canceled') === 0 || strcasecmp($status, 'Cancelled') === 0) {
            $snapshot['execution_status'] = 'SearchesForCar';
        }

        return $snapshot;
    }

    /**
     * Bonus/card leg in uid_history: canceled in dispatch (execution_status or close_reason).
     * Canceled with close_reason -1 is a fork transition, not a real cancel (Oleg / Excel matrix).
     */
    public static function isDispatchOrderCanceled(?array $order): bool
    {
        if ($order === null || $order === []) {
            return false;
        }
        $closeReason = $order['close_reason'] ?? -1;
        if ($closeReason === -1 || $closeReason === '-1' || $closeReason === 0 || $closeReason === '0') {
            return false;
        }
        if (($order['execution_status'] ?? '') === 'Canceled') {
            return true;
        }

        return true;
    }

    /**
     * Нога вилки закрыта на диспетчере — можно создавать новый UID той же ноги (нал/безнал).
     * Canceled учитывается только при реальном close_reason (не -1).
     */
    public static function isLegClosedForForkRecreate(?array $order, ?string $displayStatus): bool
    {
        if ($displayStatus === null || $displayStatus === '') {
            return false;
        }

        if (in_array($displayStatus, ['CostCalculation', 'Executed'], true)) {
            return true;
        }

        if ($displayStatus === 'Canceled') {
            return self::isDispatchOrderCanceled($order);
        }

        return false;
    }

    public static function applyCanceledOrderweb(Orderweb $orderweb): void
    {
        $orderweb->closeReason = '1';
        $orderweb->auto = null;
        if ($orderweb->cancel_timestamp === null) {
            $orderweb->cancel_timestamp = now();
        }
    }

    /**
     * Google Pay: order is created after client hold; dispatch may briefly report Canceled
     * with close_reason -1 while payment callback is still syncing.
     *
     * @param array<string, mixed> $snapshot
     */
    public static function shouldDeferDispatchCancelForGooglePayHold(
        Orderweb $orderweb,
        array $snapshot,
        ?string $dispatchUid = null
    ): bool {
        if ($dispatchUid !== null && DispatchOrderCancelService::hasActiveCampaign($dispatchUid)) {
            return false;
        }

        $dispatchCloseReason = (string) ($snapshot['close_reason'] ?? '');
        if ($dispatchCloseReason === '-1') {
            return true;
        }

        if (($orderweb->pay_system ?? '') !== 'google_pay_payment') {
            return false;
        }

        $paidStatuses = ['WaitingAuthComplete', 'Approved'];
        if (in_array((string) ($orderweb->wfp_status_pay ?? ''), $paidStatuses, true)) {
            return true;
        }

        $orderReference = (string) ($orderweb->wfp_order_id ?? '');
        if ($orderReference !== '') {
            $invoice = \App\Models\WfpInvoice::where('orderReference', $orderReference)->first();
            if ($invoice !== null
                && in_array((string) ($invoice->transactionStatus ?? ''), $paidStatuses, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dispatch reports Canceled — treat as real cancel when client cancel campaign is active.
     *
     * @param array<string, mixed> $snapshot
     */
    public static function isDispatchCanceledForClientCancel(array $snapshot, string $uid): bool
    {
        if (self::isDispatchOrderCanceled($snapshot)) {
            return true;
        }

        if (!DispatchOrderCancelService::hasActiveCampaign($uid)) {
            return false;
        }

        return (new DispatchOrderCancelService())->isDispatchCancelSettled($snapshot, $uid);
    }

    private static function shouldRefreshForkLegSnapshotsForStatusPush(
        Uid_history $uid_history,
        Orderweb $orderweb
    ): bool {
        if (self::isExplicitForkCancelRequested($uid_history)) {
            return true;
        }

        $nalOrder = json_decode($uid_history->double_status, true) ?: [];
        $cardOrder = json_decode($uid_history->bonus_status, true) ?: [];
        $nalState = (string) ($nalOrder['execution_status'] ?? '');
        $cardState = (string) ($cardOrder['execution_status'] ?? '');

        if (self::hasActiveDispatchLeg($cardOrder, $nalOrder)) {
            return false;
        }

        return in_array((string) ($orderweb->closeReason ?? ''), ['-1', '0', '100'], true)
            || self::isDispatchOrderCanceled($cardOrder)
            || self::isDispatchOrderCanceled($nalOrder)
            || $nalState === 'Canceled'
            || $cardState === 'Canceled';
    }

    private function refreshForkLegSnapshotsForStatusPush(Uid_history $uid_history, Orderweb $orderweb): void
    {
        $bonusUid = (string) ($uid_history->uid_bonusOrder ?: $orderweb->dispatching_order_uid);
        $doubleUid = (string) ($uid_history->uid_doubleOrder ?? '');
        if ($bonusUid === '') {
            return;
        }

        $bonusSnapshot = SimpleCashlessDispatchStatusSync::fetchDispatchSnapshot($orderweb, $bonusUid);
        if ($bonusSnapshot !== null) {
            $uid_history->bonus_status = json_encode($bonusSnapshot, JSON_UNESCAPED_UNICODE);
        }

        $doubleSnapshot = null;
        if ($doubleUid !== '') {
            $doubleSnapshot = SimpleCashlessDispatchStatusSync::fetchDispatchSnapshot($orderweb, $doubleUid);
            if ($doubleSnapshot !== null) {
                $uid_history->double_status = json_encode($doubleSnapshot, JSON_UNESCAPED_UNICODE);
            }
        }

        $uid_history->save();

        Log::info('refreshForkLegSnapshotsForStatusPush', [
            'bonus_uid' => $bonusUid,
            'double_uid' => $doubleUid,
            'bonus_state' => $bonusSnapshot['execution_status'] ?? null,
            'double_state' => $doubleSnapshot['execution_status'] ?? null,
        ]);
    }
}

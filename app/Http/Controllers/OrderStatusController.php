<?php

namespace App\Http\Controllers;

use App\Models\ExecutionStatus;
use App\Models\Orderweb;
use App\Models\Uid_history;
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


        $messageAdmin = "getOrderStatusMessageResult real: nalState: $nalState, cardState: $cardState";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        $action = 'Поиск авто';
        $response = $nalOrderInput;

        // Блок 1: Состояния "Поиск авто"
        if (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch']) &&
            in_array($cardState, ['SearchesForCar', 'WaitingCarSearch'])) {
            $action = 'Поиск авто';
            $response = $nalOrderInput; // НАЛ
        }
        elseif ($nalState === 'SearchesForCar' && $cardState === 'CostCalculation') {
            $action = 'Поиск авто';
            $response = $nalOrderInput; // НАЛ
        }
        elseif ($nalState === 'CostCalculation' && $cardState === 'SearchesForCar') {
            $action = 'Поиск авто';
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        elseif ($nalState === 'Canceled' && $cardState === 'SearchesForCar') {
            $action = 'Поиск авто';
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        elseif ($nalState === 'SearchesForCar' && $cardState === 'Canceled') {
            $action = 'Поиск авто';
            $response = $nalOrderInput; // НАЛ
        }
        elseif ($nalState === 'Canceled' && $cardState === 'WaitingCarSearch') {
            $action = 'Поиск авто';
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        elseif ($nalState === 'WaitingCarSearch' && $cardState === 'Canceled') {
            $action = 'Поиск авто';
            $response = $nalOrderInput; // НАЛ
        }
        elseif ($nalState === 'CostCalculation' && in_array($cardState, ['SearchesForCar', 'WaitingCarSearch'])){
            $action = 'Поиск авто';
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        elseif (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch']) && $cardState === 'CostCalculation') {
            $action = 'Поиск авто';
            $response = $nalOrderInput; // НАЛ
        }

        // Блок 2: Состояния "Авто найдено"
        elseif ($nalState === 'SearchesForCar' && in_array($cardState, ['CarFound', 'Running'])) {
            $action = 'Авто найдено';
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'SearchesForCar') {
            $action = 'Авто найдено';
            $response = $nalOrderInput; // НАЛ
        }
        elseif ($nalState === 'WaitingCarSearch' && in_array($cardState, ['CarFound', 'Running'])) {
            $action = 'Авто найдено';
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'WaitingCarSearch') {
            $action = 'Авто найдено';
            $response = $nalOrderInput; // НАЛ
        }
        elseif ($nalState === 'CarFound' && in_array($cardState, ['CarFound', 'Running'])) {
            $action = 'Авто найдено';
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        elseif ($nalState === 'Running' && $cardState === 'CarFound') {
            $action = 'Авто найдено';
            $response = $nalOrderInput; // НАЛ
        }
        elseif ($nalState === 'Running' && $cardState === 'Running') {
            $action = 'Авто найдено';
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        elseif ($nalState === 'Canceled' && in_array($cardState, ['CarFound', 'Running'])) {
            $action = 'Авто найдено';
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'Canceled') {
            $action = 'Авто найдено';
            $response = $nalOrderInput; // НАЛ
        }
        elseif ($nalState === 'CostCalculation' && in_array($cardState, ['CarFound', 'Running'])) {
            $action = 'Авто найдено';
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        elseif (in_array($nalState, ['CarFound', 'Running']) && $cardState === 'CostCalculation') {
            $action = 'Авто найдено';
            $response = $nalOrderInput; // НАЛ
        }

        // Блок 3: Состояния "Заказ выполнен"
        elseif ($nalState === 'Executed' && in_array($cardState, ['SearchesForCar', 'WaitingCarSearch', 'CarFound', 'Running'])) {
            $action = 'Заказ выполнен';
            $response = $nalOrderInput; // НАЛ
        }
        elseif (in_array($nalState, ['SearchesForCar', 'WaitingCarSearch', 'CarFound', 'Running']) && $cardState === 'Executed') {
            $action = 'Заказ выполнен';
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        elseif ($nalState === 'Executed' && $cardState === 'CostCalculation') {
            $action = 'Заказ выполнен';
            $response = $nalOrderInput; // НАЛ
        }
        elseif ($nalState === 'CostCalculation' && $cardState === 'Executed') {
            $action = 'Заказ выполнен';
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        // Блок 4: Состояния "Заказ снят" с проверкой close_reason
        elseif ($nalState === 'Canceled' && $cardState === 'CostCalculation') {
            $closeReason = $nalOrder['close_reason'] ?? -1;
            $action = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
            $response = $nalOrderInput; // НАЛ
        }
        elseif ($nalState === 'CostCalculation' && $cardState === 'Canceled') {
            $closeReason = $cardOrder['close_reason'] ?? -1;
            $action = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        elseif ($nalState === 'CostCalculation' && $cardState === 'CostCalculation') {
            $closeReasonNal = $nalOrder['close_reason'] ?? -1;
            $closeReasonCard = $cardOrder['close_reason'] ?? -1;
            if($closeReasonNal != -1 && $closeReasonCard != -1) {
                $action = 'Заказ снят';
            } else {
                $action = 'Поиск авто';
            }
            $response = $cardOrderInput; // БЕЗНАЛ
        }
        elseif ($nalState === 'Canceled' && $cardState === 'Canceled') {
            $closeReasonNal = $nalOrder['close_reason'] ?? -1;
            $closeReasonCard = $cardOrder['close_reason'] ?? -1;
            if($closeReasonNal != -1 && $closeReasonCard != -1) {
                $action = 'Заказ снят';
            } else {
                $action = 'Поиск авто';
            }
            $response = $cardOrderInput; // БЕЗНАЛ
        } else {
            $action = 'Поиск авто';
            $response = $nalOrderInput;
        }
        $response = $this->addActionToResponse($response, $action);

        $messageAdmin = "getOrderStatusMessageResult action: {$action}, nalState: $nalState, cardState: $cardState";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
//
//        $messageAdmin = "getOrderStatusMessageResult response: dispatching_order_uid ". $response ;
//        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        return $response; // Возвращаем результат с action в JSON
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
     * @throws \Exception
     */
    public function getOrderStatusMessageResultPush($dispatching_order_uid)
    {
        Log::info('Entering getOrderStatusMessageResultPush', ['dispatching_order_uid' => $dispatching_order_uid]);

        // Попробуем найти запись
        $dispatching_order_uid = (new MemoryOrderChangeController)->show($dispatching_order_uid);
        Log::debug('Updated dispatching_order_uid from MemoryOrderChangeController', ['dispatching_order_uid' => $dispatching_order_uid]);

        $orderweb = Orderweb::where("dispatching_order_uid", $dispatching_order_uid)->first();
        Log::debug('Fetched orderweb', ['orderweb' => $orderweb ? $orderweb->toArray() : null]);

        if ($orderweb) {
            if (in_array($orderweb->closeReason, ['101', '102', '103', '104'])) {

                $responseArr = [
                    'close_reason' => $orderweb->closeReason
                ];

                $responseArr['uid'] = $dispatching_order_uid;


                $storedData = $orderweb->auto ?? null;
                Log::debug('Set orderweb auto initially', ['auto' => $storedData]);
                if ($storedData != null) {
                    $dataDriver = json_decode($storedData, true);
//            $name = $dataDriver["name"];
                    $color = $dataDriver["color"];
                    $brand = $dataDriver["brand"];
                    $model = $dataDriver["model"];
                    $number = $dataDriver["number"];
                    $phoneNumber = $dataDriver["phoneNumber"];

                    $auto = "$number, цвет $color  $brand $model. ";


                    // Обновление полей
                    $responseArr['order_car_info'] = $auto;
                    $responseArr['driver_phone'] = $phoneNumber;
                    $responseArr['time_to_start_point'] = $orderweb->time_to_start_point;

                }

                $action = 'Поиск авто';
                switch ($orderweb->closeReason) {
                    // Block 2: Состояния "Авто найдено"
                    case '101':
                        $action = 'Авто найдено';
                        Log::info('Switch: Авто найдено', ['action' => $action]);
                        break;
                    case '102':
                        $action = 'На месте';
                        Log::info('Switch: На месте', ['action' => $action]);
                        break;

                    case '103':
                        $action = 'В пути';
                        Log::info('Switch: В пути', ['action' => $action]);
                        break;

                    case '104':
                        $action = "Заказ выполнен";
                        Log::info('Switch: Заказ выполнен', ['action' => $action]);
                        break;
                }

                Log::debug('Saving orderweb after action determination', ['orderweb' => $orderweb->toArray()]);
                $responseArr['action'] = $action;

                Log::info('Exiting function',  $responseArr);
                return response()->json( $responseArr);
            }
        } else {
            $action = 'Поиск авто';
            $responseArr['action'] = $action;
            response()->json( $responseArr);
        }

        $uid_history = Uid_history::where("uid_bonusOrderHold", $dispatching_order_uid)->first();
        Log::debug('Searched Uid_history by uid_bonusOrderHold', ['found' => !is_null($uid_history)]);

        if ($uid_history) {
            // Если запись найдена, выходим из цикла
            $nalOrderInput = $uid_history->double_status;
            $cardOrderInput = $uid_history->bonus_status;
            Log::info('Found uid_history by uid_bonusOrderHold', [
                'nalOrderInput' => $nalOrderInput,
                'cardOrderInput' => $cardOrderInput
            ]);
        } else {
            $uid_history = Uid_history::where("uid_bonusOrder", $dispatching_order_uid)->first();
            Log::debug('Searched Uid_history by uid_bonusOrder', ['found' => !is_null($uid_history)]);

            if ($uid_history) {
                // Если запись найдена, выходим из цикла
                $nalOrderInput = $uid_history->double_status;
                $cardOrderInput = $uid_history->bonus_status;
                $dispatching_order_uid = $uid_history->uid_bonusOrder;
                Log::info('Found uid_history by uid_bonusOrder', [
                    'nalOrderInput' => $nalOrderInput,
                    'cardOrderInput' => $cardOrderInput,
                    'updated_dispatching_order_uid' => $dispatching_order_uid
                ]);
            } else {
                $uid_history = Uid_history::where("uid_doubleOrder", $dispatching_order_uid)->first();
                Log::debug('Searched Uid_history by uid_doubleOrder', ['found' => !is_null($uid_history)]);


                if ($uid_history) {
                    // Если запись найдена, выходим из цикла
                    $nalOrderInput = $uid_history->double_status;
                    $cardOrderInput = $uid_history->bonus_status;
                    $dispatching_order_uid = $uid_history->uid_bonusOrder;
                    Log::info('Found uid_history by uid_doubleOrder', [
                        'nalOrderInput' => $nalOrderInput,
                        'cardOrderInput' => $cardOrderInput,
                        'updated_dispatching_order_uid' => $dispatching_order_uid
                    ]);
                }
            }
        }

        Log::debug('Exited loop', ['found_uid_history' => !is_null($uid_history ?? null)]);

        if ($uid_history) {
            Log::info('Processing uid_history', ['nalOrderInput' => $nalOrderInput, 'cardOrderInput' => $cardOrderInput]);



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

                    if ($uid_history->cancel == "1") {
                        $response = $cardOrderInput; // БЕЗНАЛ
                        $action = 'Заказ снят';
                        $response = $this->addActionToResponseUid($response, $action, $dispatching_order_uid);
                        Log::info('Order canceled', [
                            'action' => $action,
                            'response' => $response,
                            'dispatching_order_uid' => $dispatching_order_uid
                        ]);
                        Log::info('Exiting function due to cancel', ['response' => $response]);
                        return $response;
                    }
                    $orderweb->auto = $autoInfoNal ?? $autoInfoCard ?? null;
                    Log::debug('Set orderweb auto initially', ['auto' => $orderweb->auto]);

                    // Combine nalState and cardState as a key for switch
                    $stateKey = $nalState . '|' . $cardState;
                    Log::debug('Generated state key for switch', ['stateKey' => $stateKey]);

                    $orderweb->closeReason = "-1";
                    $orderweb->auto = null;

                    switch ($stateKey) {
                        // Block 1: Состояния "Поиск авто"
                        case 'SearchesForCar|SearchesForCar':
                        case 'SearchesForCar|WaitingCarSearch':
                        case 'WaitingCarSearch|SearchesForCar':
                        case 'WaitingCarSearch|WaitingCarSearch':
                            $action = 'Поиск авто';
                            $orderweb->auto = null;
                            $orderweb->closeReason = "-1";
                            $response = $nalOrderInput; // НАЛ
                            Log::info('Switch: Поиск авто (both searching/waiting)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'SearchesForCar|CostCalculation':
                            $action = 'Поиск авто';
                            $orderweb->auto = null;
                            $orderweb->closeReason = "-1";
                            $response = $nalOrderInput; // НАЛ
                            Log::info('Switch: Поиск авто (nal searching, card cost calc)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'CostCalculation|SearchesForCar':
                            $action = 'Поиск авто';
                            $orderweb->auto = null;
                            $orderweb->closeReason = "-1";
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Поиск авто (nal cost calc, card searching)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'Canceled|SearchesForCar':
                            $action = 'Поиск авто';
                            $orderweb->auto = null;
                            $orderweb->closeReason = "-1";
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Поиск авто (nal canceled, card searching)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'SearchesForCar|Canceled':
                            $action = 'Поиск авто';
                            $orderweb->auto = null;
                            $orderweb->closeReason = "-1";
                            $response = $nalOrderInput; // НАЛ
                            Log::info('Switch: Поиск авто (nal searching, card canceled)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'Canceled|WaitingCarSearch':
                            $action = 'Поиск авто';
                            $orderweb->auto = null;
                            $orderweb->closeReason = "-1";
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Поиск авто (nal canceled, card waiting)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'WaitingCarSearch|Canceled':
                            $action = 'Поиск авто';
                            $orderweb->auto = null;
                            $orderweb->closeReason = "-1";
                            $response = $nalOrderInput; // НАЛ
                            Log::info('Switch: Поиск авто (nal waiting, card canceled)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'CostCalculation|WaitingCarSearch':
                        case 'CostCalculation|SearchesForCar':
                            $action = 'Поиск авто';
                            $orderweb->auto = null;
                            $orderweb->closeReason = "-1";
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Поиск авто (nal cost calc, card searching/waiting)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'SearchesForCar|CostCalculation':
                        case 'WaitingCarSearch|CostCalculation':
                            $action = 'Поиск авто';
                            $orderweb->auto = null;
                            $orderweb->closeReason = "-1";
                            $response = $nalOrderInput; // НАЛ
                            Log::info('Switch: Поиск авто (nal searching/waiting, card cost calc)', ['action' => $action, 'response' => $response]);
                            break;

                        // Block 2: Состояния "Авто найдено"
                        case 'SearchesForCar|CarFound':
                        case 'SearchesForCar|Running':
                            $action = 'Авто найдено';
                            $orderweb->closeReason = "-1";
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Авто найдено (nal searching, card found/running)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'CarFound|SearchesForCar':
                        case 'Running|SearchesForCar':
                            $action = 'Авто найдено';
                            $orderweb->closeReason = "-1";
                            $response = $nalOrderInput; // НАЛ
                            Log::info('Switch: Авто найдено (nal found/running, card searching)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'WaitingCarSearch|CarFound':
                        case 'WaitingCarSearch|Running':
                            $action = 'Авто найдено';
                            $orderweb->closeReason = "-1";
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Авто найдено (nal waiting, card found/running)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'CarFound|WaitingCarSearch':
                        case 'Running|WaitingCarSearch':
                            $action = 'Авто найдено';
                            $orderweb->closeReason = "-1";
                            $response = $nalOrderInput; // НАЛ
                            Log::info('Switch: Авто найдено (nal found/running, card waiting)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'CarFound|CarFound':
                        case 'CarFound|Running':
                            $action = 'Авто найдено';
                            $orderweb->closeReason = "-1";
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Авто найдено (nal found, card found/running)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'Running|CarFound':
                            $action = 'Авто найдено';
                            $orderweb->closeReason = "-1";
                            $response = $nalOrderInput; // НАЛ
                            Log::info('Switch: Авто найдено (nal running, card found)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'Running|Running':
                            $action = 'Авто найдено';
                            $orderweb->closeReason = "-1";
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Авто найдено (both running)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'Canceled|CarFound':
                        case 'Canceled|Running':
                            $action = 'Авто найдено';
                            $orderweb->closeReason = "-1";
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Авто найдено (nal canceled, card found/running)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'CarFound|Canceled':
                        case 'Running|Canceled':
                            $action = 'Авто найдено';
                            $orderweb->closeReason = "-1";
                            $response = $nalOrderInput; // НАЛ
                            Log::info('Switch: Авто найдено (nal found/running, card canceled)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'CostCalculation|CarFound':
                        case 'CostCalculation|Running':
                            $action = 'Авто найдено';
                            $orderweb->closeReason = "-1";
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Авто найдено (nal cost calc, card found/running)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'CarFound|CostCalculation':
                        case 'Running|CostCalculation':
                            $action = 'Авто найдено';
                            $orderweb->closeReason = "-1";
                            $response = $nalOrderInput; // НАЛ
                            Log::info('Switch: Авто найдено (nal found/running, card cost calc)', ['action' => $action, 'response' => $response]);
                            break;

                        // Block 3: Состояния "Заказ выполнен"
                        case 'Executed|SearchesForCar':
                        case 'Executed|WaitingCarSearch':
                        case 'Executed|CarFound':
                        case 'Executed|Running':
                            $action = 'Заказ выполнен';
                            $orderweb->closeReason = "0";
                            $response = $nalOrderInput; // НАЛ
                            Log::info('Switch: Заказ выполнен (nal executed, card various)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'SearchesForCar|Executed':
                        case 'WaitingCarSearch|Executed':
                        case 'CarFound|Executed':
                        case 'Running|Executed':
                            $action = 'Заказ выполнен';
                            $orderweb->closeReason = "0";
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Заказ выполнен (nal various, card executed)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'Executed|CostCalculation':
                            $action = 'Заказ выполнен';
                            $orderweb->closeReason = "0";
                            $response = $nalOrderInput; // НАЛ
                            Log::info('Switch: Заказ выполнен (nal executed, card cost calc)', ['action' => $action, 'response' => $response]);
                            break;

                        case 'CostCalculation|Executed':
                            $action = 'Заказ выполнен';
                            $orderweb->closeReason = "0";
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Заказ выполнен (nal cost calc, card executed)', ['action' => $action, 'response' => $response]);
                            break;

                        // Block 4: Состояния "Заказ снят" с проверкой close_reason
                        case 'Canceled|CostCalculation':
                            $closeReason = $nalOrder['close_reason'] ?? -1;
                            $action = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
                            $orderweb->closeReason = $closeReason;
                            if ($closeReason == "-1") {
                                $orderweb->auto = null;
                            }
                            $response = $nalOrderInput; // НАЛ
                            Log::info('Switch: Заказ снят/Поиск авто (nal canceled, card cost calc)', [
                                'action' => $action,
                                'closeReason' => $closeReason,
                                'response' => $response
                            ]);
                            break;

                        case 'CostCalculation|Canceled':
                            $closeReason = $cardOrder['close_reason'] ?? -1;
                            $action = $closeReason != -1 ? 'Заказ снят' : 'Поиск авто';
                            $orderweb->closeReason = $closeReason;
                            if ($closeReason == "-1") {
                                $orderweb->auto = null;
                            }
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Заказ снят/Поиск авто (nal cost calc, card canceled)', [
                                'action' => $action,
                                'closeReason' => $closeReason,
                                'response' => $response
                            ]);
                            break;

                        case 'CostCalculation|CostCalculation':
                            $closeReasonNal = $nalOrder['close_reason'] ?? -1;
                            $closeReasonCard = $cardOrder['close_reason'] ?? -1;
                            if ($closeReasonNal != -1 && $closeReasonCard != -1) {
                                $action = 'Заказ снят';
                                $orderweb->closeReason = "1";
                            } else {
                                $action = 'Поиск авто';
                                $orderweb->auto = null;
                                $orderweb->closeReason = "-1";
                            }
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Заказ снят/Поиск авто (both cost calc)', [
                                'action' => $action,
                                'closeReasonNal' => $closeReasonNal,
                                'closeReasonCard' => $closeReasonCard,
                                'response' => $response
                            ]);
                            break;

                        case 'Canceled|Canceled':
                            $closeReasonNal = $nalOrder['close_reason'] ?? -1;
                            $closeReasonCard = $cardOrder['close_reason'] ?? -1;
                            if ($closeReasonNal != -1 && $closeReasonCard != -1) {
                                $action = 'Заказ снят';
                                $orderweb->closeReason = "1";
                            } else {
                                $action = 'Поиск авто';
                                $orderweb->auto = null;
                                $orderweb->closeReason = "-1";
                            }
                            $response = $cardOrderInput; // БЕЗНАЛ
                            Log::info('Switch: Заказ снят/Поиск авто (both canceled)', [
                                'action' => $action,
                                'closeReasonNal' => $closeReasonNal,
                                'closeReasonCard' => $closeReasonCard,
                                'response' => $response
                            ]);
                            break;

                        default:
                            $action = 'Поиск авто';
                            $orderweb->auto = null;
                            $orderweb->closeReason = "-1";
                            $response = $nalOrderInput;
                            Log::info('Switch: Default - Поиск авто', ['action' => $action, 'response' => $response]);
                            break;
                    }

                    if($action == 'Авто найдено') {
                        $uid = $dispatching_order_uid;
                        (new FCMController)->deleteDocumentFromFirestore($uid);
                        (new FCMController)->deleteDocumentFromFirestoreOrdersTakingCancel($uid);
                        (new FCMController)->deleteDocumentFromSectorFirestore($uid);
                        (new FCMController)->writeDocumentToHistoryFirestore($uid, "cancelled");

                    }


                    Log::debug('Saving orderweb after action determination', ['orderweb' => $orderweb->toArray()]);
                    $orderweb->save();

                    $response = $this->addActionToResponseUid($response, $action, $dispatching_order_uid);
                    Log::info('Added action to response', ['response' => $response, 'action' => $action]);

                    $response_arr = json_decode($response, true);
                    Log::debug('Decoded final response', ['response_arr' => $response_arr]);

                    if (isset($response_arr["order_car_info"]) && $response_arr["order_car_info"] != null) {
                        $orderweb->auto = $response_arr["order_car_info"];
                        $orderweb->closeReason = -1;
                        Log::info('Updated orderweb auto from response', ['auto' => $orderweb->auto]);
                    } else if (isset($response_arr["action"]) && $response_arr["action"] == "Заказ снят") {
                        $orderweb->closeReason = 1;
                        Log::info('Set closeReason to 1 for canceled order');
                    } else {
                        $orderweb->closeReason = $response_arr["close_reason"] ?? -1;
                        Log::info('Set closeReason from response', ['closeReason' => $orderweb->closeReason]);
                    }

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

        if ($orderweb) {
            if (in_array($orderweb->closeReason, ['101', '102', '103', '104'])) {

                $responseArr = [
                    'close_reason' => $orderweb->closeReason
                ];

                $responseArr['uid'] = $dispatching_order_uid;


                $storedData = $orderweb->auto ?? null;
                Log::debug('Set orderweb auto initially', ['auto' => $storedData]);
                if ($storedData != null) {
                    $dataDriver = json_decode($storedData, true);
//            $name = $dataDriver["name"];
                    $color = $dataDriver["color"];
                    $brand = $dataDriver["brand"];
                    $model = $dataDriver["model"];
                    $number = $dataDriver["number"];
                    $phoneNumber = $dataDriver["phoneNumber"];

                    $auto = "$number, цвет $color  $brand $model. ";


                    // Обновление полей
                    $responseArr['order_car_info'] = $auto;
                    $responseArr['driver_phone'] = $phoneNumber;
                    $responseArr['time_to_start_point'] = $orderweb->time_to_start_point;

                }

                $action = 'Поиск авто';
                switch ($orderweb->closeReason) {
                    // Block 2: Состояния "Авто найдено"
                    case '101':
                        $action = 'Авто найдено';
                        Log::info('Switch: Авто найдено', ['action' => $action]);
                        break;
                    case '102':
                        $action = 'На месте';
                        Log::info('Switch: На месте', ['action' => $action]);
                        break;

                    case '103':
                        $action = 'В пути';
                        Log::info('Switch: В пути', ['action' => $action]);
                        break;

                    case '104':
                        $action = "Заказ выполнен";
                        Log::info('Switch: Заказ выполнен', ['action' => $action]);
                        break;
                }

                Log::debug('Saving orderweb after action determination', ['orderweb' => $orderweb->toArray()]);
                $responseArr['action'] = $action;

                Log::info('Exiting function',  $responseArr);
                return response()->json( $responseArr);
            }
        } else {
            $action = 'Поиск авто';
            $responseArr['action'] = $action;
            response()->json( $responseArr);
        }

        $uid_history = Uid_history::where("uid_bonusOrderHold", $dispatching_order_uid)->first();

        if ($uid_history) {
            // Если запись найдена, выходим из цикла
            $nalOrderInput = $uid_history->double_status;
            $cardOrderInput = $uid_history->bonus_status;
        } else {
            $uid_history = Uid_history::where("uid_doubleOrder", $dispatching_order_uid)->first();

            if ($uid_history) {
                // Если запись найдена, выходим из цикла
                $nalOrderInput = $uid_history->double_status;
                $cardOrderInput = $uid_history->bonus_status;
                $dispatching_order_uid = $uid_history->uid_bonusOrder;
            }
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
                $orderweb->auto = $autoInfoNal ?? $autoInfoCard ?? null;
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
}

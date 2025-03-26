<?php

namespace App\Http\Controllers;

use App\Models\ExecutionStatus;
use App\Models\Orderweb;
use App\Models\Uid_history;
use Illuminate\Http\Request;
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
        (new MessageSentController)->sentMessageAdmin($messageAdmin);

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
        (new MessageSentController)->sentMessageAdmin($messageAdmin);
//
//        $messageAdmin = "getOrderStatusMessageResult response: dispatching_order_uid ". $response ;
//        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        return $response; // Возвращаем результат с action в JSON
    }

    public function getOrderStatusMessageResultPush($dispatching_order_uid)
    {
        $uid_history = Uid_history::where("uid_bonusOrderHold", $dispatching_order_uid)->first();

        $nalOrderInput = $uid_history->double_status;
        $cardOrderInput = $uid_history->bonus_status;

        $messageAdmin = "getOrderStatusMessageResultPush: nal: $nalOrderInput, card: $cardOrderInput";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        $nalOrder = json_decode($nalOrderInput, true);
        $cardOrder = json_decode($cardOrderInput, true);

        $nalState = $nalOrder['execution_status'] ?? 'SearchesForCar';
        $cardState = $cardOrder['execution_status'] ?? 'SearchesForCar';

        $messageAdmin = "getOrderStatusMessageResultPush real: nalState: $nalState, cardState: $cardState";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);


        $orderweb = Orderweb::where("dispatching_order_uid", $dispatching_order_uid)->first();

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
        $email = $orderweb->email;

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

        $response = $this->addActionToResponseUid($response, $action, $dispatching_order_uid);

        $messageAdmin = "getOrderStatusMessageResultPush response: {$response}";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);

        $response_arr = json_decode($response, true);
        if (isset($response_arr["order_car_info"]) && $response_arr["order_car_info"] != null) {
            $orderweb->auto = $response_arr["order_car_info"];
        }
        if (isset($response_arr["action"]) && $response_arr["action"] == "Заказ снят") {
            $orderweb->closeReason = 1;
        } else {
            $orderweb->closeReason = $response_arr["close_reason"] ?? -1; // Значение по умолчанию, если close_reason тоже отсутствует
        }

        $orderweb->save();

        $messageAdmin = "getOrderStatusMessageResultPush action: {$action}, nalState: $nalState, cardState: $cardState";
        (new MessageSentController)->sentMessageAdminLog($messageAdmin);
//
//        $messageAdmin = "getOrderStatusMessageResult response: dispatching_order_uid ". $response ;
//        (new MessageSentController)->sentMessageAdmin($messageAdmin);

        (new PusherController)->sendDoubleStatus($response, $app, $email);

//        return $response; // Возвращаем результат с action в JSON
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

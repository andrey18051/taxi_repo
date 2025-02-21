<?php

namespace App\Http\Controllers;

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

    public function getOrderStatusMessageResult($currentOrderInput, $nextOrderInput)
    {
        $currentOrder = is_string($currentOrderInput) ? json_decode($currentOrderInput, true) : $currentOrderInput;
        $nextOrder = is_string($nextOrderInput) ? json_decode($nextOrderInput, true) : $nextOrderInput;

        if (!is_array($currentOrder) || !is_array($nextOrder)) {
            $this->log("Error: Input parameters must be valid JSON strings or arrays");
            return null;
        }

        $currentState = $currentOrder['execution_status'] ?? '';
        $nextState = $nextOrder['execution_status'] ?? '';
        $closeReasonCurrent = $currentOrder['close_reason'] ?? -1;
        $closeReasonNext = $nextOrder['close_reason'] ?? -1;
        $closeReason = ($currentState === 'Canceled') ? $closeReasonCurrent :
            ($nextState === 'Canceled') ? $closeReasonNext : -1;

        $this->log("Processing state transition: $currentState -> $nextState (closeReason: $closeReason)");

        if ($currentState !== 'Canceled' && $nextState === 'Canceled') {
            return $currentOrder;
        } elseif ($currentState === 'Canceled' && $nextState !== 'Canceled') {
            return $nextOrder;
        } elseif ($currentState !== 'Canceled' && $nextState !== 'Canceled') {
            if ($currentState === 'Running') {
                return $currentOrder;
            } elseif ($nextState === 'Running') {
                return $nextOrder;
            } else {
                return $currentOrder;
            }
        } else { // оба Canceled
            if ($closeReasonCurrent != -1) {
                return $currentOrder; // отдаём приоритет тому, у кого есть причина отмены
            } elseif ($closeReasonNext != -1) {
                return $nextOrder;
            } else {
                return $currentOrder; // по умолчанию первый
            }
        }
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

<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Mail\Server;
use App\Models\BonusBalance;
use App\Models\BonusTypes;
use App\Models\City;
use App\Models\Orderweb;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Diff\Exception;

class BonusBalanceController extends Controller
{

    public function recordsAdd(
        $orderwebs_id,
        $users_id,
        $bonus_types_id,
        $bonusAdd
    ) {
        $bonus_types_size = BonusTypes::find($bonus_types_id)->size;

        $balance_records = new BonusBalance();
        $balance_records->orderwebs_id = $orderwebs_id;
        $balance_records->users_id = $users_id;
        $balance_records->bonus_types_id = $bonus_types_id;
        $balance_records->bonusAdd = $bonusAdd * $bonus_types_size;
        $balance_records->save();

        Log::debug($balance_records);
    }
    public function recordsDel(
        $orderwebs_id,
        $users_id,
        $bonus_types_id,
        $bonusDel
    ) {
        $bonus_types_size = BonusTypes::find($bonus_types_id)->size;

        $balance_records = new BonusBalance();
        $balance_records->orderwebs_id = $orderwebs_id;
        $balance_records->users_id = $users_id;
        $balance_records->bonus_types_id = $bonus_types_id;
        $balance_records->bonusDel = $bonusDel * $bonus_types_size;
        $balance_records->save();

        Log::debug($balance_records);
    }
    public function recordsBloke($uid)
    {
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();

        $bonusBlockedBalanceRecord = BonusBalance::where("orderwebs_id", $order->id)->first();
        if (!$bonusBlockedBalanceRecord) {
            $email = $order->email;

            $bonusBloke = $order->web_cost;
            $orderwebs_id = $order->id;

            $user = User::where('email', $email)->get()->toArray();

            $users_id = $user[0]['id'];
            $bonus_types_size = BonusTypes::find(6)->size;

            Log::debug("BonusBalance:", [
                '$orderwebs_id ' => $orderwebs_id,
                '$users_id ' => $users_id,
                '$bonusBloke' => $bonusBloke,
                '$bonus_types_size' => $bonus_types_size
            ]);
            $balance_records = new BonusBalance();
            $balance_records->orderwebs_id = $orderwebs_id;
            $balance_records->users_id = $users_id;
            $balance_records->bonus_types_id = 6;
            $balance_records->bonusBloke = $bonusBloke * $bonus_types_size;
            $balance_records->save();

            $response["bonus"] = $bonusBloke * $bonus_types_size;

            return response($response, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function userBalance($users_id)
    {

        self::balanceReview($users_id);
        $balance_records = BonusBalance::where("users_id", $users_id)->get();
        $userBalance = 0;
        foreach ($balance_records->toArray() as $value) {
            if ($value["bonusAdd"] != 0) {
                $userBalance += $value["bonusAdd"];
            }
            if ($value["bonusDel"] != 0) {
                $userBalance -= $value["bonusDel"];
            }
            if ($value["bonusBloke"] != 0) {
                $userBalance -= $value["bonusBloke"];
            }
        }

        $user = User::find($users_id);
//        if ($user->bonus < $userBalance) {
            $user->bonus = $userBalance;
            $user->save();
//        }


        return $userBalance;
    }
    public function userBalanceBloke($users_id)
    {
        $balance_records = BonusBalance::where("users_id", $users_id)->get();
        $userBalanceBlock = 0;
        foreach ($balance_records->toArray() as $value) {
            $userBalanceBlock += $value["bonusBloke"];
        }
        return $userBalanceBlock;
    }

    public function userBalanceHistory($users_id)
    {
        return BonusBalance::where("users_id", $users_id)->get()->toArray();
    }

    public function blockBonusToDelete($orderwebs_id)
    {
        $balance_records = BonusBalance::where("orderwebs_id", $orderwebs_id)
            ->where("bonus_types_id", 6)
            ->where("bonusBloke", "!=", 0)
            ->first();
        $balance_records_2 = BonusBalance::where("orderwebs_id", $orderwebs_id)
            ->where(function ($query) {
                $query->where("bonus_types_id", 4)
                    ->orWhere("bonus_types_id", 5);
            })
            ->first();
        if (!$balance_records_2) {
            $balance_records_new = new BonusBalance();

            $balance_records_new->bonusDel = $balance_records->bonusBloke;
            $balance_records_new->orderwebs_id = $orderwebs_id;
            $balance_records_new->users_id = $balance_records->users_id;
            $balance_records_new->bonusBloke = (-1) * $balance_records->bonusBloke;
            $balance_records_new->bonus_types_id = 5;
            $balance_records_new->save();
        }
    }

    private function blockBonusToDeleteCost($orderwebs_id, $cost)
    {
        $balance_records = BonusBalance::where("orderwebs_id", $orderwebs_id)
            ->where("bonus_types_id", 6)
            ->where("bonusBloke", "!=", 0)
            ->first();
        $balance_records_2 = BonusBalance::where("orderwebs_id", $orderwebs_id)
            ->where(function ($query) {
                $query->where("bonus_types_id", 4)
                    ->orWhere("bonus_types_id", 5);
            })
            ->first();

        $bonusType = BonusTypes::where("id", 5)->first();

        if (!$balance_records_2) {
            $balance_records_new = new BonusBalance();

            $balance_records_new->bonusDel = $cost * $bonusType->size;
            $balance_records_new->orderwebs_id = $orderwebs_id;
            $balance_records_new->users_id = $balance_records->users_id;
            $balance_records_new->bonusBloke = (-1) *  $balance_records->bonusBloke;
            $balance_records_new->bonus_types_id = 5;
            $balance_records_new->save();
        }
    }

    public function blockBonusReturn($orderwebs_id)
    {
        $balance_records = BonusBalance::where("orderwebs_id", $orderwebs_id)
            ->where("bonus_types_id", 6)
            ->where("bonusBloke", "!=", 0)
            ->first();

        $balance_records_2 = BonusBalance::where("orderwebs_id", $orderwebs_id)
            ->where(function ($query) {
                $query->where("bonus_types_id", 4)
                    ->orWhere("bonus_types_id", 5);
            })
            ->first();
        if (!$balance_records_2) {
             $balance_records_new = new BonusBalance();

             $balance_records_new->orderwebs_id = $orderwebs_id;
             $balance_records_new->users_id = $balance_records->users_id;
             $balance_records_new->bonusBloke = (-1) * $balance_records->bonusBloke;
             $balance_records_new->bonus_types_id = 4;

             $balance_records_new->save();
        }
    }

    public function balanceReview($users_id)
    {
        /**
        * Начисление за 1 вход в день в приложение
        */
        $latestBalanceRecord = BonusBalance::where("users_id", $users_id)
            ->where("bonus_types_id", 3)
            ->latest("updated_at")
            ->first();


        if ($latestBalanceRecord) {
            $daysAgo = now()->subDay()->startOfDay(); // Получить текущую дату и вычесть один день, затем обнулить время.

            if ($latestBalanceRecord->updated_at->startOfDay() <= $daysAgo) {
                // Если дата обновления, обнуленная по времени, меньше или равна предыдущей дате, обнуленной по времени, на один день назад, выполните код.
                self::recordsAdd(0, $users_id, 3, 1);
            }
        } else {
            // Если записей не найдено, вы можете выполнить соответствующие действия.
            self::recordsAdd(0, $users_id, 3, 1);
        }
        /**
         * Начисление бонусов за первую поездку
         */
        $orderBalanceRecord2 = BonusBalance::where("users_id", $users_id)
            ->where("bonus_types_id", 2)
            ->first();

        $orderBalanceRecord7 = BonusBalance::where("users_id", $users_id)
            ->where("bonus_types_id", 7)
            ->first();

        if ($orderBalanceRecord2 != null && $orderBalanceRecord7 == null) {
             self::recordsAdd(0, $users_id, 7, 1);
        }
        /**
         * Начисление бонусов за выполненную поездку
         */

        $user = User::find($users_id);

        $orderNotComplete = Orderweb::where("email", $user->email)
            ->where("closeReason", "-1")->get();

        if ($orderNotComplete != null) {
            $orderNotCompleteArray = $orderNotComplete->toArray();
            foreach ($orderNotCompleteArray as $value) {
                UIDController::UIDStatusReviewAdmin($value['dispatching_order_uid']);
            }
        }

        $orderComplete = Orderweb::where("email", $user->email)
            ->where(function ($query) {
                $query->where("closeReason", 0)
                ->orWhere("closeReason", 8);
            })->get();

        if ($orderComplete != null) {
            $orderCompleteArray = $orderComplete->toArray();

            $orderBalanceRecord = BonusBalance::where("users_id", $users_id)
                ->where("bonus_types_id", 2)
                ->get();
            if ($orderBalanceRecord != null) {
                $orderBalanceRecordArray = $orderBalanceRecord->toArray();

                foreach ($orderCompleteArray as $value) {
                    $verify = false;
                    foreach ($orderBalanceRecordArray as $item) {
                        if ($value["id"] == $item['orderwebs_id']) {
                            $verify = true;
                            break;
                        };
                    }
                    if (false == $verify) {
                        $bonus = self::historyUID($value["id"]);
                        self::recordsAdd($value["id"], $users_id, 2, $bonus);
                    };
                }
            } else {
                foreach ($orderCompleteArray as $value) {
                    $bonus = self::historyUID($value["id"]);
                    self::recordsAdd($value["id"], $users_id, 2, $bonus);
                }
            }
        }


        /**
         * Разблокировка бонусов
         */

        $totalBonus = BonusBalance::where('users_id', $users_id)->sum('bonusBloke');

        if ($totalBonus != 0) {
            $bonusBlockedBalanceRecord = BonusBalance::where("users_id", $users_id)
                ->where(function ($query) {
                    $query->where("bonus_types_id", 4)
                        ->orWhere("bonus_types_id", 6);
                })
//                ->where('orderwebs_id', '!=', 0)
//                ->select('orderwebs_id', 'bonusBloke')
                ->get()->toArray();

            $bonusSumByOrderwebsId = [];

            $uniqueArray = [];

            foreach ($bonusBlockedBalanceRecord as $item) {
                $orderwebsId = $item["orderwebs_id"];
                $bonusBloke = $item["bonusBloke"];

                // Если запись с таким orderwebs_id уже есть в результате
                if (isset($bonusSumByOrderwebsId[$orderwebsId])) {
                    // Обновляем сумму bonusBloke для данного orderwebs_id
                    $bonusSumByOrderwebsId[$orderwebsId] += $bonusBloke;
                } else {
                    // Если записи с таким orderwebs_id нет, добавляем ее в результат
                    $bonusSumByOrderwebsId[$orderwebsId] = $bonusBloke;
                }
            }

// Проходим по уникальным записям и добавляем их в результат
            foreach ($bonusBlockedBalanceRecord as $item) {
                $orderwebsId = $item["orderwebs_id"];
                if ($bonusSumByOrderwebsId[$orderwebsId] !== 0) {
                    $uniqueArray[] = $item;
                }
            }
//            dd($uniqueArray);
            foreach ($uniqueArray as $value) {
                self::historyUIDunBlocked($value['orderwebs_id']);
            }
        }
     }

    public function balanceReviewDaily()
    {
        Log::debug("balanceReviewDaily");

        /**
         * Начисление бонусов за выполненную поездку
         */
        $orderNotComplete = Orderweb::where("closeReason", "-1")->get();

        if ($orderNotComplete != null) {
            $orderNotCompleteArray = $orderNotComplete->toArray();
            foreach ($orderNotCompleteArray as $value) {
                (new UIDController)->UIDStatusReviewAdmin($value['dispatching_order_uid']);
            }
        }

        $orderComplete = Orderweb::
            where(function ($query) {
                $query->where("closeReason", 0)
                ->orWhere("closeReason", 8);
            })->get();

        if ($orderComplete != null) {
            $orderCompleteArray = $orderComplete->toArray();

            foreach ($orderCompleteArray as $value) {
                if (isset($value['users_id'])) {
                    $orderBalanceRecord = BonusBalance::where("users_id", $value['id'])
                        ->where("bonus_types_id", 2)
                        ->get();
                    if ($orderBalanceRecord != null) {
                        $orderBalanceRecordArray = $orderBalanceRecord->toArray();

                        foreach ($orderCompleteArray as $value2) {
                            $verify = false;
                            foreach ($orderBalanceRecordArray as $item) {
                                if ($value2["id"] == $item['orderwebs_id']) {
                                    $verify = true;
                                    break;
                                };
                            }
                            if (false == $verify) {
                                $bonus = self::historyUID($value2["id"]);
                                self::recordsAdd($value2["id"], $value2['users_id'], 2, $bonus);
                            };
                        }
                    } else {
                        foreach ($orderCompleteArray as $item) {
                            $bonus = self::historyUID($item["id"]);
                            self::recordsAdd($item["id"], $item['users_id'], 2, $bonus);
                        }
                    }
                }
            }
        }
        /**
         * Разблокировка бонусов
         */
         self::bonusUnBlocked();
    }

    public function bonusUnBlocked()
    {
        /**
         * Разблокировка бонусов
         */
        $usersBlocked = BonusBalance::all()->toArray();
        foreach ($usersBlocked as $valueUser) {
            $users_id =  $valueUser['users_id'];
            $totalBonus = BonusBalance::where('users_id', $users_id)->sum('bonusBloke');

            if ($totalBonus != 0) {
                $bonusBlockedBalanceRecord = BonusBalance::where("users_id", $users_id)
                    ->where(function ($query) {
                        $query->where("bonus_types_id", 4)
                            ->orWhere("bonus_types_id", 6);
                    })
                    ->get()->toArray();

                $bonusSumByOrderwebsId = [];

                $uniqueArray = [];

                foreach ($bonusBlockedBalanceRecord as $item) {
                    $orderwebsId = $item["orderwebs_id"];
                    $bonusBloke = $item["bonusBloke"];

                    // Если запись с таким orderwebs_id уже есть в результате
                    if (isset($bonusSumByOrderwebsId[$orderwebsId])) {
                        // Обновляем сумму bonusBloke для данного orderwebs_id
                        $bonusSumByOrderwebsId[$orderwebsId] += $bonusBloke;
                    } else {
                        // Если записи с таким orderwebs_id нет, добавляем ее в результат
                        $bonusSumByOrderwebsId[$orderwebsId] = $bonusBloke;
                    }
                }

                // Проходим по уникальным записям и добавляем их в результат
                foreach ($bonusBlockedBalanceRecord as $item) {
                    $orderwebsId = $item["orderwebs_id"];
                    if ($bonusSumByOrderwebsId[$orderwebsId] !== 0) {
                        $uniqueArray[] = $item;
                    }
                }
                //            dd($uniqueArray);
                foreach ($uniqueArray as $value) {
                    self::historyUIDunBlocked($value['orderwebs_id']);
                }
            }
        }
    }

    public function bonusUnBlockedUser($email)
    {
       /**
        * Разблокировка бонусов
        */

        $user = User::where('email', $email)->first();
        $users_id = $user->id;
        $totalBonus = BonusBalance::where('users_id', $users_id)->sum('bonusBloke');
        if ($totalBonus != 0) {
            $bonusBlockedBalanceRecord = BonusBalance::where("users_id", $users_id)
                ->where(function ($query) {
                    $query->where("bonus_types_id", 4)
                        ->orWhere("bonus_types_id", 6);
                })
                ->get()->toArray();

            $bonusSumByOrderwebsId = [];

            $uniqueArray = [];

            foreach ($bonusBlockedBalanceRecord as $item) {
                $orderwebsId = $item["orderwebs_id"];
                $bonusBloke = $item["bonusBloke"];

                // Если запись с таким orderwebs_id уже есть в результате
                if (isset($bonusSumByOrderwebsId[$orderwebsId])) {
                    // Обновляем сумму bonusBloke для данного orderwebs_id
                    $bonusSumByOrderwebsId[$orderwebsId] += $bonusBloke;
                } else {
                    // Если записи с таким orderwebs_id нет, добавляем ее в результат
                    $bonusSumByOrderwebsId[$orderwebsId] = $bonusBloke;
                }
            }

             // Проходим по уникальным записям и добавляем их в результат
            foreach ($bonusBlockedBalanceRecord as $item) {
                $orderwebsId = $item["orderwebs_id"];
                if ($bonusSumByOrderwebsId[$orderwebsId] !== 0) {
                    $uniqueArray[] = $item;
                }
            }
            //            dd($uniqueArray);
            foreach ($uniqueArray as $value) {
                self::historyUIDunBlocked($value['orderwebs_id']);
            }
        }
    }

    public function bonusUnBlockedUid($bonusOrder, $doubleOrder, $bonusOrderHold)
    {
       /**
        * Разблокировка бонусов
        */

        $result = 0;

        $order = Orderweb::where("dispatching_order_uid", $bonusOrderHold)->first();

        $connectAPI =  $order->server;
        $autorization = self::autorization($connectAPI);
        $identificationId = $order->comment;

        $amount = $order->web_cost;
        $amount_settle = $amount;

        Log::debug("bonusUnBlockedUid holdOrderCost" .$amount);

        $bonusOrder_response = (new UIDController)->closeReasonUIDStatusFirstWfp(
            $bonusOrder,
            $connectAPI,
            $autorization,
            $identificationId
        );
        if ($bonusOrder_response != -1) {
            $closeReason_bonusOrder = $bonusOrder_response["close_reason"];
            $order_cost_bonusOrder = $bonusOrder_response["order_cost"];
            Log::debug("closeReason_bonusOrder: $closeReason_bonusOrder");
            Log::debug("order_cost_bonusOrder: $order_cost_bonusOrder");
        } else {
            $closeReason_bonusOrder = -1;
            $order_cost_bonusOrder = $amount;
            WfpController::messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $bonusOrder);
        }
        $doubleOrder_response = (new UIDController)->closeReasonUIDStatusFirstWfp(
            $doubleOrder,
            $connectAPI,
            $autorization,
            $identificationId
        );
        if ($doubleOrder_response != -1) {
            $closeReason_doubleOrder = $doubleOrder_response["close_reason"];
            $order_cost_doubleOrder = $doubleOrder_response["order_cost"];
            Log::debug("closeReason_doubleOrder: $closeReason_doubleOrder");
            Log::debug("order_cost_doubleOrder : $order_cost_doubleOrder");
        } else {
            $closeReason_doubleOrder = -1;
            $order_cost_doubleOrder = $amount;
            WfpController::messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $doubleOrder);
        }


        $bonusOrderHold_response = (new UIDController)->closeReasonUIDStatusFirstWfp(
            $bonusOrderHold,
            $connectAPI,
            $autorization,
            $identificationId
        );
        if ($bonusOrderHold_response != -1) {
            $closeReason_bonusOrderHold = $bonusOrderHold_response["close_reason"];
            $order_cost_bonusOrderHold = $bonusOrderHold_response["order_cost"];
            Log::debug("closeReason_bonusOrderHold: $closeReason_bonusOrderHold");
            Log::debug("order_cost_bonusOrderHold : $order_cost_bonusOrderHold");
        } else {
            $closeReason_bonusOrderHold = -1;
            $order_cost_bonusOrderHold = $amount;
            WfpController::messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $bonusOrderHold);
        }

        $hold_bonusOrder = false;
        switch ($closeReason_bonusOrder) {
            case "0":
            case "8":
                $hold_bonusOrder = true;
                $amount_settle = $order_cost_bonusOrder;
                $result = 1;
                break;
        }
        $hold_doubleOrder = false;
        switch ($closeReason_doubleOrder) {
            case "0":
            case "8":
                $hold_doubleOrder = true;
                $amount_settle = $order_cost_doubleOrder;
                $result = 1;
                break;
        }
        $hold_bonusOrderHold = false;
        switch ($closeReason_bonusOrderHold) {
            case "0":
            case "8":
                $hold_bonusOrderHold = true;
                $amount_settle = $order_cost_bonusOrderHold;
                $result = 1;
                break;
        }
        if ($amount >= $amount_settle) {
            self::blockBonusReturn($order->id);
            $amount = $amount_settle;
            $order->web_cost = $amount;
            $order->save();
            self::recordsBloke($bonusOrderHold);
        } else {
            $subject = "Оплата поездки больше холда";

            $messageAdmin = "Заказ $bonusOrderHold. Сервер $connectAPI. Время $order->created_at.
                 Маршрут $order->routefrom - $order->routeto.
                 Телефон клиента:  $order->user_phone.
                 Сумма холда $amount грн. Сумма заказа $amount_settle грн.";
            $paramsAdmin = [
                'subject' => $subject,
                'message' => $messageAdmin,
            ];
            $alarmMessage = new TelegramController();

            try {
                $alarmMessage->sendAlarmMessage($messageAdmin);
                $alarmMessage->sendMeMessage($messageAdmin);
            } catch (Exception $e) {
                $subject = 'Ошибка в телеграмм';
                $paramsCheck = [
                    'subject' => $subject,
                    'message' => $e,
                ];

                Mail::to('taxi.easy.ua@gmail.com')->send(new Check($paramsCheck));
            };

            Mail::to('cartaxi4@gmail.com')->send(new Server($paramsAdmin));
            Mail::to('taxi.easy.ua@gmail.com')->send(new Server($paramsAdmin));
        }
        if ($hold_bonusOrder || $hold_doubleOrder || $hold_bonusOrderHold) {
            self::blockBonusToDeleteCost($order->id, $amount);

            $result = 1;
            if ($hold_bonusOrder) {
                $order->closeReason = $closeReason_bonusOrder;
            }
            if ($hold_doubleOrder) {
                $order->closeReason = $closeReason_doubleOrder;
            }
            if ($hold_bonusOrderHold) {
                $order->closeReason = $closeReason_bonusOrderHold;
            }
        } else {
            if ($closeReason_bonusOrder != "-1"
                || $closeReason_doubleOrder != "-1"
                || $closeReason_bonusOrderHold != "-1") {
                self::blockBonusReturn($order->id);

                $result = 1;
                $order->closeReason = $closeReason_bonusOrderHold;
            }
        }

        $order->save();
        return $result;
    }

    private function closeReasonUIDStatusFirst($uid, $connectAPI, $autorization, $identificationId)
    {
        $url = $connectAPI . '/api/weborders/' . $uid;
        $response = Http::withHeaders([
            "Authorization" => $autorization,
            "X-WO-API-APP-ID" => $identificationId,
        ])->get($url);
        if ($response->status() == 200) {
            $response_arr = json_decode($response, true);
            Log::debug('closeReasonUIDStatusFirst ' . json_encode($response_arr));
            return $response_arr;
        }
        return "-1";
    }

    private function autorization($connectApi)
    {

        $city = City::where('address', str_replace('http://', '', $connectApi))->first();

        $username = $city->login;
        $password = hash('SHA512', $city->password);

        return 'Basic ' . base64_encode($username . ':' . $password);
    }

    public function historyUID($id)
    {

        $order = Orderweb::find($id);

        $connectApi =str_replace('http://', '', $order->server);

        $city = City::where('address', $connectApi)->first();

        $username = $city->login;
        $password = hash('SHA512', $city->password);

        $autorization = 'Basic ' . base64_encode($username . ':' . $password);

        $url = 'http://' . $connectApi . '/api/weborders/'  . $order->dispatching_order_uid;

        $response = Http::withHeaders([
            "Authorization" => $autorization,
            "X-WO-API-APP-ID" => $order->comment,
        ])->get($url);

        Log::debug("historyUID " . $url);

//        dd($response);
        return $response['order_cost'];
    }

    public function historyUIDunBlocked($id)
    {

        $order = Orderweb::where("id", $id)->first()->toArray();

        $connectApi =str_replace('http://', '', $order['server']);

        $city = City::where('address', $connectApi)->first();

        $username = $city->login;
        $password = hash('SHA512', $city->password);

        $autorization = 'Basic ' . base64_encode($username . ':' . $password);

        $url = 'http://' . $connectApi . '/api/weborders/'  . $order['dispatching_order_uid'];

        $response = Http::withHeaders([
            "Authorization" => $autorization,
            "X-WO-API-APP-ID" => $order['comment'],
        ])->get($url);

        Log::debug("historyUIDunBlocked " . $url);

        if ($response["close_reason"] == 0) {
            self::blockBonusToDelete($id);
        };
        if ($response["close_reason"] == 8) {
            self::blockBonusToDelete($id);
        } elseif ($response["close_reason"] != -1) {
            self::blockBonusReturn($id);
        };
    }
}

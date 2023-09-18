<?php

namespace App\Http\Controllers;

use App\Models\BonusBalance;
use App\Models\BonusTypes;
use App\Models\City;
use App\Models\Orderweb;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

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
    }
    public function recordsBloke(
        $uid
    ) {
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();
//dd( $order);
        $email = $order->email;

        $bonusBloke = $order->web_cost;
        $orderwebs_id = $order->id;

        $user = User::where('email', $email)->get()->toArray();
//dd($user);
        $users_id =  $user[0]['id'];
        $bonus_types_size = BonusTypes::find(6)->size;

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
        $user->bonus = $userBalance;
        $user->save();

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

//        if ($latestBalanceRecord) {
//            // В $latestBalanceRecord содержится запись с самой поздней датой в поле "updated_at"
//            // Вы можете использовать эту запись по вашим нуждам
//            $currentTime = time();
//            $timeElapsed = $currentTime - strtotime($latestBalanceRecord->updated_at);
//            if ($timeElapsed >= 24 * 60 * 60) {
//                self::recordsAdd(0, $users_id, 3, 1);
//            }
//        } else {
//            // Если записей не найдено, вы можете выполнить соответствующие действия
//            self::recordsAdd(0, $users_id, 3, 1);
//        }


        if ($latestBalanceRecord) {
            $daysAgo = now()->subDay(); // Получить текущую дату и вычесть один день.

            if ($latestBalanceRecord->updated_at <= $daysAgo) {
                // Если дата обновления меньше или равна дате, предшествующей текущей дате на один день, выполните код.
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

//        $orderNotComplete = Orderweb::where("email", $user->email)
//            ->where(function ($query) {
//                $query->where("closeReason", "!=", 0)
//                    ->orWhere("closeReason", "!=", 8);
//            })->get();
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

        $bonusBlockedBalanceRecord = BonusBalance::where("bonus_types_id", 6)->get();

        if (!$bonusBlockedBalanceRecord->isEmpty()) {
            foreach ($bonusBlockedBalanceRecord->toArray() as $value) {
                self::historyUIDunBlocked($value['orderwebs_id']);
            }
        }
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
//dd($response);
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
//dd($response["close_reason"]);
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

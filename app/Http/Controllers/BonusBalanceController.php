<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Mail\Server;
use App\Models\BonusBalance;
use App\Models\BonusBalancePas1;
use App\Models\BonusBalancePas2;
use App\Models\BonusBalancePas4;
use App\Models\BonusTypes;
use App\Models\City;
use App\Models\Orderweb;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Diff\Exception;

class BonusBalanceController extends Controller
{

//    public function recordsAdd(
//        $orderwebs_id,
//        $users_id,
//        $bonus_types_id,
//        $bonusAdd
//    ) {
//        $bonus_types_size = BonusTypes::find($bonus_types_id)->size;
//
//        $balance_records = new BonusBalance();
//        $balance_records->orderwebs_id = $orderwebs_id;
//        $balance_records->users_id = $users_id;
//        $balance_records->bonus_types_id = $bonus_types_id;
//        $balance_records->bonusAdd = $bonusAdd * $bonus_types_size;
//        $balance_records->save();
//
//        Log::debug("recordsAdd", $balance_records->toArray());
//    }
    public function recordsAddApp(
        $orderwebs_id,
        $users_id,
        $bonus_types_id,
        $bonusAdd,
        $app
    ) {
        $bonus_types_size = BonusTypes::find($bonus_types_id)->size;

//        $balance_records = new BonusBalance();
        switch ($app) {
            case "PAS1":
                $balance_records = new BonusBalancePas1();
                break;
            case "PAS2":
                $balance_records = new BonusBalancePas2();
                break;
            default:
                $balance_records = new BonusBalancePas4();
        }


        $balance_records->orderwebs_id = $orderwebs_id;
        $balance_records->users_id = $users_id;
        $balance_records->bonus_types_id = $bonus_types_id;
        $balance_records->bonusAdd = $bonusAdd * $bonus_types_size;
        $balance_records->save();

        Log::debug("recordsAddApp", $balance_records->toArray());
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

        Log::debug("recordsDel", $balance_records->toArray());
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
            Log::debug("recordsBloke", $response);
            return response($response, 200)
                ->header('Content-Type', 'json');
        }
    }

    public function recordsBlokeApp($uid, $app)
    {
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();

//        $bonusBlockedBalanceRecord = BonusBalance::where("orderwebs_id", $order->id)->first();
        switch ($app) {
            case "PAS1":
                $bonusBlockedBalanceRecord = BonusBalancePas1::where("orderwebs_id", $order->id)->first();
                break;
            case "PAS2":
                $bonusBlockedBalanceRecord = BonusBalancePas2::where("orderwebs_id", $order->id)->first();
                break;
            default:
                $bonusBlockedBalanceRecord = BonusBalancePas4::where("orderwebs_id", $order->id)->first();
        }
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

            switch ($app) {
                case "PAS1":
                    $balance_records = new BonusBalancePas1();
                    break;
                case "PAS2":
                    $balance_records = new BonusBalancePas2();
                    break;
                default:
                    $balance_records = new BonusBalancePas4();
            }
            $balance_records->orderwebs_id = $orderwebs_id;
            $balance_records->users_id = $users_id;
            $balance_records->bonus_types_id = 6;
            $balance_records->bonusBloke = $bonusBloke * $bonus_types_size;
            $balance_records->save();

            $response["bonus"] = $bonusBloke * $bonus_types_size;
            Log::debug("recordsBloke", $response);
            return response($response, 200)
                ->header('Content-Type', 'json');
        }
    }
    public function recordsBlokeAmount($uid)
    {
        $order = Orderweb::where('dispatching_order_uid', $uid)->first();
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
        Log::debug("recordsBlokeAmount", $response);
        return response($response, 200)
            ->header('Content-Type', 'json');
    }

//    public function userBalance($users_id)
//    {
//
//        self::balanceReview($users_id);
//        $balance_records = BonusBalance::where("users_id", $users_id)->get();
//        $userBalance = 0;
//        foreach ($balance_records->toArray() as $value) {
//            if ($value["bonusAdd"] != 0) {
//                $userBalance += $value["bonusAdd"];
//            }
//            if ($value["bonusDel"] != 0) {
//                $userBalance -= $value["bonusDel"];
//            }
//            if ($value["bonusBloke"] != 0) {
//                $userBalance -= $value["bonusBloke"];
//            }
//        }
//
//        $user = User::find($users_id);
//        $user->bonus = $userBalance;
//        $user->save();
//        Log::debug("userBalance $userBalance");
//
//        return $userBalance;
//    }
    public function userBalanceApp($users_id, $app)
    {

        self::balanceReviewApp($users_id, $app);
        switch ($app) {
            case "PAS1":
                $balance_records = BonusBalancePas1::where("users_id", $users_id)->get();
                break;
            case "PAS2":
                $balance_records = BonusBalancePas2::where("users_id", $users_id)->get();
                break;
            default:
                $balance_records = BonusBalancePas4::where("users_id", $users_id)->get();
        }
        Log::debug("userBalanceApp $balance_records");
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
        switch ($app) {
            case "PAS1":
                $user->bonus_pas_1 = $userBalance;
                break;
            case "PAS2":
                $user->bonus_pas_2 = $userBalance;
                break;
            default:
                $user->bonus_pas_4 = $userBalance;
        }

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

    private function blockBonusToDeleteCostApp($orderwebs_id, $cost, $app)
    {
        Log::debug("blockBonusToDeleteCost $orderwebs_id, $cost");
//        $balance_records = BonusBalance::where("orderwebs_id", $orderwebs_id)
//            ->where("bonus_types_id", 6)
//            ->where("bonusBloke", "!=", 0)
//            ->first();

        switch ($app) {
            case "PAS1":
                $balance_records = BonusBalancePas1::where("orderwebs_id", $orderwebs_id)
                    ->where("bonus_types_id", 6)
                    ->where("bonusBloke", "!=", 0)
                    ->first();
                break;
            case "PAS2":
                $balance_records = BonusBalancePas2::where("orderwebs_id", $orderwebs_id)
                    ->where("bonus_types_id", 6)
                    ->where("bonusBloke", "!=", 0)
                    ->first();
                break;
            default:
                $balance_records = BonusBalancePas4::where("orderwebs_id", $orderwebs_id)
                    ->where("bonus_types_id", 6)
                    ->where("bonusBloke", "!=", 0)
                    ->first();
        }

//        $balance_records_2 = BonusBalance::where("orderwebs_id", $orderwebs_id)
//            ->where(function ($query) {
//                $query->where("bonus_types_id", 4)
//                    ->orWhere("bonus_types_id", 5);
//            })
//            ->first();

        $bonusType = BonusTypes::where("id", 5)->first();
        Log::debug("blockBonusToDeleteCost", [
            '$cost' =>$cost,
            '$bonusType->size' =>$bonusType->size,
            '$orderwebs_id' =>$orderwebs_id,
//            '$balance_records->users_id' =>$balance_records->users_id,
            '(-1) *  $balance_records->bonusBloke' =>(-1) *  $cost * $bonusType->size,
            '$balance_records_new->bonus_types_id' => 5,
        ]);
//        if (!$balance_records_2) {

//            $balance_records_new = new BonusBalance();
        switch ($app) {
            case "PAS1":
                $balance_records_new = new BonusBalancePas1();
                break;
            case "PAS2":
                $balance_records_new = new BonusBalancePas2();
                break;
            default:
                $balance_records_new = new BonusBalancePas4();
        }
            $balance_records_new->bonusDel = $cost * $bonusType->size;
            $balance_records_new->orderwebs_id = $orderwebs_id;
            $balance_records_new->users_id = $balance_records->users_id;
            $balance_records_new->bonusBloke = (-1) *  $cost * $bonusType->size;
            $balance_records_new->bonus_types_id = 5;
            $balance_records_new->save();
//        }
        self::recordsAddApp($orderwebs_id, $balance_records->users_id, "2", $cost, $app);
        self::userBalanceApp($balance_records->users_id, $app);
    }

    public function blockBonusReturn($orderwebs_id, $bonusBloke)
    {
        $bonusType = BonusTypes::where("id", 5)->first();

        $balance_records = BonusBalance::where("orderwebs_id", $orderwebs_id)->first();

        $balance_records_new = new BonusBalance();

        $balance_records_new->orderwebs_id = $orderwebs_id;
        $balance_records_new->users_id = $balance_records->users_id;
        $balance_records_new->bonusBloke = (-1) * $bonusBloke * $bonusType->size;
        $balance_records_new->bonus_types_id = 4;

        $balance_records_new->save();

        self::userBalance($balance_records->users_id);
    }
    public function blockBonusReturnApp($orderwebs_id, $bonusBloke, $app)
    {
        Log::info("blockBonusReturnApp");
        $bonusType = BonusTypes::where("id", 5)->first();

        switch ($app) {
            case "PAS1":
                $balance_records_new = new BonusBalancePas1();
                $balance_records = BonusBalancePas1::where("orderwebs_id", $orderwebs_id)->first();
                break;
            case "PAS2":
                $balance_records_new = new BonusBalancePas2();
                $balance_records = BonusBalancePas2::where("orderwebs_id", $orderwebs_id)->first();
                break;
            default:
                $balance_records = BonusBalancePas4::where("orderwebs_id", $orderwebs_id)->first();
                $balance_records_new = new BonusBalancePas4();
        }
        if ($balance_records != null) {
            $balance_records_new->orderwebs_id = $orderwebs_id;
            $balance_records_new->users_id = $balance_records->users_id;
            $balance_records_new->bonusBloke = (-1) * $bonusBloke * $bonusType->size;
            $balance_records_new->bonus_types_id = 4;
            $balance_records_new->save();
            self::userBalanceApp($balance_records_new->users_id, $app);
        }



    }

    public function blockBonusReturnCancel($orderwebs_id)
    {
        $balance_records_2 = BonusBalance::where("orderwebs_id", $orderwebs_id)
            ->where(function ($query) {
                $query->where("bonus_types_id", 4)
                    ->orWhere("bonus_types_id", 5);
            })
            ->first();

        if (!$balance_records_2) {
            $balance_records = BonusBalance::where("orderwebs_id", $orderwebs_id)->first();
            $bonusType = BonusTypes::where("id", 5)->first();

            $order = Orderweb::where("id", $orderwebs_id)->first();

            $balance_records_new = new BonusBalance();

            $balance_records_new->orderwebs_id = $orderwebs_id;
            $balance_records_new->users_id = $balance_records->users_id;
            $balance_records_new->bonusBloke = (-1) * $order->web_cost * $bonusType->size;
            $balance_records_new->bonus_types_id = 4;

            $balance_records_new->save();
            Log::debug("blockBonusReturnCancel balance_records_new", $balance_records_new->toArray());

            self::userBalance($balance_records->users_id);
        } else {
            Log::debug("blockBonusReturnCancel balance_records_2", $balance_records_2->toArray());
        }
    }
    public function blockBonusReurnCancelApp($orderwebs_id, $app)
    {
//        $balance_records_2 = BonusBalance::where("orderwebs_id", $orderwebs_id)
//            ->where(function ($query) {
//                $query->where("bonus_types_id", 4)
//                    ->orWhere("bonus_types_id", 5);
//            })
//            ->first();
        switch ($app) {
            case "PAS1":
                $balance_records_2 = BonusBalancePas1::where("orderwebs_id", $orderwebs_id)
                    ->where(function ($query) {
                        $query->where("bonus_types_id", 4)
                            ->orWhere("bonus_types_id", 5);
                    })
                    ->first();
                break;
            case "PAS2":
                $balance_records_2 = BonusBalancePas2::where("orderwebs_id", $orderwebs_id)
                    ->where(function ($query) {
                        $query->where("bonus_types_id", 4)
                            ->orWhere("bonus_types_id", 5);
                    })
                    ->first();
                break;
            default:
                $balance_records_2 = BonusBalancePas4::where("orderwebs_id", $orderwebs_id)
                    ->where(function ($query) {
                        $query->where("bonus_types_id", 4)
                            ->orWhere("bonus_types_id", 5);
                    })
                    ->first();
        }
        if (!$balance_records_2) {
            switch ($app) {
                case "PAS1":
                    $balance_records = BonusBalancePas1::where("orderwebs_id", $orderwebs_id)->first();
                    break;
                case "PAS2":
                    $balance_records = BonusBalancePas2::where("orderwebs_id", $orderwebs_id)->first();
                    break;
                default:
                    $balance_records = BonusBalancePas4::where("orderwebs_id", $orderwebs_id)->first();
            }

//            $balance_records = BonusBalance::where("orderwebs_id", $orderwebs_id)->first();
            $bonusType = BonusTypes::where("id", 5)->first();

            $order = Orderweb::where("id", $orderwebs_id)->first();

//            $balance_records_new = new BonusBalance();
            switch ($app) {
                case "PAS1":
                    $balance_records_new = new BonusBalancePas1();
                    break;
                case "PAS2":
                    $balance_records_new = new BonusBalancePas2();
                    break;
                default:
                    $balance_records_new = new BonusBalancePas4();
            }
            $balance_records_new->orderwebs_id = $orderwebs_id;
            $balance_records_new->users_id = $balance_records->users_id;
            $balance_records_new->bonusBloke = (-1) * $order->web_cost * $bonusType->size;
            $balance_records_new->bonus_types_id = 4;

            $balance_records_new->save();
            Log::debug("blockBonusReturnCancel balance_records_new", $balance_records_new->toArray());

            self::userBalanceApp($balance_records->users_id, $app);
        } else {
            Log::debug("blockBonusReturnCancel balance_records_2", $balance_records_2->toArray());
        }
    }

//    public function balanceReview($users_id)
//    {
//        /**
//        * Начисление за 1 вход в день в приложение
//        */
//        $latestBalanceRecord = BonusBalance::where("users_id", $users_id)
//            ->where("bonus_types_id", 3)
//            ->latest("updated_at")
//            ->first();
//
//
//        if ($latestBalanceRecord) {
//            $daysAgo = now()->subDay()->startOfDay(); // Получить текущую дату и вычесть один день, затем обнулить время.
//
//            if ($latestBalanceRecord->updated_at->startOfDay() <= $daysAgo) {
//                // Если дата обновления, обнуленная по времени, меньше или равна предыдущей дате, обнуленной по времени, на один день назад, выполните код.
//                self::recordsAdd(0, $users_id, 3, 1);
//            }
//        } else {
//            // Если записей не найдено, вы можете выполнить соответствующие действия.
//            self::recordsAdd(0, $users_id, 3, 1);
//        }
//        /**
//         * Начисление бонусов за первую поездку
//         */
//        $orderBalanceRecord2 = BonusBalance::where("users_id", $users_id)
//            ->where("bonus_types_id", 2)
//            ->first();
//
//        $orderBalanceRecord7 = BonusBalance::where("users_id", $users_id)
//            ->where("bonus_types_id", 7)
//            ->first();
//
//        if ($orderBalanceRecord2 != null && $orderBalanceRecord7 == null) {
//             self::recordsAdd(0, $users_id, 7, 1);
//        }
//    }
    public function balanceReviewApp($users_id, $app)
    {
        Log::debug("balanceReviewApp $users_id, $app");
        switch ($app) {
            case "PAS1":
                $latestBalanceRecord = BonusBalancePas1::where("users_id", $users_id)
                    ->where("bonus_types_id", 3)
                    ->latest("updated_at")
                    ->first();
                $orderBalanceRecord2 = BonusBalancePas1::where("users_id", $users_id)
                    ->where("bonus_types_id", 2)
                    ->first();

                $orderBalanceRecord7 = BonusBalancePas1::where("users_id", $users_id)
                    ->where("bonus_types_id", 7)
                    ->first();
                break;
            case "PAS2":
                $latestBalanceRecord = BonusBalancePas2::where("users_id", $users_id)
                    ->where("bonus_types_id", 3)
                    ->latest("updated_at")
                    ->first();
                $orderBalanceRecord2 = BonusBalancePas2::where("users_id", $users_id)
                    ->where("bonus_types_id", 2)
                    ->first();

                $orderBalanceRecord7 = BonusBalancePas2::where("users_id", $users_id)
                    ->where("bonus_types_id", 7)
                    ->first();
                break;
            default:
                $latestBalanceRecord = BonusBalancePas4::where("users_id", $users_id)
                    ->where("bonus_types_id", 3)
                    ->latest("updated_at")
                    ->first();
                $orderBalanceRecord2 = BonusBalancePas4::where("users_id", $users_id)
                    ->where("bonus_types_id", 2)
                    ->first();

                $orderBalanceRecord7 = BonusBalancePas4::where("users_id", $users_id)
                    ->where("bonus_types_id", 7)
                    ->first();
        }



        /**
        * Начисление за 1 вход в день в приложение
        */



        if ($latestBalanceRecord) {
            $daysAgo = now()->subDay()->startOfDay(); // Получить текущую дату и вычесть один день, затем обнулить время.

            if ($latestBalanceRecord->updated_at->startOfDay() <= $daysAgo) {
                // Если дата обновления, обнуленная по времени, меньше или равна предыдущей дате, обнуленной по времени, на один день назад, выполните код.
                self::recordsAddApp(0, $users_id, 3, 1, $app);
            }
        } else {
            // Если записей не найдено, вы можете выполнить соответствующие действия.
            self::recordsAddApp(0, $users_id, 3, 1, $app);
        }
        /**
         * Начисление бонусов за первую поездку
         */


        if ($orderBalanceRecord2 != null && $orderBalanceRecord7 == null) {
             self::recordsAddApp(0, $users_id, 7, 1, $app);
        }
    }

    /**
     * Разблокировка бонусов
     * @param $bonusOrder
     * @param $doubleOrder
     * @param $bonusOrderHold
     * @return int
     */
    public function bonusUnBlockedUid($bonusOrder, $doubleOrder, $bonusOrderHold): int
    {
        Log::info("bonusUnBlockedUid");
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
            $order_car_info_bonusOrder = $bonusOrder_response["order_car_info"];
            Log::debug("closeReason_bonusOrder: $closeReason_bonusOrder");
            Log::debug("order_cost_bonusOrder: $order_cost_bonusOrder");
        } else {
            $closeReason_bonusOrder = -1;
            $order_cost_bonusOrder = $amount;
            $order_car_info_bonusOrder = null;
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
            $order_car_info_doubleOrder = $doubleOrder_response["order_car_info"];
            Log::debug("closeReason_doubleOrder: $closeReason_doubleOrder");
            Log::debug("order_cost_doubleOrder : $order_cost_doubleOrder");
        } else {
            $closeReason_doubleOrder = -1;
            $order_cost_doubleOrder = $amount;
            $order_car_info_doubleOrder = null;
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
            $order_car_info_bonusOrderHold = $bonusOrderHold_response["order_car_info"];
            Log::debug("closeReason_bonusOrderHold: $closeReason_bonusOrderHold");
            Log::debug("order_cost_bonusOrderHold : $order_cost_bonusOrderHold");
        } else {
            $closeReason_bonusOrderHold = -1;
            $order_cost_bonusOrderHold = $amount;
            $order_car_info_bonusOrderHold = null;
            WfpController::messageAboutCloseReasonUIDStatusFirstWfp($bonusOrderHold, $bonusOrderHold);
        }

        $hold_bonusOrder = false;
        switch ($closeReason_bonusOrder) {
            case "0":
            case "8":
                $hold_bonusOrder = true;
                $amount_settle = $order_cost_bonusOrder;
                $order->auto = $order_car_info_bonusOrder;
                break;
        }
        $hold_doubleOrder = false;
        switch ($closeReason_doubleOrder) {
            case "0":
            case "8":
                $hold_doubleOrder = true;
                $amount_settle = $order_cost_bonusOrderHold;
                $order->auto = $order_car_info_doubleOrder;
                break;
        }
        $hold_bonusOrderHold = false;
        switch ($closeReason_bonusOrderHold) {
            case "0":
            case "8":
                $hold_bonusOrderHold = true;
                $amount_settle = $order_cost_bonusOrderHold;
                $order->auto = $order_car_info_bonusOrderHold;
                break;
        }
        if ($amount > $amount_settle) {
            self::blockBonusReturn($order->id, $amount);
            $amount = $amount_settle;
            $order->web_cost = $amount;
            $order->save();
            self::recordsBlokeAmount($bonusOrderHold);
        } else if ($amount < $amount_settle) {
            $subject = "Оплата поездки больше холда";
            $localCreatedAt = Carbon::parse($order->created_at)->setTimezone('Europe/Kiev');

            $messageAdmin = "Заказ $bonusOrderHold. Сервер $connectAPI. Время $localCreatedAt.
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
        $app = Orderweb::where("dispatching_order_uid", $bonusOrderHold)->first()->comment;
        Log::debug("app $app");
        switch ($app) {
            case "taxi_easy_ua_pas1":
                $app = "PAS1";
                break;
            case "taxi_easy_ua_pas2":
                $app = "PAS2";
                break;
            default:
                $app = "PAS4";
        }
        if ($hold_bonusOrder || $hold_doubleOrder || $hold_bonusOrderHold) {
            Log::debug("hold_bonusOrderHold $hold_bonusOrderHold");
//            self::blockBonusToDeleteCost($order->id, $amount);
            self::blockBonusToDeleteCostApp($order->id, $amount, $app);
            $order->bonus_status = "settle";
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
                && $closeReason_doubleOrder != "-1"
                && $closeReason_bonusOrderHold != "-1") {
                self::blockBonusReturnApp($order->id, $amount, $app);
                $order->bonus_status = "refund";
                $result = 1;
                $order->closeReason = $closeReason_bonusOrderHold;
            }
        }

        $order->save();
        return $result;
    }
//    public function balanceReviewDaily()
//    {
//        Log::debug("balanceReviewDaily");
//
//        /**
//         * Начисление бонусов за выполненную поездку
//         */
//        $orderNotComplete = Orderweb::where("closeReason", "-1")->get();
//
//        if ($orderNotComplete != null) {
//            $orderNotCompleteArray = $orderNotComplete->toArray();
//            foreach ($orderNotCompleteArray as $value) {
//                (new UIDController)->UIDStatusReviewAdmin($value['dispatching_order_uid']);
//            }
//        }
//
//        $orderComplete = Orderweb::
//        where(function ($query) {
//            $query->where("closeReason", 0)
//                ->orWhere("closeReason", 8);
//        })->get();
//
//        if ($orderComplete != null) {
//            $orderCompleteArray = $orderComplete->toArray();
//
//            foreach ($orderCompleteArray as $value) {
//                if (isset($value['users_id'])) {
//                    $orderBalanceRecord = BonusBalance::where("users_id", $value['id'])
//                        ->where("bonus_types_id", 2)
//                        ->get();
//                    if ($orderBalanceRecord != null) {
//                        $orderBalanceRecordArray = $orderBalanceRecord->toArray();
//
//                        foreach ($orderCompleteArray as $value2) {
//                            $verify = false;
//                            foreach ($orderBalanceRecordArray as $item) {
//                                if ($value2["id"] == $item['orderwebs_id']) {
//                                    $verify = true;
//                                    break;
//                                };
//                            }
//                            if (false == $verify) {
//                                $bonus = self::historyUID($value2["id"]);
//                                self::recordsAdd($value2["id"], $value2['users_id'], 2, $bonus);
//                            };
//                        }
//                    } else {
//                        foreach ($orderCompleteArray as $item) {
//                            $bonus = self::historyUID($item["id"]);
//                            self::recordsAdd($item["id"], $item['users_id'], 2, $bonus);
//                        }
//                    }
//                }
//            }
//        }
//        /**
//         * Разблокировка бонусов
//         */
//        self::bonusUnBlocked();
//    }

//    public function bonusUnBlocked()
//    {
//        /**
//         * Разблокировка бонусов
//         */
//        $usersBlocked = BonusBalance::all()->toArray();
//        foreach ($usersBlocked as $valueUser) {
//            $users_id =  $valueUser['users_id'];
//            $totalBonus = BonusBalance::where('users_id', $users_id)->sum('bonusBloke');
//
//            if ($totalBonus != 0) {
//                $bonusBlockedBalanceRecord = BonusBalance::where("users_id", $users_id)
//                    ->where(function ($query) {
//                        $query->where("bonus_types_id", 4)
//                            ->orWhere("bonus_types_id", 6);
//                    })
//                    ->get()->toArray();
//
//                $bonusSumByOrderwebsId = [];
//
//                $uniqueArray = [];
//
//                foreach ($bonusBlockedBalanceRecord as $item) {
//                    $orderwebsId = $item["orderwebs_id"];
//                    $bonusBloke = $item["bonusBloke"];
//
//                    // Если запись с таким orderwebs_id уже есть в результате
//                    if (isset($bonusSumByOrderwebsId[$orderwebsId])) {
//                        // Обновляем сумму bonusBloke для данного orderwebs_id
//                        $bonusSumByOrderwebsId[$orderwebsId] += $bonusBloke;
//                    } else {
//                        // Если записи с таким orderwebs_id нет, добавляем ее в результат
//                        $bonusSumByOrderwebsId[$orderwebsId] = $bonusBloke;
//                    }
//                }
//
//                // Проходим по уникальным записям и добавляем их в результат
//                foreach ($bonusBlockedBalanceRecord as $item) {
//                    $orderwebsId = $item["orderwebs_id"];
//                    if ($bonusSumByOrderwebsId[$orderwebsId] !== 0) {
//                        $uniqueArray[] = $item;
//                    }
//                }
//                //            dd($uniqueArray);
//                foreach ($uniqueArray as $value) {
//                    self::historyUIDunBlocked($value['orderwebs_id']);
//                }
//            }
//        }
//    }
//
//    public function bonusUnBlockedUser($email)
//    {
//        /**
//         * Разблокировка бонусов
//         */
//
//        $user = User::where('email', $email)->first();
//        $users_id = $user->id;
//        $totalBonus = BonusBalance::where('users_id', $users_id)->sum('bonusBloke');
//        if ($totalBonus != 0) {
//            $bonusBlockedBalanceRecord = BonusBalance::where("users_id", $users_id)
//                ->where(function ($query) {
//                    $query->where("bonus_types_id", 4)
//                        ->orWhere("bonus_types_id", 6);
//                })
//                ->get()->toArray();
//
//            $bonusSumByOrderwebsId = [];
//
//            $uniqueArray = [];
//
//            foreach ($bonusBlockedBalanceRecord as $item) {
//                $orderwebsId = $item["orderwebs_id"];
//                $bonusBloke = $item["bonusBloke"];
//
//                // Если запись с таким orderwebs_id уже есть в результате
//                if (isset($bonusSumByOrderwebsId[$orderwebsId])) {
//                    // Обновляем сумму bonusBloke для данного orderwebs_id
//                    $bonusSumByOrderwebsId[$orderwebsId] += $bonusBloke;
//                } else {
//                    // Если записи с таким orderwebs_id нет, добавляем ее в результат
//                    $bonusSumByOrderwebsId[$orderwebsId] = $bonusBloke;
//                }
//            }
//
//            // Проходим по уникальным записям и добавляем их в результат
//            foreach ($bonusBlockedBalanceRecord as $item) {
//                $orderwebsId = $item["orderwebs_id"];
//                if ($bonusSumByOrderwebsId[$orderwebsId] !== 0) {
//                    $uniqueArray[] = $item;
//                }
//            }
//            //            dd($uniqueArray);
//            foreach ($uniqueArray as $value) {
//                self::historyUIDunBlocked($value['orderwebs_id']);
//            }
//        }
//    }
//    private function closeReasonUIDStatusFirst($uid, $connectAPI, $autorization, $identificationId)
//    {
//        $url = $connectAPI . '/api/weborders/' . $uid;
//        $response = Http::withHeaders([
//            "Authorization" => $autorization,
//            "X-WO-API-APP-ID" => $identificationId,
//        ])->get($url);
//        if ($response->status() == 200) {
//            $response_arr = json_decode($response, true);
//            Log::debug('closeReasonUIDStatusFirst ' . json_encode($response_arr));
//            return $response_arr;
//        }
//        return "-1";
//    }

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

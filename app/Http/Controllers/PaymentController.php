<?php

namespace App\Http\Controllers;

use App\Models\Orderweb;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * @param $email
     * @param $uid
     * @param $value
     * @param $status
     * status : add, blocked, usePay
     */
    public function addRecord($email, $value)
    {
        $user = User::where("email", $email)->first();

        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->value = $value;
        $payment->status = "add";
        $payment->save();
    }
    public function blockedRecord($uid)
    {
        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
        $user = User::where("email", $orderweb->email)->first();

        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->uid_id = $orderweb->id;
        $payment->value = $orderweb->web_cost;
        $payment->status = "blocked";
        $payment->save();
    }
    public function updateStatus($email)
    {
        $user = User::where("email", $email)->first();

        $payments_blocked = Payment::where("user_id", $user->id)->where("status", "blocked")->get();

        foreach ($payments_blocked->toArray() as $item) {
            $orderweb = Orderweb::find($item["uid_id"]);

            $payment = Payment::where("uid_id", $orderweb->id)->first();
            switch ($orderweb->closeReason) {
                case "0":
                case "8":
                    $payment->status = "usePay";
                    $payment->save();
                    break;
                case "1":
                case "2":
                case "3":
                case "4":
                case "5":
                case "6":
                case "7":
                case "9":
                    $payment->delete();
                    break;
            }
        }
    }

    public function userBalance($email)
    {
        $user = User::where("email", $email)->first();
        $payments_all = Payment::where("user_id", $user->id)->get();
        $count["value"] = 0;
        $count["blocked"] = 0;
        foreach ($payments_all->toArray() as $item) {
            switch ($item["status"]) {
                case "add":
                    $count["value"] += $item["value"];
                    break;
                case "usePay":
                    $count["value"] -= $item["value"];
                    break;
                case "blocked":
                    $count["value"] -= $item["value"];
                    $count["blocked"] += $item["value"];
                    break;
            }
        }
        return response($count, 200)
            ->header('Content-Type', 'json');
    }
}

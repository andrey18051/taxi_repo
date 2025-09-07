<?php

namespace App\Http\Controllers;

use App\Models\Orderweb;

class CostController extends Controller
{
    public function orderweb_actual($uid)
    {
        $uid = (new MemoryOrderChangeController)->show($uid);
        return Orderweb::where("dispatching_order_uid", $uid)->first();
    }

    public function save_finish_cost($uid, $cost)
    {
        $orderweb = $this->orderweb_actual($uid);

        if ($orderweb) {
            $finish_cost = $orderweb->client_cost + $orderweb->attempt_20 + $cost;
            $orderweb->finish_cost = $finish_cost;
            $orderweb->save();
            return response()->json([
                "result" => "success",
                "message" => "save_finish_cost $uid $cost successful",
                "order_id" => $orderweb->id,
                "finish_cost" => $finish_cost,
            ]);
        }

        return response()->json([
            "result" => "error",
            "message" => "save_finish_cost unsuccessful: not found $uid"
        ], 404);
    }

    public function show_finish_cost($uid)
    {
        $orderweb = $this->orderweb_actual($uid);

        if (!$orderweb) {
            return response()->json([
                "result" => "error",
                "message" => "order not found",
                "finish_cost" => 0,
            ], 404);
        }

        $finish_cost = $orderweb->finish_cost ?? $orderweb->client_cost ?? 0;

        return response()->json([
            "result" => "success",
            "finish_cost" => $finish_cost,
            "order_id" => $orderweb->id,
        ]);
    }
}

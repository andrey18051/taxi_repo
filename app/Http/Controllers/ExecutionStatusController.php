<?php

namespace App\Http\Controllers;

use App\Models\ExecutionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExecutionStatusController extends Controller
{
    public function index()
    {
        $statusArray = [
            "WaitingCarSearch",
            "SearchesForCar",
            "CarFound",
            "Running",
            "Canceled",
            "Executed",
            "CostCalculation",
        ];

        DB::table('execution_statuses')->truncate();
        DB::statement('ALTER TABLE execution_statuses AUTO_INCREMENT = 1');

//        DB::table('exec_status_histories')->truncate();
//        DB::statement('ALTER TABLE exec_status_histories AUTO_INCREMENT = 1');

        $exec = new ExecutionStatus();
        $exec->bonus = "WaitingCarSearch";
        $exec->double = "WaitingCarSearch";
        $exec->save();

        return view('emulator.set_status_exec', ['statusArray' => $statusArray]);
    }

    public function bonusExec(Request $request)
    {
        $requestArray = $request->toArray();
        $exec = ExecutionStatus::find(1);
        $exec->bonus = $requestArray["bonus_status"];
        $exec->save();
    }

    public function doubleExec(Request $request)
    {
        $requestArray = $request->toArray();
        $exec = ExecutionStatus::find(1);

        $exec->double = $requestArray["double_status"];

        $exec->save();
    }

    public function updateStatusExec()
    {
        $data = DB::table('exec_status_histories')
            ->orderByDesc('id') // Упорядочить по столбцу 'id' в обратном порядке
            ->get()
            ->toArray();

        return view('emulator.update_status_exec', ['data' => $data]);
    }



    public function lastUpdateOrderTimeInSeconds($order)
    {
        $latestRecord = DB::table('exec_status_histories')
            ->where('order', $order)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestRecord) {
            $latestTime = Carbon::parse($latestRecord->created_at);
            $currentTime = Carbon::now();
            $differenceInSeconds = $latestTime->diffInSeconds($currentTime);

            return $differenceInSeconds;
        } else {
            return null;
        }
    }


}

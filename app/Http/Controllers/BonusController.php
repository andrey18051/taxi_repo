<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BonusTypes;
use App\Models\User;

class BonusController extends Controller
{
    public function index()
    {
        $BonusTypes = BonusTypes::all();
        return $BonusTypes->toArray();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'size' => 'required|integer',
        ]);

        $bonusType =  new BonusTypes();
        $bonusType->name = $request->name;
        $bonusType->size = $request->size;
        $bonusType->save();


        return redirect('/admin/bonus')->with('success', 'Запись успешно создана');
    }

    public function edit($id, $name, $size)
    {


        $BonusTypes = BonusTypes::find($id);
        $BonusTypes->name = $name;
        $BonusTypes->size = $size;
        $BonusTypes->save();
    }

    public function destroy($id)
    {
        $BonusTypes = BonusTypes::find($id);
        $BonusTypes->delete();
    }

    public function new()
    {
        return view('admin.bonus');
    }

    public function bonusUserShow($email)
    {
        $user = User::where('email', $email)->first();
        if ($user != null) {
            BonusBalanceController::userBalance($user->id);
            $user = User::where('email', $email)->first();
            $response = [
                'bonus' => $user->toArray()['bonus'],
            ];
        } else {
            $response = [
                'bonus' => 0,
            ];
        }
        return $response;
    }

    public function bonusAdd($email, $bonusTypeId, $bonus)
    {
        $user = User::where('email', $email)->first();
        $bonusTypes = BonusTypes::find($bonusTypeId);
        $bonus *= $bonusTypes->size;
        $user->bonus += $bonus;
        $user->save();

        return [
            'bonus' => $bonus,
        ];
    }

    public function bonusDel($email, $bonusTypeId, $bonus)
    {
        $user = User::where('email', $email)->first();
        $bonusTypes = BonusTypes::find($bonusTypeId);
        $bonus *= $bonusTypes->size;
        $user->bonus -= $bonus;
        if ($user->bonus <= 0) {
            $user->bonus = 0;
        }
        $user->save();
    }
}

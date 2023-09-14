<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BonusTypes;

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
}

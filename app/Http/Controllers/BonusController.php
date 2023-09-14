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

    public function store($name, $size)
    {
        $data = [
            'name' => $name,
            'size' => $size,
        ];

        BonusTypes::create($data);
    }

    public function edit(Request $id, $name, $size)
    {
        $data = [
            'name' => $name,
            'size' => $size,
        ];

        $BonusTypes = BonusTypes::find($id);
        $BonusTypes->update($data);
    }

    public function destroy($id)
    {
        $BonusTypes = BonusTypes::find($id);
        $BonusTypes->delete();
    }
}

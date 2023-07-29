<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function cityAdd(Request $request)
    {
        $city = new City();
        $city->name = $request->input('name');
        $city->address = $request->input('address');
        $city->login = $request->input('login');
        $city->password = $request->input('password');
        $city->save();
    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        return response()->json(City::get());
    }


    public function edit($id, $name, $address, $login, $password)
    {
        $city = City::find($id);

        if (!$city) {
            // Обработка ошибки, если город не найден
            return response()->json(['error' => 'Город не найден'], 404);
        }

        $city->name = $name;
        $city->address = $address;
        $city->login = $login;
        $city->password = $password;
        $city->save();

        return response()->json($city);
    }

    public function destroy($id)
    {
        City::find($id)->delete();
    }

    public function cityCreat(Request $req)
    {
        $city  = new City();

        $city->name = $req->name;
        $city->address = $req->address;
        $city->login = $req->login;
        $city->password = $req->password;
        $city->save();
        return redirect()->route('city-new');
    }

    public function cityAll(): array
    {
        return City::all()->toArray();
    }
}

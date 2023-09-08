<?php

namespace App\Http\Controllers;

use App\Models\Visicom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class VisicomController extends Controller
{
    public function store($request)
    {

        if(self::showLatLng($request["lat"], $request["lng"] ) == 404) {
            // Создание новой записи в базе данных
            $visicom = new Visicom();
            $visicom->street_type = $request["street_type"];
            $visicom->street = $request["street"];
            $visicom->name = $request["name"];
            $visicom->settlement_type = $request["settlement_type"];
            $visicom->settlement = $request["settlement"];
            $visicom->lat = $request["lat"];
            $visicom->lng = $request["lng"];
            // Заполните остальные поля вашей модели Visicom
            $visicom->save();
        }

        // Ответ с созданной записью
        return 200;
    }

    public function show($settlement)
    {
        // Поиск записи по идентификатору
        $visicom = Visicom::where("settlement", $settlement)->get();

        // Проверка, найдена ли запись
        if (!$visicom) {
            return 404;
        }

        $visicomJson = json_encode($visicom, JSON_UNESCAPED_UNICODE);
        return response($visicomJson, 200)->header('Content-Type', 'application/json; charset=utf-8');   }

    public function showLatLng($lat, $lng)
    {
        // Поиск записи по полям 'lat' и 'lng'
        $visicom = Visicom::where('lat', $lat)->where('lng', $lng)->first();

        if (!$visicom) {
            return 404;
        }
        // Ответ с найденной записью
        return $visicom->toArray();

//        return 404;
    }


    public function destroy($id)
    {
        // Поиск записи по идентификатору
        $visicom = Visicom::find($id);

        // Проверка, найдена ли запись
        if (!$visicom) {
            return response()->json(['message' => 'Запись не найдена'], 404);
        }

        // Удаление записи
        $visicom->delete();

        // Ответ с сообщением об успешном удалении
        return response()->json(['message' => 'Запись успешно удалена'], 200);
    }
}

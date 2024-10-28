<?php

namespace App\Http\Controllers;

use App\Models\ComboTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ComboTestController extends Controller
{
    public function insertComboTest(): string
    {

        $username = '0936734488';
        $password = hash('SHA512', '22223344');
        $authorization = 'Basic ' . base64_encode($username . ':' . $password);
        $connectAPI = 'http://188.190.245.102:7303 ';

        /**
         * Проверка даты геоданных в АПИ
         */

        $url = $connectAPI . '/api/geodata/streets';
        $json_str = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url, [
            'versionDateGratherThan' => '', //Необязательный. Дата версии гео-данных полученных ранее. Если параметр пропущен — возвращает  последние гео-данные.
        ]);

        $json_arr = json_decode($json_str, true);
        $url_ob = $connectAPI . '/api/geodata/objects';
        $response_ob = Http::withHeaders([
            'Authorization' => $authorization,
        ])->get($url_ob);

        $json_arr_ob = json_decode($response_ob, true);

        DB::table('combo_tests')->truncate();

        foreach ($json_arr['geo_street'] as $arrStreet) { //Улицы
            $combo = new ComboTest();
            $combo->name = $arrStreet["name"];
            $combo->street = 1;
            $combo->save();

            $geo_street = $arrStreet["localizations"];
            if ($geo_street !== null) {
                foreach ($geo_street as $val) {
                    if ($val["locale"] == "UK") {
                        $combo = new ComboTest();
                        $combo->name = $val['name'];
                        $combo->street = 1;
                        $combo->save();
                    }
                }
            }
        }

        foreach ($json_arr_ob['geo_object'] as $arrObject) { // Объекты
            $combo = new ComboTest();
            $combo->name = $arrObject["name"];
            $combo->street = 0;
            $combo->save();

            $geo_object = $arrObject["localizations"];
            if ($geo_object !== null) {
                foreach ($geo_object as $val) {
                    if ($val["locale"] == "UK") {
                        $combo = new ComboTest();
                        $combo->name = $val['name'];
                        $combo->street = 0;
                        $combo->save();
                    }
                }
            }
        }
        return "База created.";
    }



    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        $response =  DB::table("combo_tests")->select('name', 'street')->get()->toArray();
        return  response($response, 200)
            ->header('Content-Type', 'json');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ComboTest  $comboTest
     * @return \Illuminate\Http\Response
     */
    public function show(ComboTest $comboTest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ComboTest  $comboTest
     * @return \Illuminate\Http\Response
     */
    public function edit(ComboTest $comboTest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ComboTest  $comboTest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ComboTest $comboTest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ComboTest  $comboTest
     * @return \Illuminate\Http\Response
     */
    public function destroy(ComboTest $comboTest)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DriverPosition;
use Illuminate\Http\Request;

class DriverPositionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function upsertDriverPosition($driverUid, $latitude, $longitude)
    {
        DriverPosition::updateOrCreate(
            ['driver_uid' => $driverUid], // Условие поиска
            ['latitude' => $latitude, 'longitude' => $longitude] // Данные для обновления или создания
        );
        $status = "upsertDriverPosition";
        // Вернуть JSON с сообщением об успехе
        return response()->json([
            'status' => $status,
            'message' => 'upsertDriverPosition successfully'
        ], 200);
    }
}

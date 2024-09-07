<?php

namespace App\Http\Controllers;

use App\Models\DriverMemoryOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DriverMemoryOrderController extends Controller
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
     * Store a newly created resource in storage.
     *
     */
    public function store(
        $uid,
        $response,
        $authorization,
        $connectAPI,
        $identificationId,
        $apiVersion
    ) {
        $orderMemory = new DriverMemoryOrder();

        $orderMemory->response = $response;
        $orderMemory->authorization = $authorization;
        $orderMemory->connectAPI = $connectAPI;
        $orderMemory->identificationId = $identificationId;
        $orderMemory->apiVersion = $apiVersion;
        $orderMemory->dispatching_order_uid = $uid;
        $orderMemory->save();
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
}

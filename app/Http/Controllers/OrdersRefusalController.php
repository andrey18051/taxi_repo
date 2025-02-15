<?php

namespace App\Http\Controllers;

use App\Models\OrdersRefusal;
use App\Models\Orderweb;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class OrdersRefusalController extends Controller
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
    public function store($driver_uid, $order_uid)
    {
        $orderRefusal = new OrdersRefusal();
        $orderRefusal->driver_uid = $driver_uid;
        $orderRefusal->order_uid = $order_uid;
        $orderRefusal->save();
    }

    /**
     * Display the specified resource.
     *
     */
    public function show($driver_uid, $order_uid): bool
    {
        return OrdersRefusal::where("driver_uid", $driver_uid)->where("order_uid", $order_uid)->exists();
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

    public function cleanOrderRefusalTable()
    {
        $orderRefusals = OrdersRefusal::all();
        foreach ($orderRefusals as $value) {
            $order = Orderweb::where("dispatching_order_uid", $value->order_uid)
                ->whereIn('closeReason', ['-1', '101', '102'])
                ->first();

            if ($order == null) {
                OrdersRefusal::where("order_uid", $value->order_uid)->delete();
            }
        }
    }

}

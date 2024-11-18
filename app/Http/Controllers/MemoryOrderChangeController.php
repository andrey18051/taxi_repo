<?php

namespace App\Http\Controllers;

use App\Models\MemoryOrderChange;
use App\Models\Orderweb;
use Illuminate\Http\Request;

class MemoryOrderChangeController extends Controller
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
     */
    public function store($order_old_uid, $order_new_uid)
    {
        $order = new MemoryOrderChange();
        $order->order_old = $order_old_uid;
        $order->order_new = $order_new_uid;
        $order->save();
    }

    /**
     * Display the specified resource.
     *
     */
    public function show(string $uid)
    {
        $exit = false;
//        $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
        $order_search = MemoryOrderChange::where("order_old", $uid)->first();
        if ($order_search == null) {
            return $uid;
        } else {
            do {
                $order_search = MemoryOrderChange::where("order_old", $uid)->first();
                $uid = $order_search->order_new;
                $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
                if ($orderweb != null) {
                    $exit = true;
                }
            } while (!$exit);
        }
        return $uid;
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

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
//                $orderweb = Orderweb::where("dispatching_order_uid", $uid)->first();
                $orderweb = MemoryOrderChange::where("order_old", $uid)->first();
                if ($orderweb == null) {
                    $exit = true;
                }
            } while (!$exit);
        }
        return $uid;
    }
    public function getChain(string $uid)
    {
        $chain = []; // Массив для хранения всей цепочки номеров
        $order_search = MemoryOrderChange::where("order_new", $uid)->first();

        if ($order_search != null) {
            do {
                $chain[] = $uid;
                $order_search = MemoryOrderChange::where("order_new", $uid)->first();
                if ($order_search !== null) {
                    $uid = $order_search->order_old; // Получаем новый номер
                    $chain[] = $uid;// Добавляем текущий номер в массив
                    // Проверяем, есть ли связь с следующим номером
                    $orderweb = MemoryOrderChange::where("order_new", $uid)->first();
                    if ($orderweb != null) {
                        $uid = $orderweb->order_old;
                    }
                } else {
                    $orderweb = null; // Завершаем цикл, если следующего номера нет
                }



            } while ($orderweb != null); // Продолжаем цикл, пока есть связь
        }

        return $chain; // Возвращаем массив номеров
    }

    public function getFilteredOrders($orders)
    {
        $uniqueOrders = [];
        foreach ($orders as $order) {
            $currentUid = $this->findLatestOrderUid($order->uid); // Используем вашу функцию поиска последнего UID
            if (!isset($uniqueOrders[$currentUid])) {
                $uniqueOrders[$currentUid] = $order;
            }
        }

        return collect($uniqueOrders);
    }

    public function findLatestOrderUid(string $uid)
    {
        $exit = false;
        do {
            $orderSearch = MemoryOrderChange::where("order_old", $uid)->first();
            if ($orderSearch === null) {
                $exit = true;
            } else {
                $uid = $orderSearch->order_new;
            }
        } while (!$exit);

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

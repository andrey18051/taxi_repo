<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(User::get());
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
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json(User::where('id', $id)->get());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id, $name, $email, $bonus, $bonus_pay, $card_pay, $black_list)
    {
        Log::info("bonus_pay  $bonus_pay");
        Log::info("card_pay  $card_pay");

        $bon_pay = 1;

        if ($bonus_pay === "false" || $bonus_pay === "0"|| $bonus_pay === "null") {
            $bon_pay = 0;
        }

        $c_pay = 1;

        if ($card_pay === "false" || $card_pay === "0" || $card_pay === "null") {
            $c_pay = 0;
        }
        $b_list = 1;

        if ($black_list === "false" || $black_list === "0" || $black_list === "null") {
            $b_list = 0;
        }
        Log::info("bon_pay  $bon_pay");
        Log::info("c_pay  $c_pay");
        $user = User::find($id);

        $user->name = $name;
        $user->email = $email;
        $user->bonus = $bonus;
        $user->bonus_pay = $bon_pay;
        $user->card_pay = $c_pay;
        $user->black_list = $b_list;
        $user->save();

        return response()->json(User::find($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
    }



    public function setForAllPermissionsTrue()
    {
        User::where('id', '>', 0) // Укажите условие, которое выберет все записи (в данном случае, все записи)
        ->update([
            'bonus_pay' => 1,
            'card_pay' => 1,
        ]);

        // Дополнительные действия или возвращение результата, если необходимо
    }


    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $user = User::find($id);

            if (!$user) {
                return response()->json(['error' => 'Пользователь не найден'], 404);
            }

            $user->delete();

            // Удаление сообщений пользователя
            UserMessage::where('user_id', $id)->delete();

            DB::commit();

            return response()->json(['message' => 'Пользователь успешно удален'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Произошла ошибка при удалении пользователя'], 500);
        }
    }
}

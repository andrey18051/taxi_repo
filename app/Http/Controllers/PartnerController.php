<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(Partner::get());
    }


    public function create()
    {
        $partner = new Partner();

        $partner->name = " ";
        do {
            $randomString = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 12); // Получаем случайные 3 буквы
            $randomEmail = $randomString . rand(100, 999) . '@';
        } while (Partner::where('email', $randomEmail)->exists());

        $partner->email = $randomEmail;
        $partner->service = " ";
        $partner->city = " ";


        $partner->phone = "+380";
        $partner->save();

        // Возвращение JSON-ответа с созданным партнером и статусом 201 (Created)

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
        return response()->json(Partner::where('id', $id)->get());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(
        $id,
        $name,
        $email,
        $service,
        $city,
        $phone
    ) {
        $partner = Partner::find($id);

        $partner->name = $name;
        $partner->email = $email;
        $partner->phone = $phone;
        $partner->service =  $service;
        $partner->city = $city;
        $partner->save();

        return response()->json(Partner::find($id));
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
        try {
            DB::beginTransaction();

            $partner = Partner::find($id);

            if (!$partner) {
                return response()->json(['error' => 'Пользователь не найден'], 404);
            }

            $partner->delete();
//
//            // Удаление сообщений пользователя
//            UserMessage::where('user_id', $id)->delete();

            DB::commit();

            return response()->json(['message' => 'Пользователь успешно удален'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Произошла ошибка при удалении пользователя'], 500);
        }
    }
}

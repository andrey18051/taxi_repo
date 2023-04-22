<?php

namespace App\Http\Controllers;

use App\Models\Services;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    public function servicesAdd(string $name, string $email)
    {
        $service = new Services();

        $service->name = $name;
        $service->email = $email;

        $service->save();
    }

    public function index()
    {
        return response()->json(Services::all());
    }

    public function edit($id, $name, $email, $telegram_id, $viber_id)
    {
        $user = Services::find($id);

        $user->name = $name;
        $user->email = $email;
        $user->telegram_id = $telegram_id;
        $user->viber_id = $viber_id;
        $user->save();

        return response()->json(Services::find($id));
    }
    public function destroy($id)
    {
        Services::find($id)->delete();
    }

}

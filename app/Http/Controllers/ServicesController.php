<?php

namespace App\Http\Controllers;

use App\Models\Services;
use Illuminate\Http\Request;
use yii\helpers\Json;

class ServicesController extends Controller
{
    public function servicesAdd(string $name, string $email)
    {
        $service = new Services();

        $service->name = $name;
        $service->email = $email;

        $service->save();
    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        return response()->json(Services::get());
    }

    public function edit($id, $name, $email, $telegram_id, $viber_id)
    {
        $service = Services::find($id);

        $service->name = $name;
        $service->email = $email;
        $service->telegram_id = $telegram_id;
        $service->viber_id = $viber_id;
        $service->save();

        return response()->json(Services::find($id));
    }
    public function destroy($id)
    {
        Services::find($id)->delete();
    }

    public function serviceCreat(Request $req)
    {
        $service  = new Services();

        $service->name = $req->name;
        $service->email = $req->email;
        $service->telegram_id = $req->telegram_id;
        $service->viber_id = $req->viber_id;
        $service->save();
        return redirect()->route('services-new');
    }

    public function servicesAll(): array
    {
        return Services::all()->toArray();
    }


}

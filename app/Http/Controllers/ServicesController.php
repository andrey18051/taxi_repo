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
}

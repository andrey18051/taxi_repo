<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MaxboxController extends Controller
{
    public function maxboxKeyInfo(string $appName): array
    {
        $result = ["keyMapbox" => ""];
        switch ($appName) {
            case "PAS2":
//                $result = ["keyMapbox" => config("app.keyMapbox")];
//                break;
            case "PAS1":
            case "PAS4":
                $result = ["keyMapbox" => config("app.keyMapbox")];
                break;
        }
        \Illuminate\Support\Facades\Log::debug($result);
        return $result;
    }
}

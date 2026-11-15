<?php

namespace App\Http\Controllers;

use App\Support\MantisBugReportGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class MantisBugReportController extends Controller
{
    public function store(Request $request, MantisBugReportGateway $gateway): JsonResponse
    {
        try {
            $result = $gateway->forward(
                (string) $request->header('Authorization', ''),
                (string) $request->getContent()
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json($result['body'], $result['status']);
    }
}

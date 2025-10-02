<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaxiAiController extends Controller
{
    protected $baseUrl;

    public function __construct()
    {
        // URL твоего FastAPI контейнера
        $this->baseUrl = "http://172.17.0.1:8001";
    }

    public function parseRequest(Request $request)
    {
        $text = $request->input('text');

        Log::info('[TaxiAi] Incoming text request', [
            'text' => $text,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if (!$text) {
            Log::warning('[TaxiAi] Empty text received');
            return response()->json(['error' => 'Text is required'], 400);
        }

        try {
            $response = Http::post("{$this->baseUrl}/parse", [
                'text' => $text,
            ]);

            $responseData = $response->json();

            Log::info('[TaxiAi] FastAPI response', [
                'text' => $text,
                'response' => $responseData,
            ]);

            // Преобразуем ответ под модель Kotlin
            $aiResponse = [
                'text' => $responseData['text'] ?? $text,
                'response' => [
                    'text' => $responseData['text'] ?? $text,
                    'entities_spacy' => $responseData['response']['entities_spacy'] ?? [],
                    'entities_hf' => $responseData['response']['entities_hf'] ?? [],
                ],
            ];

            return response()->json($aiResponse);
        } catch (\Exception $e) {
            Log::error('[TaxiAi] Cannot connect to Taxi AI service', [
                'text' => $text,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Cannot connect to Taxi AI service',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

}

<?php
// app/Http/Controllers/CityTariffController.php

namespace App\Http\Controllers;

use App\Models\CityTariff;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CityTariffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $tariffs = CityTariff::all();
        return response()->json([
            'success' => true,
            'data' => $tariffs
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city' => 'required|string|unique:city_tariffs',
            'base_price' => 'required|numeric|min:0',
            'base_distance' => 'required|integer|min:1',
            'price_per_km' => 'required|numeric|min:0',
            'is_test' => 'boolean'
        ]);

        $tariff = CityTariff::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Тариф успешно создан',
            'data' => $tariff
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CityTariff $cityTariff): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $cityTariff
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CityTariff $cityTariff): JsonResponse
    {
        $validated = $request->validate([
            'city' => 'sometimes|string|unique:city_tariffs,city,' . $cityTariff->id,
            'base_price' => 'sometimes|numeric|min:0',
            'base_distance' => 'sometimes|integer|min:1',
            'price_per_km' => 'sometimes|numeric|min:0',
            'is_test' => 'sometimes|boolean'
        ]);

        $cityTariff->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Тариф успешно обновлен',
            'data' => $cityTariff
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CityTariff $cityTariff): JsonResponse
    {
        $cityTariff->delete();

        return response()->json([
            'success' => true,
            'message' => 'Тариф успешно удален'
        ]);
    }

    /**
     * Расчет стоимости поездки для города
     */
    public function calculatePrice(Request $request, string $city): JsonResponse
    {
        $request->validate([
            'distance' => 'required|numeric|min:0'
        ]);

        $tariff = CityTariff::where('city', $city)->first();

        if (!$tariff) {
            return response()->json([
                'success' => false,
                'message' => 'Тариф для указанного города не найден'
            ], 404);
        }

        $price = $tariff->calculatePrice($request->distance);

        return response()->json([
            'success' => true,
            'data' => [
                'city' => $city,
                'distance' => $request->distance,
                'price' => $price,
                'tariff' => $tariff
            ]
        ]);
    }

    /**
     * Получить тариф по городу
     */
    public function getByCity(string $city): JsonResponse
    {
        $tariff = CityTariff::where('city', $city)->first();

        if (!$tariff) {
            return response()->json([
                'success' => false,
                'message' => 'Тариф для указанного города не найден'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tariff
        ]);
    }
}

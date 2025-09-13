<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DriverKarma;
use Illuminate\Support\Facades\Validator;

class DriverKarmaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $driverKarmas = DriverKarma::all();
        return response()->json([
            'status' => 'success',
            'data' => $driverKarmas
        ], 200);
    }


    /**
     * Store a newly created resource in storage using GET request.
     *
     * @param string $uidDriver
     * @param string $order_id
     * @param string $action
     */
    public function store(string $uidDriver, string $order_id, string $action)
    {
        // Формируем массив данных для валидации
        $data = [
            'uidDriver' => $uidDriver,
            'order_id' => $order_id,
            'action' => $action,
        ];

        // Валидация параметров
        $validator = Validator::make($data, [
            'uidDriver' => 'nullable|string|max:255',
            'order_id' => 'nullable|string|max:255',
            'action' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Создание новой записи
        $driverKarma = DriverKarma::create($data);

        $driverKarmaValue = $this->driverKarma($uidDriver);

        if($driverKarmaValue <= config("app.driver_block_25"))  {
            (new FCMController)->writeDocumentToBlockUserFirestore($uidDriver);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Driver karma created successfully',
            'data' => $driverKarma
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $driverKarma = DriverKarma::find($id);

        if (!$driverKarma) {
            return response()->json([
                'status' => 'error',
                'message' => 'Driver karma not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $driverKarma
        ], 200);
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
        $driverKarma = DriverKarma::find($id);

        if (!$driverKarma) {
            return response()->json([
                'status' => 'error',
                'message' => 'Driver karma not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'uidDriver' => 'nullable|string|max:255',
            'order_id' => 'nullable|string|max:255',
            'action' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $driverKarma->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Driver karma updated successfully',
            'data' => $driverKarma
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $driverKarma = DriverKarma::find($id);

        if (!$driverKarma) {
            return response()->json([
                'status' => 'error',
                'message' => 'Driver karma not found'
            ], 404);
        }

        $driverKarma->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Driver karma deleted successfully'
        ], 200);
    }

    /**
     * Count records for a given uidDriver grouped by unique action values.
     *
     * @param  string|null  $uidDriver
     * @return \Illuminate\Http\Response
     */
    public function countByAction($uidDriver = null)
    {
        // Валидация uidDriver
        $validator = Validator::make(['uidDriver' => $uidDriver], [
            'uidDriver' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Получаем ID последних 100 записей для uidDriver
        $latestRecords = DriverKarma::where('uidDriver', $uidDriver)
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->pluck('id');

        if ($latestRecords->isEmpty()) {
            $counts = [

            ];
            return response()->json([
                'status' => 'success',
                'message' => "Counted records for uidDriver $uidDriver grouped by action",
                'data' => [
                    'uidDriver' => $uidDriver,
                    'karma' => 100,
                    'counts' => $counts
                ]
            ], 200);
        }
        // Группируем записи по уникальным значениям action и подсчитываем количество
        $counts = DriverKarma::where('uidDriver', $uidDriver)
            ->whereIn('id', $latestRecords)
            ->groupBy('action')
            ->selectRaw('action, COUNT(*) as count')
            ->get()
            ->pluck('count', 'action')
            ->toArray();

        $orderUnTaking = ($counts['orderUnTaking'] ?? 0) + ($counts['orderUnTakingPersonal'] ?? 0);

        $karma = 100 - $orderUnTaking;


        return response()->json([
            'status' => 'success',
            'message' => "Counted records for uidDriver $uidDriver grouped by action",
            'data' => [
                'uidDriver' => $uidDriver,
                'karma' => $karma,   // вот здесь явно есть karma
                'counts' => $counts
            ]
        ], 200);
    }

    public function driverKarma($uidDriver)
    {
        $response = $this->countByAction($uidDriver);
        $data = $response->getData(true); // Get the response as an array
        $karma = $data['data']['karma'] ?? null;
        return $karma; // Use the karma value
    }
}

<?php

use App\Http\Controllers\KafkaController;
use Illuminate\Support\Facades\Route;

/*
 * Test Kafka
 */
Route::get('/test-kafka/{orderId}/{status}', [KafkaController::class, 'sendMessage']);
Route::get('/consume-kafka', [KafkaController::class, 'getMessages']);

/**
 * Cost
 */
Route::post('/sendCostMessage', [KafkaController::class, 'sendCostMessage']);
Route::post('/sendCostMessageMyApi', [KafkaController::class, 'sendCostMessageMyApi']);

<?php

use App\Http\Controllers\KafkaController;
use Illuminate\Support\Facades\Route;

/*
 * Test Kafka (старые)
 */
Route::get('/test-kafka/{orderId}/{status}', [KafkaController::class, 'sendTestMessage']);
Route::get('/consume-kafka', [KafkaController::class, 'getMessages']);

/**
 * Cost (старые endpoints - оставляем как есть)
 */
Route::post('/sendCostMessage', [KafkaController::class, 'sendCostMessage']);
Route::post('/sendCostMessageMyApi', [KafkaController::class, 'sendCostMessageMyApi']);

/**
 * NEW Kafka API Routes (v2) - только новые методы
 */

Route::get('/debug-consumer', [KafkaController::class, 'debugConsumer']);
Route::get('/test-direct-api', [KafkaController::class, 'testDirectKafkaApi']);

Route::get('/test-curl-direct', [KafkaController::class, 'testCurlDirect']);
Route::get('/test-guzzle-direct', [KafkaController::class, 'testGuzzleDirect']);
Route::get('/test-shell', [KafkaController::class, 'testShell']);
Route::get('/diagnose-consumer', [KafkaController::class, 'diagnoseConsumerIssue']);
Route::get('/test-network', [KafkaController::class, 'testNetwork']);

Route::get('/test', [KafkaController::class, 'testEndpoint']);

// 2. Отправка тестового сообщения
Route::get('/send-test/{orderId}/{status}', [KafkaController::class, 'sendTestMessage']);

// 3. Отправка сообщений в топики (ЭТО ИСПОЛЬЗУЕТ ANDROID!)

Route::post('/send-cost', [KafkaController::class, 'sendCostMessage']);

// 4. Управление топиками
Route::get('/topics', [KafkaController::class, 'listTopics']);
Route::get('/check-topic', [KafkaController::class, 'checkTopic']);

// 5. Чтение сообщений
Route::get('/consume', [KafkaController::class, 'getMessages']);


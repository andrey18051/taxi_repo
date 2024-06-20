<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SebastianBergmann\Diff\Exception;

class InformerController extends Controller
{
    public function sendMessageInformer($message)
    {

        $informMessage = new TelegramController();

        try {
            $informMessage->sendInformMessage($message);
        } catch (Exception $e) {
            Log::debug("sendMessageInformer Ошибка в телеграмм $message");
        }
        Log::debug("sendMessageInformer  $message");}
}

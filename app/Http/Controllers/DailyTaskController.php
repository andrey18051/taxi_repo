<?php

namespace App\Http\Controllers;

use App\Mail\Check;
use App\Mail\ServerServiceMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use SebastianBergmann\Diff\Exception;

class DailyTaskController extends Controller
{

    public function sentTaskMessage($message)
    {
        $alarmMessage = new TelegramController();

        try {
            $alarmMessage->sendAlarmMessage($message);
            $alarmMessage->sendMeMessage($message);
            Log::info("sentTaskMessage: $message");
        } catch (Exception $e) {
            $subject = 'Ошибка в телеграмм';
            $paramsCheck = [
                'subject' => $subject,
                'message' => $e,
            ];
            Log::error("sentTaskMessage: Ошибка отправки в телеграмм");
        };
       }
}

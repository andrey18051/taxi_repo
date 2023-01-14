<?php

namespace App\Http\Controllers;

use App\Helpers\Viber;
use Illuminate\Http\Request;

class ViberController extends Controller
{
    public function setWebhook(Viber $viber)
    {
        $ch = $viber->setWebhook();
        dd(json_decode($ch->body()));
    }

    public function getAccountInfo(Viber $viber)
    {
        $ch = $viber->getAccountInfo();
        dd(json_decode($ch->body()));
    }

    public function getUserDetails(Viber $viber, $user_id)
    {
       // $user_id = 'tL0zpzMcNDlklD9V5dqEKg=';
        $ch = $viber->getUserDetails($user_id);
        $viberUser = json_decode($ch->body(), true);
    //    dd($viberUser['user']['id'], $viberUser['user']['name']);
        return $viberUser['user']['id'];
    }

    public function sendMessage(Viber $viber, $user_id, $message)
    {
        $viber->sendMessage($user_id, $message);
    }

    public function sendKeyboard(Viber $viber, $user_id, $message, $keyboard)
    {
        $viber->sendKeyboard($user_id, $message, $keyboard);
    }
}

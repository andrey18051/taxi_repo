<?php


namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class Viber
{
    protected $http;
    protected $bot;
    const url = 'https://chatapi.viber.com/pa';

    public function __construct(Http $http, $bot)
    {
        $this->http = $http;
        $this->bot = $bot;
    }

    public function setWebhook()
    {
        return $this->http::withHeaders([
            'X-Viber-Auth-Token' => $this->bot,
            'Content-Type' => 'application/json',
        ])->post(self::url . '/set_webhook', [
            'url' => 'https://m.easy-order-taxi.site/webhookViber',
        ]);
    }

    public function getAccountInfo()
    {
        return $this->http::withHeaders([
            'X-Viber-Auth-Token' => $this->bot,
        ])->get(self::url . '/get_account_info');
    }

    public function getUserDetails($user_id)
    {
        return $this->http::withHeaders([
            'X-Viber-Auth-Token' => $this->bot,
        ])->post(self::url . '/get_user_details', [
            'id' => $user_id,
        ]);
    }


    public function sendMessage($user_id, $message)
    {
        return $this->http::withHeaders([
            'X-Viber-Auth-Token' => $this->bot,
        ])->post(self::url . '/send_message', [
            'receiver' => $user_id,
            'type' => 'text',
            'sender.name' => 'ViberBot',
            'text' => $message,
        ]);
    }
}

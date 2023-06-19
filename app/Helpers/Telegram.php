<?php


namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class Telegram
{
    protected $http;
    protected $bot;
    const url = 'https://api.telegram.org/bot';

    public function __construct(Http $http, $bot)
    {
        $this->http = $http;
        $this->bot = $bot;
    }

    public function sendMessage($chat_id, $message)
    {
        return $this->http::post(self::url . $this->bot . '/sendMessage', [
             'chat_id' => $chat_id,
             'text' => $message,
             'parse_mode' => 'html'
         ]);
    }

    public function sendButtons($chat_id, $message, $button)
    {
        return $this->http::post(self::url . $this->bot . '/sendMessage', [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'html',
            'reply_markup' => $button
        ]);
    }

    public function sendDocument($chat_id, $file)
    {
        return $this->http::attach('document', Storage::get('/public/' . $file), $file)
            ->post(self::url . $this->bot . '/sendDocument', [
            'chat_id' => $chat_id
        ]);
    }


    public function setWebhook()
    {
        return $this->http::post(self::url . $this->bot . '/setWebhook', [
            'url' => 'https://m.easy-order-taxi.site/webhook',
        ]);
    }

    public function getWebhook()
    {
        return $this->http::post(self::url . $this->bot . '/getWebhook');
    }
    public function getWebhookInfo()
    {
        return $this->http::post(self::url . $this->bot . '/getWebhookInfo');
    }
}

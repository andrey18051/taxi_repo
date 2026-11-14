<?php

namespace App\Services;

use App\Helpers\Telegram;
use App\Mail\MailAlertText;
use App\Mail\SmtpAuthFailure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class MailAuthAlert
{
    public static function report($message): void
    {
        if (MailPasswordStore::isSuppressed() || !SmtpAuthFailure::matches($message)) {
            return;
        }
        try {
            $locked = Redis::setnx(MailPasswordStore::KEY_OPEN, (string) time());
        } catch (\Throwable $e) {
            Log::error('Mail auth alert lock failed');

            return;
        }
        if (!$locked) {
            return;
        }
        try {
            self::sendPinned();
            Log::info('Mail auth alert pinned in Telegram');
        } catch (\Throwable $e) {
            try {
                Redis::del(MailPasswordStore::KEY_OPEN);
            } catch (\Throwable $ignored) {
            }
            Log::error('Mail auth alert was not sent');
        }
    }

    public static function clear(): void
    {
        try {
            $raw = Redis::get(MailPasswordStore::KEY_PINS);
            $pins = json_decode((string) $raw, true);
            if (is_array($pins)) {
                foreach ($pins as $chatId => $messageId) {
                    self::api('unpinChatMessage', [
                        'chat_id' => $chatId,
                        'message_id' => (int) $messageId,
                    ]);
                }
            }
            Redis::del(MailPasswordStore::KEY_PINS);
            Redis::del(MailPasswordStore::KEY_OPEN);
        } catch (\Throwable $e) {
            Log::warning('Mail auth alert was not unpinned');
        }
    }

    private static function sendPinned(): void
    {
        $text = MailAlertText::pinned((string) config('app.url'));
        $pins = [];
        foreach (self::chatIds() as $chatId) {
            $response = self::api('sendMessage', [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
            $messageId = (int) data_get($response, 'result.message_id');
            if ($messageId === 0) {
                throw new \RuntimeException('Telegram sendMessage failed');
            }
            self::api('pinChatMessage', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'disable_notification' => false,
            ]);
            $pins[(string) $chatId] = $messageId;
        }
        Redis::set(MailPasswordStore::KEY_PINS, json_encode($pins));
    }

    /**
     * @return int[]
     */
    private static function chatIds(): array
    {
        $ids = [(int) config('app.chat_id_alarm')];
        foreach ((array) config('app.mail_password_admin_ids') as $adminId) {
            $ids[] = (int) $adminId;
        }
        $ids = array_values(array_unique(array_filter($ids)));

        return $ids;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    private static function api($method, array $payload)
    {
        $bot = (string) config('bots.bot');
        if ($bot === '') {
            throw new \RuntimeException('Telegram bot token is empty');
        }
        $response = Http::timeout(20)->post(Telegram::url.$bot.'/'.$method, $payload);
        if (!$response->successful()) {
            if ($method === 'pinChatMessage' || $method === 'unpinChatMessage') {
                Log::warning('Telegram '.$method.' failed');

                return null;
            }
            throw new \RuntimeException('Telegram '.$method.' failed');
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }
}

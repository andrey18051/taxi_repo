<?php

namespace App\Services;

use App\Helpers\Telegram;
use App\Mail\MailAlertText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class MailPasswordTelegram
{
    public static function tryHandle(Request $request): bool
    {
        $text = (string) $request->input('message.text', '');
        if (!MailAlertText::isCommand($text)) {
            return false;
        }
        $fromId = (int) $request->input('message.from.id');
        $chatId = (int) $request->input('message.chat.id');
        $messageId = (int) $request->input('message.message_id');
        $admins = array_map('intval', (array) config('app.mail_password_admin_ids'));
        if (!in_array($fromId, $admins, true)) {
            return true;
        }

        $password = MailAlertText::commandPassword($text);
        if ($password === null) {
            self::reply($chatId, 'Нужен пароль приложения: 16 букв. Пробелы можно. Команда: /mailpass пароль');

            return true;
        }
        if (!MailPasswordStore::probe($password)) {
            self::reply($chatId, 'Gmail не принял этот пароль. Создайте новый пароль приложения и пришлите его той же командой.');
            self::tryDelete($chatId, $messageId);

            return true;
        }

        MailPasswordStore::persist($password);
        MailAuthAlert::clear();
        $peer = self::pushPeer($password);
        self::tryDelete($chatId, $messageId);
        self::reply($chatId, self::resultText($peer));

        return true;
    }

    private static function resultText($peer): string
    {
        if ($peer === 'ok') {
            return 'Пароль почты обновлён на боевом и тестовом сервере. Письма снова должны уходить.';
        }
        if ($peer === 'skipped') {
            return 'Пароль почты обновлён на этом сервере. Второй сервер не подключён.';
        }

        return 'Пароль почты обновлён на этом сервере. Второй сервер не ответил — пришлите команду ещё раз.';
    }

    private static function pushPeer($password): string
    {
        $url = MailAlertText::peerUrl((string) config('app.url'));
        $token = (string) config('app.mail_admin_token');
        if ($url === '' || $token === '') {
            return 'skipped';
        }
        try {
            $response = Http::timeout(40)
                ->withHeaders(['X-Mail-Admin-Token' => $token])
                ->post($url.'/internal/mail-password', ['password' => $password]);

            return $response->successful() ? 'ok' : 'failed';
        } catch (\Throwable $e) {
            Log::error('Mail password peer update failed');

            return 'failed';
        }
    }

    private static function reply($chatId, $text): void
    {
        self::api('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }

    private static function tryDelete($chatId, $messageId): void
    {
        if ($messageId <= 0) {
            return;
        }
        self::api('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function api($method, array $payload): void
    {
        $bot = (string) config('bots.bot');
        if ($bot === '') {
            return;
        }
        try {
            Http::timeout(20)->post(Telegram::url.$bot.'/'.$method, $payload);
        } catch (\Throwable $e) {
            Log::warning('Telegram '.$method.' failed');
        }
    }
}

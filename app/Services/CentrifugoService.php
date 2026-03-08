<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CentrifugoService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $secret;
    protected bool $verifySsl;
    protected int $timeout;

    public function __construct()
    {
        $this->apiUrl = config('centrifugo.api_url');
        $this->apiKey = config('centrifugo.api_key');
        $this->secret = config('centrifugo.secret');
        $this->verifySsl = config('centrifugo.verify_ssl');
        $this->timeout = config('centrifugo.timeout');
    }

    /**
     * Отправить сообщение в канал
     */
    public function publish(string $channel, array $data, bool $skipHistory = false): array
    {
        return $this->request('publish', [
            'channel' => $channel,
            'data' => $data,
            'skip_history' => $skipHistory,
        ]);
    }

    /**
     * Отправить сообщение в несколько каналов
     */
    public function broadcast(array $channels, array $data, bool $skipHistory = false): array
    {
        $results = [];
        foreach ($channels as $channel) {
            $results[$channel] = $this->publish($channel, $data, $skipHistory);
        }
        return $results;
    }

    /**
     * Получить информацию о канале (кто подписан)
     */
    public function presence(string $channel): array
    {
        return $this->request('presence', [
            'channel' => $channel,
        ]);
    }

    /**
     * Получить количество подписчиков в канале
     */
    public function presenceStats(string $channel): array
    {
        return $this->request('presence_stats', [
            'channel' => $channel,
        ]);
    }

    /**
     * Получить историю сообщений канала
     */
    public function history(string $channel, int $limit = 10): array
    {
        return $this->request('history', [
            'channel' => $channel,
            'limit' => $limit,
        ]);
    }

    /**
     * Удалить историю канала
     */
    public function historyRemove(string $channel): array
    {
        return $this->request('history_remove', [
            'channel' => $channel,
        ]);
    }

    /**
     * Отозвать токены пользователя
     */
    public function revokeUserToken(string $userId, int $expireAt = null): array
    {
        $params = ['user' => $userId];

        if ($expireAt) {
            $params['expire_at'] = $expireAt;
        }

        return $this->request('revoke_user_token', $params);
    }

    /**
     * Отозвать все токены (принудительно)
     */
    public function revokeAllTokens(): array
    {
        return $this->request('revoke_token', [
            'all' => true,
        ]);
    }

    /**
     * Отправить команду в Centrifugo
     */
    protected function request(string $method, array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'apikey ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->withoutVerifying() // если verify_ssl = false
                ->post($this->apiUrl . '/' . $method, $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            Log::error('Centrifugo API error', [
                'method' => $method,
                'params' => $params,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'API error: ' . $response->status(),
                'details' => $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Centrifugo connection error', [
                'method' => $method,
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Сгенерировать JWT токен для клиента
     */
    public function generateClientToken(string $userId, array $info = [], int $expireIn = 86400): string
    {
        $payload = [
            'sub' => $userId,
            'exp' => time() + $expireIn,
        ];

        if (!empty($info)) {
            $payload['info'] = $info;
        }

        return $this->generateJwt($payload);
    }

    /**
     * Сгенерировать JWT токен для подписки на канал
     */
    public function generateSubscriptionToken(string $userId, string $channel, array $info = [], int $expireIn = 86400): string
    {
        $payload = [
            'sub' => $userId,
            'channel' => $channel,
            'exp' => time() + $expireIn,
        ];

        if (!empty($info)) {
            $payload['info'] = $info;
        }

        return $this->generateJwt($payload);
    }

    /**
     * Генерация JWT токена
     */
    protected function generateJwt(array $payload): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
}

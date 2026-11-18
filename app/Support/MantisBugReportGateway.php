<?php

namespace App\Support;

use App\Http\Controllers\TelegramController;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Принимает баг-репорт от PAS и создаёт заявку в Mantis на этом же хосте.
 * Токен и тело не пишутся в лог.
 */
class MantisBugReportGateway
{
    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    public function forward(string $authorization, string $rawBody): array
    {
        $token = trim($authorization);
        if ($token === '') {
            return ['status' => 401, 'body' => ['message' => 'Missing Authorization']];
        }
        if (trim($rawBody) === '') {
            return ['status' => 400, 'body' => ['message' => 'Empty body']];
        }

        $apiUrl = (string) config('services.mantis.api_url', 'http://127.0.0.1:8989/api/rest/issues');
        $this->assertLocalApi($apiUrl);

        try {
            $response = Http::withOptions(['allow_redirects' => false])
                ->withHeaders([
                    'Authorization' => $token,
                    'Accept' => 'application/json',
                ])
                ->withBody($rawBody, 'application/json')
                ->timeout(60)
                ->post($apiUrl);
        } catch (ConnectionException $e) {
            Log::warning('mantis bug report: local Mantis unreachable');
            return ['status' => 502, 'body' => ['message' => 'Mantis unavailable']];
        }

        $decoded = $response->json();
        if (!is_array($decoded)) {
            $decoded = ['message' => 'Mantis response was not JSON'];
        }

        $issueId = $decoded['issue']['id'] ?? null;
        if ($response->successful() && is_numeric($issueId)) {
            $public = (string) config('services.mantis.url', 'https://t.easy-order-taxi.site/mantis/');
            $decoded['view_url'] = self::viewUrl((int) $issueId, $public);
            $this->notifyCrashScreen($rawBody, (int) $issueId, $decoded['view_url']);
        }

        Log::info('mantis bug report forwarded', [
            'status' => $response->status(),
            'issue_id' => is_numeric($issueId) ? (int) $issueId : null,
        ]);

        return ['status' => $response->status(), 'body' => $decoded];
    }

    public static function viewUrl(int $issueId, string $publicBase): string
    {
        return rtrim(trim($publicBase), '/') . '/view.php?id=' . $issueId;
    }

    private function notifyCrashScreen(string $rawBody, int $issueId, string $viewUrl): void
    {
        $message = CrashScreenBugReportNotice::messageFromBody($rawBody, $issueId, $viewUrl);
        if ($message === null) {
            return;
        }

        try {
            $telegram = new TelegramController();
            $telegram->sendAlarmMessage($message);
            $telegram->sendMeMessage($message);
        } catch (\Throwable $e) {
            Log::warning('mantis crash-screen telegram skipped', [
                'issue_id' => $issueId,
            ]);
        }
    }

    private function assertLocalApi(string $url): void
    {
        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';
        if ($scheme !== 'http' || !in_array($host, ['127.0.0.1', 'localhost'], true)) {
            throw new InvalidArgumentException('Mantis API URL must stay on this host');
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\AndroidInstallation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class SendLoginReminderDue extends Command
{
    protected $signature = 'login-reminder:send-due';
    protected $description = 'Send login reminder pushes for anonymous installations';

    public function handle()
    {
        $now = Carbon::now('UTC');

        $due = AndroidInstallation::query()
            ->whereNotNull('reminder_due_at')
            ->where('reminder_due_at', '<=', $now)
            ->whereNull('reminder_sent_at')
            ->whereNull('reminder_cancelled_at')
            ->where('reminder_opt_out', false)
            ->whereNotNull('fcm_token')
            ->limit(500)
            ->get();

        if ($due->isEmpty()) {
            return 0;
        }

        foreach ($due as $installation) {
            $app = $installation->app;
            $token = $installation->fcm_token;

            $firebaseMessaging = $this->getFirebaseMessagingForApp($app);
            if ($firebaseMessaging === null) {
                Log::error('Login reminder: firebaseMessaging not configured', ['app' => $app]);
                continue;
            }

            $locale = $installation->locale ?: 'uk';
            $body = $this->buildBody($locale, $app);
            $title = $this->buildTitle($locale, $app);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData([
                    'type' => 'login_reminder',
                    'target_app' => $app,
                    'message_uk' => $this->buildBody('uk', $app),
                    'message_ru' => $this->buildBody('ru', $app),
                    'message_en' => $this->buildBody('en', $app),
                ]);

            try {
                $firebaseMessaging->send($message);
                $installation->reminder_sent_at = $now;
                $installation->save();
            } catch (NotFound $e) {
                // токен умер — помечаем отправленным, чтобы не циклило
                Log::warning('Login reminder: token not found', [
                    'installation_id' => $installation->installation_id,
                    'app' => $app,
                ]);
                $installation->reminder_sent_at = $now;
                $installation->save();
            } catch (\Throwable $e) {
                Log::error('Login reminder: send failed', [
                    'installation_id' => $installation->installation_id,
                    'app' => $app,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return 0;
    }

    private function getFirebaseMessagingForApp(string $app)
    {
        if ($app === 'PAS1') {
            return app('firebase.messaging')['app1'] ?? null;
        }
        if ($app === 'PAS2') {
            return app('firebase.messaging')['app2'] ?? null;
        }
        if ($app === 'PAS4') {
            return app('firebase.messaging')['app4'] ?? null;
        }
        return app('firebase.messaging')['app5'] ?? null;
    }

    private function buildTitle(string $locale, string $app): string
    {
        $names = config('fcm_app_names')[$app] ?? null;
        $appName = is_array($names) ? ($names[$locale] ?? $names['uk'] ?? $app) : $app;

        if ($locale === 'ru') {
            return $appName . ': напоминание';
        }
        if ($locale === 'en') {
            return $appName . ': reminder';
        }
        return $appName . ': нагадування';
    }

    private function buildBody(string $locale, string $app): string
    {
        if ($locale === 'ru') {
            return 'Вы установили приложение, но не вошли в аккаунт. Откройте приложение и войдите, чтобы получать заказы и уведомления.';
        }
        if ($locale === 'en') {
            return 'You installed the app but haven’t signed in. Open the app and sign in to receive orders and notifications.';
        }
        return 'Ви встановили додаток, але не увійшли в акаунт. Відкрийте додаток і увійдіть, щоб отримувати замовлення та сповіщення.';
    }
}


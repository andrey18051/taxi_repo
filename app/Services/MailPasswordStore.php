<?php

namespace App\Services;

use App\Mail\MailAppPassword;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

final class MailPasswordStore
{
    const KEY_PASSWORD = 'mail:smtp_password';
    const KEY_OPEN = 'mail:auth_alert_open';
    const KEY_PINS = 'mail:auth_alert_pins';

    /** @var bool */
    private static $suppressAlert = false;

    public static function isSuppressed(): bool
    {
        return self::$suppressAlert;
    }

    public static function applyOverride(): void
    {
        try {
            $password = Redis::get(self::KEY_PASSWORD);
        } catch (\Throwable $e) {
            return;
        }
        if (!is_string($password) || MailAppPassword::normalize($password) === null) {
            return;
        }
        config(['mail.mailers.smtp.password' => $password]);
    }

    public static function probe($password): bool
    {
        $password = (string) $password;
        $host = (string) config('mail.mailers.smtp.host');
        $username = (string) config('mail.mailers.smtp.username');
        if ($host === '' || $username === '' || $password === '') {
            return false;
        }

        $encryption = config('mail.mailers.smtp.encryption');
        $encryption = is_string($encryption) && $encryption !== '' ? $encryption : null;

        self::$suppressAlert = true;
        $transport = null;
        try {
            $transport = new \Swift_SmtpTransport(
                $host,
                (int) config('mail.mailers.smtp.port'),
                $encryption
            );
            $transport->setUsername($username);
            $transport->setPassword($password);
            $transport->setTimeout(20);
            $transport->start();

            return true;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            if (strpos($error, $password) !== false) {
                $error = 'auth failed';
            }
            Log::warning('Gmail rejected the mail app password: '.$error);

            return false;
        } finally {
            self::$suppressAlert = false;
            if ($transport !== null) {
                try {
                    $transport->stop();
                } catch (\Throwable $e) {
                    // Соединение уже закрыто.
                }
            }
        }
    }

    public static function persist($password): void
    {
        $password = (string) $password;
        Redis::set(self::KEY_PASSWORD, $password);
        config(['mail.mailers.smtp.password' => $password]);
        foreach (['mail.manager', 'mailer', 'swift.mailer'] as $abstract) {
            if (app()->bound($abstract)) {
                app()->forgetInstance($abstract);
            }
        }
        self::writeEnv($password);
        try {
            Artisan::call('queue:restart');
        } catch (\Throwable $e) {
            Log::warning('Queue restart after mail password update failed');
        }
        Log::info('Mail app password updated');
    }

    private static function writeEnv($password): void
    {
        $path = base_path('.env');
        if (!is_file($path) || !is_writable($path)) {
            Log::warning('MAIL_PASSWORD file was not updated');

            return;
        }
        $env = (string) file_get_contents($path);
        $line = 'MAIL_PASSWORD="'.$password.'"';
        if (preg_match('/^MAIL_PASSWORD=.*$/m', $env) === 1) {
            $env = (string) preg_replace('/^MAIL_PASSWORD=.*$/m', $line, $env, 1);
        } else {
            $env = rtrim($env)."\n".$line."\n";
        }
        file_put_contents($path, $env, LOCK_EX);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AndroidInstallation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AndroidInstallationController extends Controller
{
    private const DEFAULT_TZ = 'Europe/Kyiv';
    private const KYIV_TZ_FALLBACK = 'Europe/Kiev';

    public function register(string $installationId, string $app, string $token, string $local, string $tz)
    {
        $tz = $tz ?: self::DEFAULT_TZ;
        $local = $local ?: null;

        $installation = AndroidInstallation::firstOrNew([
            'installation_id' => $installationId,
            'app' => $app,
        ]);

        $installation->fcm_token = $token;
        $installation->locale = $local;
        $installation->timezone = $tz;
        $installation->first_open_at = $installation->first_open_at ?: now();
        $installation->save();

        Log::info('Android installation registered', [
            'installation_id' => $installationId,
            'app' => $app,
            'has_token' => !empty($token),
            'locale' => $local,
            'timezone' => $tz,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Планируем одно напоминание на завтра 07:00 по Europe/Kyiv.
     * Если уже отправлено/отменено/opt-out — не планируем снова.
     */
    public function scheduleLoginReminder(string $installationId, string $app, string $local, string $tz)
    {
        $tz = $tz ?: self::DEFAULT_TZ;
        $local = $local ?: null;

        $installation = AndroidInstallation::firstOrNew([
            'installation_id' => $installationId,
            'app' => $app,
        ]);

        $installation->locale = $installation->locale ?: $local;
        $installation->timezone = $installation->timezone ?: $tz;
        $installation->first_open_at = $installation->first_open_at ?: now();

        if ($installation->reminder_opt_out) {
            return response()->json(['ok' => true, 'skipped' => 'opt_out']);
        }
        if ($installation->reminder_sent_at) {
            return response()->json(['ok' => true, 'skipped' => 'already_sent']);
        }
        if ($installation->reminder_cancelled_at) {
            return response()->json(['ok' => true, 'skipped' => 'cancelled']);
        }
        if ($installation->reminder_due_at) {
            return response()->json(['ok' => true, 'skipped' => 'already_scheduled', 'due_at' => $installation->reminder_due_at]);
        }

        $dueAtUtc = self::computeReminderDueAtUtc();
        $installation->reminder_due_at = $dueAtUtc;
        $installation->save();

        Log::info('Login reminder scheduled', [
            'installation_id' => $installationId,
            'app' => $app,
            'due_at_utc' => (string) $dueAtUtc,
            'timezone' => $tz,
        ]);

        return response()->json(['ok' => true, 'due_at' => $dueAtUtc]);
    }

    public function cancelLoginReminder(string $installationId, string $app)
    {
        $installation = AndroidInstallation::where('installation_id', $installationId)
            ->where('app', $app)
            ->first();

        if (!$installation) {
            return response()->json(['ok' => true, 'skipped' => 'not_found']);
        }

        $installation->reminder_cancelled_at = now();
        $installation->reminder_due_at = null;
        $installation->save();

        Log::info('Login reminder cancelled', [
            'installation_id' => $installationId,
            'app' => $app,
        ]);

        return response()->json(['ok' => true]);
    }

    public static function computeReminderDueAtUtc(?Carbon $nowKyiv = null): Carbon
    {
        $kyiv = $nowKyiv ?: Carbon::now(self::kyivTz());
        $tomorrowAtSevenKyiv = $kyiv->copy()->addDay()->setTime(7, 0, 0);
        return $tomorrowAtSevenKyiv->clone()->setTimezone('UTC');
    }

    private static function kyivTz(): string
    {
        try {
            new \DateTimeZone(self::DEFAULT_TZ);
            return self::DEFAULT_TZ;
        } catch (\Throwable $e) {
            return self::KYIV_TZ_FALLBACK;
        }
    }
}


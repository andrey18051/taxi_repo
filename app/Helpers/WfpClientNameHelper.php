<?php

namespace App\Helpers;

use App\Models\User;

class WfpClientNameHelper
{
    /**
     * WayForPay clientFirstName/clientLastName: phone from users table (not placeholder).
     *
     * @return array{clientFirstName: string, clientLastName: string}
     */
    public static function resolve(?string $clientEmail, ?string $clientPhone): array
    {
        return self::buildNameFields(self::resolveUserPhone($clientEmail, $clientPhone));
    }

    /**
     * @return array{clientFirstName: string, clientLastName: string}
     */
    public static function buildNameFields(string $phone): array
    {
        return [
            'clientFirstName' => $phone,
            'clientLastName' => ' ',
        ];
    }

    public static function pickDisplayPhone(?User $user, ?string $clientPhone): string
    {
        if ($user !== null && !empty($user->user_phone)) {
            return (string) $user->user_phone;
        }

        if ($clientPhone !== null && $clientPhone !== '') {
            return (string) $clientPhone;
        }

        return 'Unknown';
    }

    public static function resolveUserPhone(?string $clientEmail, ?string $clientPhone): string
    {
        $user = self::findUser($clientEmail, $clientPhone);

        return self::pickDisplayPhone($user, $clientPhone);
    }

    private static function findUser(?string $clientEmail, ?string $clientPhone): ?User
    {
        if ($clientEmail !== null && $clientEmail !== '') {
            $user = User::where('email', $clientEmail)->first();
            if ($user !== null) {
                return $user;
            }
        }

        if ($clientPhone !== null && $clientPhone !== '') {
            return User::where('user_phone', $clientPhone)->first();
        }

        return null;
    }
}

<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array publish(string $channel, array $data, bool $skipHistory = false)
 * @method static array broadcast(array $channels, array $data, bool $skipHistory = false)
 * @method static array presence(string $channel)
 * @method static array presenceStats(string $channel)
 * @method static array history(string $channel, int $limit = 10)
 * @method static array historyRemove(string $channel)
 * @method static array revokeUserToken(string $userId, int $expireAt = null)
 * @method static array revokeAllTokens()
 * @method static string generateClientToken(string $userId, array $info = [], int $expireIn = 86400)
 * @method static string generateSubscriptionToken(string $userId, string $channel, array $info = [], int $expireIn = 86400)
 */
class Centrifugo extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'centrifugo';
    }
}

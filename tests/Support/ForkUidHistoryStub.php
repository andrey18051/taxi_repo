<?php

namespace Tests\Support;

use App\Models\Uid_history;

/**
 * Uid_history без обращения к БД в тестах вилки.
 */
final class ForkUidHistoryStub extends Uid_history
{
    public function save(array $options = [])
    {
        return true;
    }
}

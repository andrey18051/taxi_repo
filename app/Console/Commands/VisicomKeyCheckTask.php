<?php

namespace App\Console\Commands;

use App\Http\Controllers\OpenStreetMapController;
use Illuminate\Console\Command;

class VisicomKeyCheckTask extends Command
{
    protected $signature = 'visicom-key:check';

    protected $description = 'Ночная проверка ключа Visicom и уведомление в Telegram об остатке срока';

    public function handle(): int
    {
        (new OpenStreetMapController())->checkVisicomRequest();

        return 0;
    }
}

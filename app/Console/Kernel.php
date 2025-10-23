<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\DailyTask::class,
        \App\Console\Commands\RestartTask::class,
        \App\Console\Commands\VersionApiTask::class,
    ];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('daily-task:run')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();
//        $schedule->command('logs:send')->dailyAt('22:00');
        $schedule->command('queue:monitor-failed')->everyFiveMinutes();

        $schedule->command('check-inactive:run')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/server-check.log'));
    }


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

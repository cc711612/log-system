<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if (!config('logsystem.schedule.enable')) {
            return;
        }

        $schedule->command('command:create_execute_schedule')
            ->everyFiveMinutes(); // Adjust the timing as needed

        $schedule->command('command:handle_execute_schedule')
            ->everyMinute()
            ->withoutOverlapping(5);

        if (config('logsystem.connection_monitor.enable')) {
            $schedule->command('command:log_connection_monitor')
                ->everyMinute(); // Adjust the timing as needed
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

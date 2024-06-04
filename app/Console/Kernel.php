<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
         $schedule->command('command:create_execute_schedule')
             ->everyFiveMinutes() // Adjust the timing as needed
             ->then(function () use ($schedule) {
                 // Schedule the second command to run after the first one completes
                 $schedule->command('command:handle_execute_schedule')
                     ->withoutOverlapping();
             });
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

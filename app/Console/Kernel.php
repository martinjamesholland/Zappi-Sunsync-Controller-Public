<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Refresh the energy data mart every fifteen minutes
        $schedule->command('app:refresh-energy-data-mart')->everyFifteenMinutes();

        // Existing data acquisition can stay on its own cadence if needed
        // $schedule->command('app:update-energy-flow-data')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}



<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    // Define the application's command schedule
    protected function schedule(Schedule $schedule): void
    {
        // Purge revoked and expired tokens daily
        $schedule->command('passport:purge')->daily();

        // Add more scheduled tasks here as needed
        // $schedule->command('inspire')->hourly();
    }

    // Register the commands for the application
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    // Get the timezone that should be used by default for scheduled events
    protected function scheduleTimezone(): string
    {
        return config('app.timezone');
    }
}


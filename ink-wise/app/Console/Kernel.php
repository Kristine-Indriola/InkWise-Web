<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\SyncProductImages;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        SyncProductImages::class,
    ];

    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
    }

    protected function commands()
    {
        // Load commands from routes/console.php if needed
        require base_path('routes/console.php');
    }
}

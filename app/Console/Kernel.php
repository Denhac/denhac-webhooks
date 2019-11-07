<?php

namespace App\Console;

use App\Console\Commands\BackupWinDSX;
use App\Console\Commands\SetUpDenhacWebhooks;
use App\Console\Commands\UpdateBaseData;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        BackupWinDSX::class,
        SetUpDenhacWebhooks::class,
        UpdateBaseData::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
//        $schedule
//            ->command(SetUpDenhacWebhooks::class)
//            ->hourly();

        $schedule
            ->command(BackupWinDSX::class, [
                storage_path("backups/".date("Y/m/d/h/i"))
            ])
            ->hourly();

        // TODO Clean up backups that are too old
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

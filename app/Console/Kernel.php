<?php

namespace App\Console;

use App\Aggregates\CardNotifierAggregate;
use App\Console\Commands\BackupWinDSXCommand;
use App\Console\Commands\MatchSlackUsers;
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
        BackupWinDSXCommand::class,
        MatchSlackUsers::class,
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
            ->command(BackupWinDSXCommand::class, [
                storage_path('backups/on_time/'.date('Y/m/d/h/i')),
            ])
            ->hourly();

        $schedule->command('denhac:backup-cleanup')->daily();

        $schedule
            ->call(function () {
                CardNotifierAggregate::make()->sendNotificationEmail()->persist();
            })
            ->weeklyOn(6, "13:00");
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

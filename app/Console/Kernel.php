<?php

namespace App\Console;

use App\Aggregates\CardNotifierAggregate;
use App\Console\Commands\IdentifyIssues;
use App\Console\Commands\MakeIssue;
use App\Console\Commands\MakeIssueChecker;
use App\Console\Commands\MatchSlackUsers;
use App\Console\Commands\SetUpDenhacWebhooks;
use App\Console\Commands\SlackProfileFieldsUpdate;
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
        IdentifyIssues::class,
        MakeIssue::class,
        MakeIssueChecker::class,
        MatchSlackUsers::class,
        SetUpDenhacWebhooks::class,
        SlackProfileFieldsUpdate::class,
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
        $schedule
            ->call(function () {
                CardNotifierAggregate::make()->sendNotificationEmail()->persist();
            })
            ->weeklyOn(6, '13:00');

//        $schedule->command('denhac:slack-profile-fields-update')
//            ->everySixHours();

        $schedule->command('passport:purge')->hourly();
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

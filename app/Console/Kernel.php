<?php

namespace App\Console;

use App\Aggregates\CardNotifierAggregate;
use App\Console\Commands\IdentifyIssues;
use App\Console\Commands\LinkQuickbooks;
use App\Console\Commands\MakeIssue;
use App\Console\Commands\MakeIssueChecker;
use App\Console\Commands\MatchSlackUsers;
use App\Console\Commands\SetUpDenhacWebhooks;
use App\Console\Commands\SlackProfileFieldsUpdate;
use App\Console\Commands\UpdateBaseData;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        IdentifyIssues::class,
        LinkQuickbooks::class,
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
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule
            ->call(function () {
                CardNotifierAggregate::make()->sendNotificationEmail()->persist();
            })
            ->weeklyOn(6, '13:00');

        $schedule->command('denhac:slack-profile-fields-update')
            ->daily();

        $schedule->command('passport:purge')->hourly();

        $schedule->call(fn() => $this->refreshQuickBooksAccessToken())->everyThirtyMinutes();
    }

    protected function refreshQuickBooksAccessToken(): void
    {
        if (is_null(setting('quickbooks.accessToken'))) {
            return;
        }
        app(OAuth2LoginHelper::class);  // This should refresh the token automatically on resolving
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

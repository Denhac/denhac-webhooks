<?php

namespace App\Console;

use App\Actions\QuickBooks\GenerateVendingNetJournalEntry;
use App\Aggregates\CardNotifierAggregate;
use App\Console\Commands\IdentifyIssues;
use App\Console\Commands\LinkQuickbooks;
use App\Console\Commands\MakeIssue;
use App\Console\Commands\MakeIssueChecker;
use App\Console\Commands\MatchSlackUsers;
use App\Console\Commands\SetUpDenhacWebhooks;
use App\Console\Commands\SlackProfileFieldsUpdate;
use App\Console\Commands\UpdateBaseData;
use App\External\QuickBooks\QuickBooksAuthSettings;
use Carbon\Carbon;
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
            ->weeklyOn(Schedule::SATURDAY, '13:00');

        $schedule->command('denhac:slack-profile-fields-update')
            ->daily();

        $schedule->command('passport:purge')->hourly();

        // QuickBooks tokens expire every hour. Every half should prevent any issues with a job running right as a token expires.
        $schedule->call(fn() => $this->refreshQuickBooksAccessToken())->everyThirtyMinutes();

        // daily at noon because te cron is in UTC but I grab Denver timezone minus one day. This makes the date string
        // for searching orders as well as the date used for the QuickBooks entry correct regardless of if it's daylight
        //savings time or not.
        $schedule->call(fn() => $this->generateVendingNetJournalEntry())->dailyAt('12:00');
    }

    protected function refreshQuickBooksAccessToken(): void
    {
        if (!QuickBooksAuthSettings::hasKnownAuth()) {
            return;
        }
        app(OAuth2LoginHelper::class);  // This should refresh the token automatically on resolving
    }

    protected function generateVendingNetJournalEntry(): void
    {
        if (!QuickBooksAuthSettings::hasKnownAuth()) {
            return;
        }
        $yesterday = Carbon::now('America/Denver')->subDay();
        app(GenerateVendingNetJournalEntry::class)
            ->onQueue()
            ->execute($yesterday);
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

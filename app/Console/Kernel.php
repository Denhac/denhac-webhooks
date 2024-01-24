<?php

namespace App\Console;

use App\Actions\QuickBooks\GenerateVendingNetJournalEntry;
use App\Actions\Stripe\SetIssuingBalanceToValue;
use App\Aggregates\CardNotifierAggregate;
use App\Console\Commands\ClearOutFailedGitHubInvites;
use App\Console\Commands\IdentifyIssues;
use App\Console\Commands\LinkQuickbooks;
use App\Console\Commands\MakeIssue;
use App\Console\Commands\MakeIssueChecker;
use App\Console\Commands\SetUpDenhacWebhooks;
use App\Console\Commands\SlackProfileFieldsUpdate;
use App\Console\Commands\UpdateBaseData;
use App\External\QuickBooks\QuickBooksAuthSettings;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        ClearOutFailedGitHubInvites::class,
        IdentifyIssues::class,
        LinkQuickbooks::class,
        MakeIssue::class,
        MakeIssueChecker::class,
        SetUpDenhacWebhooks::class,
        SlackProfileFieldsUpdate::class,
        UpdateBaseData::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule
            ->call(function () {
                CardNotifierAggregate::make()->sendNotificationEmail()->persist();
            })
            ->weeklyOn(Schedule::SATURDAY, '13:00');

        $schedule->command('denhac:slack-profile-fields-update')->daily();

        $schedule->command('passport:purge')->hourly();

        $schedule->command('denhac:clear-out-failed-git-hub-invites')->daily();

        // QuickBooks tokens expire every hour. Every half should prevent any issues with a job running right as a token expires.
        $schedule->call(fn () => $this->refreshQuickBooksAccessToken())->everyThirtyMinutes();

        // daily at noon because the cron is in UTC but I grab Denver timezone minus one day. This makes the date string
        // for searching orders as well as the date used for the QuickBooks entry correct regardless of if it's daylight
        //savings time or not.
        $schedule->call(fn () => $this->generateVendingNetJournalEntry())->dailyAt('12:00');

        $schedule->call(fn () => $this->topUpIssuingBalance())->daily();
    }

    protected function refreshQuickBooksAccessToken(): void
    {
        if (! QuickBooksAuthSettings::hasKnownAuth()) {
            return;
        }
        /** @var DataService $dataService */
        $dataService = app(DataService::class);
        /** @var OAuth2LoginHelper $OAuth2LoginHelper */
        $OAuth2LoginHelper = app(OAuth2LoginHelper::class);

        // If this throws, we don't have a token to refresh.
        // I have not found a better way to do it.
        $OAuth2LoginHelper->getAccessToken();

        $accessToken = $OAuth2LoginHelper->refreshToken();
        // TODO check $OAuth2LoginHelper->getLastError() or if refreshToken just throws for us

        $dataService->updateOAuth2Token($accessToken);

        QuickBooksAuthSettings::saveDataServiceInfo($accessToken);
    }

    protected function generateVendingNetJournalEntry(): void
    {
        if (! QuickBooksAuthSettings::hasKnownAuth()) {
            return;
        }
        $yesterday = Carbon::now('America/Denver')->subDay();
        app(GenerateVendingNetJournalEntry::class)
            ->onQueue()
            ->execute($yesterday);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    private function topUpIssuingBalance(): void
    {
        // This code should only be temporary until we have the full system built out to manage current issuing balance

        $today = Carbon::today();
        app(SetIssuingBalanceToValue::class)
            ->onQueue()
            ->execute(100000, "Top-Up to $1,000 on {$today->toFormattedDayDateString()}");
    }
}

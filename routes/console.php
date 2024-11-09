<?php

use App\Aggregates\CardNotifierAggregate;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function () {
    CardNotifierAggregate::make()->sendNotificationEmail()->persist();
})
    ->weeklyOn(Schedule::SATURDAY, '13:00');

Schedule::command('denhac:slack-profile-fields-update')->daily();

Schedule::command('passport:purge')->hourly();

Schedule::command('denhac:clear-out-failed-git-hub-invites')->daily();

// QuickBooks tokens expire every hour. Every half should prevent any issues with a job running right as a token expires.
Schedule::call(fn () => $this->refreshQuickBooksAccessToken())->everyThirtyMinutes();

// daily at noon because the cron is in UTC but I grab Denver timezone minus one day. This makes the date string
// for searching orders as well as the date used for the QuickBooks entry correct regardless of if it's daylight
//savings time or not.
Schedule::call(fn () => $this->generateVendingNetJournalEntry())->dailyAt('12:00');

Schedule::call(fn () => $this->topUpIssuingBalance())->daily();

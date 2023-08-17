<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\SlackDoorCodeCommandController;
use App\Http\Controllers\SlackEventController;
use App\Http\Controllers\SlackInteractivityController;
use App\Http\Controllers\SlackMembershipCommandController;
use App\Http\Controllers\SlackOptionsController;
use Illuminate\Support\Facades\Route;

Route::webhooks('webhooks/denhac-org', 'denhac.org');
Route::webhooks('webhooks/octoprint', 'OctoPrint');
Route::webhooks('webhooks/waiver', 'WaiverForever');

Route::middleware(['slack'])->prefix("slack")->group(function () {
    Route::post('door_code', SlackDoorCodeCommandController::class);
    Route::post('membership', SlackMembershipCommandController::class);

    Route::post('event', SlackEventController:: class);
    Route::post('interactive', SlackInteractivityController:: class);
    Route::post('options', SlackOptionsController:: class);
});

Route::prefix("quickbooks")->group(function () {
    Route::get('launch', App\Http\Controllers\Quickbooks\LaunchController::class);
    Route::get('redirect', App\Http\Controllers\Quickbooks\RedirectController::class)->name('quickbooks.redirect');
});

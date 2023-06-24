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

Route::middleware(['slack'])->group(function () {
    Route::post('slack/door_code', SlackDoorCodeCommandController::class);
    Route::post('slack/membership', SlackMembershipCommandController::class);

    Route::post('slack/event', SlackEventController:: class);
    Route::post('slack/interactive', SlackInteractivityController:: class);
    Route::post('slack/options', SlackOptionsController:: class);
});

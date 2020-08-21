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

use Illuminate\Support\Facades\Route;

Route::webhooks('webhooks/denhac-org', 'denhac.org');

Route::post('slack/door_code', 'SlackDoorCodeCommandController');
Route::post('slack/membership', 'SlackMembershipCommandController');

Route::post('slack/interactive', 'SlackInteractivityController@interactive');
Route::post('slack/options', 'SlackInteractivityController@options');

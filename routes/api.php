<?php

use App\Http\Controllers\CardUpdateRequestsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/card_updates', [CardUpdateRequestsController::class, 'index'])
    ->middleware('auth:api');
Route::post('/card_updates/{card_update_request}/status', [CardUpdateRequestsController::class, 'updateStatus'])
    ->middleware('auth:api');
Route::post('/active_card_holders', [CardUpdateRequestsController::class, 'updateActiveCardHolders'])
    ->middleware('auth:api');

<?php

use App\Http\Controllers\AllCardsController;
use App\Http\Controllers\CardScannedController;
use App\Http\Controllers\CardUpdateRequestsController;
use App\Http\Controllers\MemberCountApiController;
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

Route::middleware(['auth:api', 'scopes:card:manage'])
    ->group(function () {
        Route::get('/all_cards', AllCardsController::class);
        Route::get('/card_updates', [CardUpdateRequestsController::class, 'index']);
        Route::post('/card_updates/{card_update_request}/status', [CardUpdateRequestsController::class, 'updateStatus']);
        Route::post('/active_card_holders', [CardUpdateRequestsController::class, 'updateActiveCardHolders']);

        Route::post('/events/card_scanned', CardScannedController::class);
    });

Route::get('/member_count', MemberCountApiController::class);

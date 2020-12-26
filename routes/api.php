<?php

use App\Http\Controllers\CardScannedController;
use App\Http\Controllers\ADUpdateRequestsController;
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

Route::middleware(["auth:api"])
    ->group(function () {
        Route::get("/card_updates", [CardUpdateRequestsController::class, "index"]);
        Route::post("/card_updates/{card_update_request}/status", [CardUpdateRequestsController::class, "updateStatus"]);
        Route::post("/active_card_holders", [CardUpdateRequestsController::class, "updateActiveCardHolders"]);

        Route::post("/events/card_scanned", CardScannedController::class);

        Route::get("/ad_updates", [ADUpdateRequestsController::class, "index"]);
        Route::post("/card_updates/{ad_update_request}/status", [ADUpdateRequestsController::class, "updateStatus"]);
    });

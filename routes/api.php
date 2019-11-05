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

Route::get("/card_updates", [CardUpdateRequestsController::class, "index"])
    ->middleware("auth:api");

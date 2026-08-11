<?php

use App\Http\Controllers\Api\AirportController;
use App\Http\Controllers\Api\FlightSearchController;
use App\Http\Controllers\Api\SignalController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('airports')->group(function (): void {
    Route::get('popular', [AirportController::class, 'popular']);
    Route::get('search', [AirportController::class, 'search']);
});

Route::get('flights/search', [FlightSearchController::class, 'search']);
Route::get('signals', [SignalController::class, 'index']);
Route::post('subscriptions', [SubscriptionController::class, 'store']);

<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehicleApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/vehicles', [VehicleApiController::class, 'index']);
        Route::get('/vehicles/{vehicle}', [VehicleApiController::class, 'show']);
        Route::post('/vehicles/scan/{token}', [VehicleApiController::class, 'scan']);
        Route::post('/vehicles/{vehicle}/check-in', [VehicleApiController::class, 'checkIn']);
        Route::post('/vehicles/{vehicle}/loan', [VehicleApiController::class, 'loan']);
        Route::post('/loans/{loan}/return', [VehicleApiController::class, 'returnLoan']);
    });
});

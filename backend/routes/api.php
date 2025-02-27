<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AdminGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group([
    'prefix' => '/v1'
], function() {

    // Authentication endpoints
    Route::group([
        'prefix' => '/auth'
    ], function() {

        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('/profile', [AuthController::class, 'profile'])->middleware('auth:sanctum');

    });

    // User CRUD endpoints (admin only)
    Route::group([
        'middleware' => ['auth:sanctum', AdminGate::class]
    ], function() {

        Route::apiResource('user', UserController::class);

    });

});
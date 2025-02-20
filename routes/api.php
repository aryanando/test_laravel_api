<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArtistController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:api')->group(function () {
    Route::apiResource('artist', ArtistController::class);
    Route::post('logout', [AuthController::class, 'logout']);
});

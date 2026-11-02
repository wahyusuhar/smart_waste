<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassifyController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

// Limits brute-force attempts: 5 requests per minute per IP, shared by login and register.
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dashboard', DashboardController::class);
    Route::apiResource('reports', ReportController::class)->only(['index', 'store', 'show']);
    Route::post('/classify', ClassifyController::class);
});

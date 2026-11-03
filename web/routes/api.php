<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassifyController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

// Every API route name starts with "api.": apiResource('reports') would otherwise be named reports.index and
// reports.show, the same names as the web pages, and route('reports.index') could point to the JSON API.
Route::name('api.')->group(function () {
    // Limits brute-force attempts: 5 requests per minute per IP, shared by login and register.
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::apiResource('reports', ReportController::class)->only(['index', 'store', 'show']);
        Route::post('/classify', ClassifyController::class)->name('classify');
    });
});

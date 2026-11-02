<?php

use App\Http\Controllers\Auth\SessionController;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Reports\Index as ReportIndex;
use App\Livewire\User\Dashboard as UserDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()->dashboardRoute())
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    // No role middleware here: admins are redirected to their own dashboard inside the component.
    Route::livewire('/dashboard', UserDashboard::class)->name('dashboard');

    // Shared by admins and users; what each one may see or do is checked in the components (ReportPolicy).
    Route::livewire('/reports', ReportIndex::class)->name('reports.index');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/dashboard', AdminDashboard::class)->name('dashboard');
    });
});

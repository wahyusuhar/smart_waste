<?php

use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\WelcomeController;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Users\Form as UserForm;
use App\Livewire\Admin\Users\Index as UserIndex;
use App\Livewire\Reports\Form as ReportForm;
use App\Livewire\Reports\Index as ReportIndex;
use App\Livewire\Reports\Show as ReportShow;
use App\Livewire\User\Dashboard as UserDashboard;
use Illuminate\Support\Facades\Route;

// Guests see the opening page; signed-in users go to their dashboard (WelcomeController).
Route::get('/', WelcomeController::class);

Route::middleware('guest')->group(function () {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store']);
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    // No role middleware here: admins are redirected to their own dashboard inside the component.
    Route::livewire('/dashboard', UserDashboard::class)->name('dashboard');

    // Shared by admins and users; what each one may see or do is checked in the components (ReportPolicy).
    Route::livewire('/reports', ReportIndex::class)->name('reports.index');
    Route::livewire('/reports/create', ReportForm::class)->name('reports.create');
    Route::livewire('/reports/{report}', ReportShow::class)->name('reports.show');
    Route::livewire('/reports/{report}/edit', ReportForm::class)->name('reports.edit');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/dashboard', AdminDashboard::class)->name('dashboard');
        Route::livewire('/users', UserIndex::class)->name('users.index');
        Route::livewire('/users/create', UserForm::class)->name('users.create');
        Route::livewire('/users/{user}/edit', UserForm::class)->name('users.edit');
    });
});

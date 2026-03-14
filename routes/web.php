<?php

use App\Http\Controllers\Mobile\MobileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

// Mobile app routes (NativePHP)
Route::prefix('mobile')->name('mobile.')->group(function () {
    Route::get('/', [MobileController::class, 'startup'])->name('startup');

    // Auth screens
    Route::get('/login', [MobileController::class, 'login'])->name('login');
    Route::get('/register', [MobileController::class, 'register'])->name('register');
    Route::post('/token', [MobileController::class, 'storeToken'])->name('storeToken');
    Route::get('/biometric-login', [MobileController::class, 'biometricLogin'])->name('biometricLogin');
    Route::post('/logout', [MobileController::class, 'logout'])->name('logout');

    // Main app screens
    Route::get('/push/enroll', [MobileController::class, 'pushEnroll'])->name('pushEnroll');
    Route::get('/dashboard', [MobileController::class, 'dashboard'])->name('dashboard');
    Route::get('/ledger', [MobileController::class, 'ledger'])->name('ledger');
    Route::get('/funds', [MobileController::class, 'funds'])->name('funds');
    Route::get('/debts', [MobileController::class, 'debts'])->name('debts');
    Route::get('/settings', [MobileController::class, 'settings'])->name('settings');
});

require __DIR__.'/settings.php';

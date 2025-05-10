<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZappiController;
use App\Http\Controllers\SunSyncController;
use App\Http\Controllers\EvChargingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AppKeySetupController;

// App Key Setup Routes - These should be first to handle missing app key
Route::get('/setup/app-key', [AppKeySetupController::class, 'show'])->name('app.key.setup');
Route::post('/setup/app-key/generate', [AppKeySetupController::class, 'generate'])->name('app.key.generate');

// Other routes
Route::get('/', function () {
    return view('home');
});

Route::get('/zappi/status', [ZappiController::class, 'status'])->name('zappi.status');

Route::get('/sunsync/dashboard', [SunSyncController::class, 'dashboard'])->name('sunsync.dashboard');

Route::get('/ev-charging/status', [EvChargingController::class, 'updateSystemMode'])->name('ev-charging.status');
Route::post('/ev-charging/settings', [EvChargingController::class, 'updateSettings'])->name('ev-charging.settings.update');

Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

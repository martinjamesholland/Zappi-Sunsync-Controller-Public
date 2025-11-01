<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZappiController;
use App\Http\Controllers\SunSyncController;
use App\Http\Controllers\EvChargingController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\LoginController;

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Ev-charging status - special handling for cron mode
Route::middleware(['setup.complete', 'cron.auth', 'throttle:30,1', 'api.key'])->group(function () {
    Route::get('/ev-charging/status', [EvChargingController::class, 'updateSystemMode'])
        ->name('ev-charging.status');
});

// Data mart refresh - token protected
Route::middleware(['setup.complete', 'throttle:2,1'])->group(function () {
    Route::get('/reports/refresh-data-mart', [ReportsController::class, 'refreshDataMart'])
        ->name('reports.refresh-data-mart');
});

// Setup Wizard Routes - These should be first and excluded from setup check middleware
Route::prefix('setup')->name('setup.')->group(function () {
    Route::get('/', [SetupController::class, 'index'])->name('index');
    
    // Step 1: APP KEY
    Route::get('/app-key', [SetupController::class, 'showAppKey'])->name('app-key');
    Route::post('/app-key/generate', [SetupController::class, 'generateAppKey'])->name('app-key.generate');
    
    // Step 2: Database
    Route::get('/database', [SetupController::class, 'showDatabase'])->name('database');
    Route::post('/database/save', [SetupController::class, 'saveDatabase'])->name('database.save');
    
    // Step 3: Zappi
    Route::get('/zappi', [SetupController::class, 'showZappi'])->name('zappi');
    Route::post('/zappi/save', [SetupController::class, 'saveZappi'])->name('zappi.save');
    
    // Step 4: SunSync
    Route::get('/sunsync', [SetupController::class, 'showSunSync'])->name('sunsync');
    Route::post('/sunsync/save', [SetupController::class, 'saveSunSync'])->name('sunsync.save');
    
    // Completion
    Route::get('/complete', [SetupController::class, 'complete'])->name('complete');
});

// Application routes - protected by setup check middleware and authentication
Route::middleware(['setup.complete', 'auth'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('/zappi/status', [ZappiController::class, 'status'])->name('zappi.status');

    Route::get('/sunsync/dashboard', [SunSyncController::class, 'dashboard'])->name('sunsync.dashboard');

    // Note: ev-charging/status route is defined separately above to handle both auth and cron modes

    Route::post('/ev-charging/settings', [EvChargingController::class, 'updateSettings'])
        ->middleware('throttle:10,1')
        ->name('ev-charging.settings.update');
    Route::get('/ev-charging/get-inverter-settings', [EvChargingController::class, 'getInverterSettings'])
        ->middleware('throttle:60,1')
        ->name('ev-charging.get-inverter-settings');
    Route::match(['get', 'post'], '/ev-charging/sync-now', [EvChargingController::class, 'syncToInverterNow'])
        ->middleware('throttle:10,1')
        ->name('ev-charging.sync-now');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Reports routes
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    
    // Maintenance routes (protected by token)
    Route::get('/maintenance/run-migrations', [ReportsController::class, 'runMigrations'])->name('maintenance.migrations');
    Route::get('/maintenance/clear-caches', [ReportsController::class, 'clearCaches'])->name('maintenance.clear-caches');
    
    // Note: reports/refresh-data-mart route is defined separately above to handle token auth
});

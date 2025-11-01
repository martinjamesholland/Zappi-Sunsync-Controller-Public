<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EnergyFlowController;
use App\Http\Controllers\Api\CommandController;
use App\Http\Controllers\ReportsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Energy Flow API Routes
Route::get('energy-flow/history', [EnergyFlowController::class, 'history'])->name('api.energy-flow.history');
Route::get('/energy-flow/available-dates', [EnergyFlowController::class, 'getAvailableDates']);

// Command API Routes
Route::get('command/update-energy-flow', [CommandController::class, 'updateEnergyFlowData'])->name('api.command.update-energy-flow');

// Reports API Routes
Route::get('reports/daily-summary', [ReportsController::class, 'getDailySummary'])->name('api.reports.daily-summary');
Route::get('reports/energy-distribution', [ReportsController::class, 'getEnergyDistribution'])->name('api.reports.energy-distribution');
Route::get('reports/battery-efficiency', [ReportsController::class, 'getBatteryEfficiency'])->name('api.reports.battery-efficiency');
Route::get('reports/solar-yield', [ReportsController::class, 'getSolarYield'])->name('api.reports.solar-yield');
Route::get('reports/load-distribution', [ReportsController::class, 'getLoadDistribution'])->name('api.reports.load-distribution');
Route::get('reports/grid-interaction', [ReportsController::class, 'getGridInteraction'])->name('api.reports.grid-interaction');
Route::get('reports/hourly-patterns', [ReportsController::class, 'getHourlyPatterns'])->name('api.reports.hourly-patterns');
Route::get('reports/ev-charging', [ReportsController::class, 'getEvChargingActivity'])->name('api.reports.ev-charging');
Route::get('reports/system-stats', [ReportsController::class, 'getSystemStats'])->name('api.reports.system-stats');
Route::get('reports/home-usage-stats', [ReportsController::class, 'getHomeUsageStats'])->name('api.reports.home-usage-stats');
Route::get('reports/cost-breakdown', [ReportsController::class, 'getCostBreakdown'])->name('api.reports.cost-breakdown');
Route::get('reports/daily-cost-breakdown', [ReportsController::class, 'getDailyCostBreakdown'])->name('api.reports.daily-cost-breakdown');
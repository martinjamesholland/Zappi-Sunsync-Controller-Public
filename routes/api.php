<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EnergyFlowController;
use App\Http\Controllers\Api\CommandController;

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
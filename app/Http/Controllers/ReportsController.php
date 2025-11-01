<?php

namespace App\Http\Controllers;

use App\Models\EnergyFlowLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportsController extends Controller
{
    private function applyDateFilter($query, $days)
    {
        if ($days === 'all') {
            return $query;
        } elseif ($days === 'ytd') {
            return $query->where('created_at', '>=', now()->startOfYear());
        } elseif (is_numeric($days)) {
            return $query->where('created_at', '>=', now()->subDays((int)$days));
        }
        return $query->where('created_at', '>=', now()->subDays(30)); // default
    }

    public function index(): View
    {
        return view('reports.index');
    }

    /**
     * Run migrations via HTTP (protected by token).
     */
    public function runMigrations(Request $request)
    {
        // Require token for security
        $token = $request->query('token');
        $expected = env('ETL_WEBHOOK_KEY');

        if (!$expected || !hash_equals((string)$expected, (string)$token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();
        } catch (\Throwable $e) {
            Log::error('Migration failed: '.$e->getMessage());
            return response()->json(['message' => 'Migration failed', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Migrations completed',
            'output' => $output
        ]);
    }

    /**
     * Clear all caches via HTTP (protected by token).
     */
    public function clearCaches(Request $request)
    {
        // Require token for security
        $token = $request->query('token');
        $expected = env('ETL_WEBHOOK_KEY');

        if (!$expected || !hash_equals((string)$expected, (string)$token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
        } catch (\Throwable $e) {
            Log::error('Cache clear failed: '.$e->getMessage());
            return response()->json(['message' => 'Cache clear failed', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'All caches cleared successfully'
        ]);
    }

    /**
     * Trigger the data mart refresh via HTTP (protected by token or session auth).
     */
    public function refreshDataMart(Request $request)
    {
        // Require token for security
        $token = $request->query('token');
        $expected = env('ETL_WEBHOOK_KEY');

        if (!$expected || !hash_equals((string)$expected, (string)$token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $since = $request->query('since');

        try {
            // Keep web execution quick by default: last 60 minutes + today's daily bucket
            $args = array_filter([
                '--since' => $since ?: now()->subMinutes(60)->toIso8601String(),
                '--max-minutes' => $request->query('max_minutes') ?: 60,
                '--max-days' => $request->query('max_days') ?: 1,
            ]);

            $exitCode = Artisan::call('app:refresh-energy-data-mart', $args);
        } catch (\Throwable $e) {
            Log::error('HTTP ETL trigger failed: '.$e->getMessage());
            return response()->json(['message' => 'ETL failed', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'ETL triggered',
            'exit_code' => $exitCode,
            'since' => $args['--since'] ?? $since,
            'limits' => [
                'max_minutes' => $args['--max-minutes'] ?? null,
                'max_days' => $args['--max-days'] ?? null,
            ],
        ]);
    }

    public function getDailySummary(Request $request)
    {
        $days = $request->input('days', 30);
        
        $query = EnergyFlowLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('AVG(total_pv_power) as avg_pv'),
                DB::raw('MAX(total_pv_power) as max_pv'),
                DB::raw('AVG(grid_power) as avg_grid'),
                DB::raw('AVG(battery_power) as avg_battery'),
                DB::raw('AVG(battery_soc) as avg_soc'),
                DB::raw('AVG(home_load_power + smart_load_power + ups_load_power) as avg_load'),
                DB::raw('AVG(home_load_power) as avg_home_load'),
                DB::raw('AVG(smart_load_power) as avg_smart_load'),
                DB::raw('AVG(ups_load_power) as avg_ups_load'),
                DB::raw('COUNT(*) as record_count')
            )
            ;
            $query = $this->applyDateFilter($query, $days);
            $data = $query
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($data);
    }

    public function getEnergyDistribution(Request $request)
    {
        $days = $request->input('days', 7);
        
        $query = EnergyFlowLog::select(
                DB::raw('AVG(total_pv_power) as solar_energy'),
                DB::raw('AVG(CASE WHEN grid_power > 0 THEN grid_power ELSE 0 END) as grid_import'),
                DB::raw('AVG(CASE WHEN grid_power < 0 THEN ABS(grid_power) ELSE 0 END) as grid_export'),
                DB::raw('AVG(CASE WHEN battery_power < 0 THEN ABS(battery_power) ELSE 0 END) as battery_discharge'),
                DB::raw('AVG(home_load_power + smart_load_power + ups_load_power) as total_consumption')
            )
            ;
            $query = $this->applyDateFilter($query, $days);
            $data = $query
            ->first();

        return response()->json($data);
    }

    public function getBatteryEfficiency(Request $request)
    {
        $days = $request->input('days', 30);
        
        $query = EnergyFlowLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('AVG(battery_soc) as avg_soc'),
                DB::raw('MAX(battery_soc) as max_soc'),
                DB::raw('MIN(battery_soc) as min_soc'),
                DB::raw('AVG(CASE WHEN battery_power > 0 THEN battery_power ELSE 0 END) as avg_charge'),
                DB::raw('AVG(CASE WHEN battery_power < 0 THEN ABS(battery_power) ELSE 0 END) as avg_discharge')
            )
            ;
            $query = $this->applyDateFilter($query, $days);
            $data = $query
            ->whereNotNull('battery_soc')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($data);
    }

    public function getSolarYield(Request $request)
    {
        $days = $request->input('days', 30);
        
        $query = EnergyFlowLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('AVG(total_pv_power) as avg_pv_power'),
                DB::raw('MAX(total_pv_power) as peak_pv_power'),
                DB::raw('SUM(total_pv_power) as total_solar_energy'),
                DB::raw('AVG(pv1_power) as avg_pv1'),
                DB::raw('AVG(pv2_power) as avg_pv2')
            )
            ;
            $query = $this->applyDateFilter($query, $days);
            $data = $query
            ->whereNotNull('total_pv_power')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($data);
    }

    public function getLoadDistribution(Request $request)
    {
        $days = $request->input('days', 7);
        
        $query = EnergyFlowLog::select(
                DB::raw('AVG(home_load_power) as home_load'),
                DB::raw('AVG(smart_load_power) as smart_load'),
                DB::raw('AVG(ups_load_power) as ups_load'),
                DB::raw('AVG(CASE WHEN car_node_connection IN (\'C2\', \'B2\') OR car_node_sta = 3 THEN ABS(zappi_node) ELSE 0 END) as ev_charging_load')
            )
            ;
            $query = $this->applyDateFilter($query, $days);
            $data = $query
            ->first();

        return response()->json($data);
    }

    public function getGridInteraction(Request $request)
    {
        $days = $request->input('days', 30);
        
        $query = EnergyFlowLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('AVG(CASE WHEN grid_power > 0 THEN grid_power ELSE 0 END) as grid_import'),
                DB::raw('AVG(CASE WHEN grid_power < 0 THEN ABS(grid_power) ELSE 0 END) as grid_export'),
                DB::raw('SUM(CASE WHEN grid_power > 0 THEN grid_power ELSE 0 END) as total_imported'),
                DB::raw('SUM(CASE WHEN grid_power < 0 THEN ABS(grid_power) ELSE 0 END) as total_exported')
            )
            ;
            $query = $this->applyDateFilter($query, $days);
            $data = $query
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($data);
    }

    public function getHourlyPatterns(Request $request)
    {
        $days = $request->input('days', 7);
        
        $query = EnergyFlowLog::select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('AVG(total_pv_power) as avg_pv'),
                DB::raw('AVG(home_load_power + smart_load_power + ups_load_power) as avg_load'),
                DB::raw('AVG(battery_power) as avg_battery'),
                DB::raw('AVG(grid_power) as avg_grid')
            )
            ;
            $query = $this->applyDateFilter($query, $days);
            $data = $query
            ->groupBy('hour')
            ->orderBy('hour', 'asc')
            ->get();

        return response()->json($data);
    }

    public function getEvChargingActivity(Request $request)
    {
        $days = $request->input('days', 30);
        
        $query = EnergyFlowLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(CASE WHEN car_node_connection IN (\'C2\', \'B2\') OR car_node_sta = 3 THEN 1 END) as charging_sessions'),
                DB::raw('AVG(CASE WHEN car_node_connection IN (\'C2\', \'B2\') OR car_node_sta = 3 THEN ABS(zappi_node) ELSE 0 END) as avg_charging_power'),
                DB::raw('MAX(CASE WHEN car_node_connection IN (\'C2\', \'B2\') OR car_node_sta = 3 THEN ABS(zappi_node) ELSE 0 END) as peak_charging_power'),
                DB::raw('SUM(CASE WHEN car_node_connection IN (\'C2\', \'B2\') OR car_node_sta = 3 THEN ABS(zappi_node) ELSE 0 END) as total_ev_energy')
            )
            ;
            $query = $this->applyDateFilter($query, $days);
            $data = $query
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($data);
    }

    public function getSystemStats()
    {
        $stats = [
            'total_records' => EnergyFlowLog::count(),
            'oldest_record' => EnergyFlowLog::min('created_at'),
            'newest_record' => EnergyFlowLog::max('created_at'),
            'avg_solar_yield' => EnergyFlowLog::avg('total_pv_power'),
            'max_solar_yield' => EnergyFlowLog::max('total_pv_power'),
            'total_days' => EnergyFlowLog::distinct()->count(DB::raw('DATE(created_at)'))
        ];

        return response()->json($stats);
    }

    public function getHomeUsageStats(Request $request)
    {
        $days = $request->input('days', 30);
        
        // Get all home load values for the period
        $homeLoads = EnergyFlowLog::where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('home_load_power')
            ->pluck('home_load_power')
            ->map(function($value) {
                return abs($value); // Ensure positive values
            })
            ->sort()
            ->values();
        
        $count = $homeLoads->count();
        
        if ($count === 0) {
            return response()->json([
                'average' => 0,
                'median' => 0,
                'min' => 0,
                'max' => 0,
                'first_quartile' => 0,
                'third_quartile' => 0
            ]);
        }
        
        // Calculate average
        $average = $homeLoads->avg();
        
        // Calculate median
        $median = $count % 2 === 0 
            ? ($homeLoads[$count / 2 - 1] + $homeLoads[$count / 2]) / 2
            : $homeLoads[floor($count / 2)];
        
        // Calculate quartiles
        $firstQuartileIndex = (int) floor($count * 0.25);
        $thirdQuartileIndex = (int) floor($count * 0.75);
        
        $firstQuartile = $homeLoads[$firstQuartileIndex] ?? 0;
        $thirdQuartile = $homeLoads[$thirdQuartileIndex] ?? 0;
        
        return response()->json([
            'average' => $average,
            'median' => $median,
            'min' => $homeLoads->min(),
            'max' => $homeLoads->max(),
            'first_quartile' => $firstQuartile,
            'third_quartile' => $thirdQuartile,
            'count' => $count,
            'std_dev' => $this->calculateStandardDeviation($homeLoads->toArray())
        ]);
    }
    
    private function calculateStandardDeviation($values)
    {
        $count = count($values);
        if ($count === 0) {
            return 0;
        }
        
        $mean = array_sum($values) / $count;
        $variance = 0;
        
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        $stdDev = sqrt($variance / $count);
        return $stdDev;
    }

    /**
     * Check if timestamp is in peak hours
     * Dynamically loaded from database settings
     */
    private function isPeakHours($timestamp)
    {
        $time = Carbon::parse($timestamp);
        $hour = $time->hour;
        $minute = $time->minute;
        
        // Get peak hours from database
        $peakStartValue = \App\Models\CostSetting::getValue('peak_start', 530); // Default 530 (5:30)
        $peakEndValue = \App\Models\CostSetting::getValue('peak_end', 2330); // Default 2330 (23:30)
        
        // Parse HHMM format
        $peakStartHour = intval($peakStartValue / 100);
        $peakStartMinute = intval($peakStartValue % 100);
        $peakEndHour = intval($peakEndValue / 100);
        $peakEndMinute = intval($peakEndValue % 100);
        
        // Convert to minutes since midnight for easier comparison
        $minutes = $hour * 60 + $minute;
        $peakStart = $peakStartHour * 60 + $peakStartMinute;
        $peakEnd = $peakEndHour * 60 + $peakEndMinute;
        
        return $minutes >= $peakStart && $minutes < $peakEnd;
    }

    /**
     * Check if EV is charging
     */
    private function isEvCharging($connection, $sta)
    {
        // C2 = Charging, B2 = Boosting/Waiting
        $chargingConnections = ['C2', 'B2'];
        $chargingSta = 3; // Diverting/Charging
        
        return in_array($connection, $chargingConnections) || $sta == $chargingSta;
    }

    /**
     * Calculate cost per kWh for given timestamp and EV status
     * Dynamically loaded from database settings
     */
    private function getCostPerKwh($timestamp, $isEvCharging = false)
    {
        // Get rates from database settings
        $peakRate = \App\Models\CostSetting::getValue('peak_rate', 0.30);
        $offPeakRate = \App\Models\CostSetting::getValue('off_peak_rate', 0.07);
        $evChargingRate = \App\Models\CostSetting::getValue('ev_charging_rate', 0.07);
        
        // EV charging always uses EV charging rate
        if ($isEvCharging) {
            return $evChargingRate;
        }
        
        return $this->isPeakHours($timestamp) ? $peakRate : $offPeakRate;
    }

    /**
     * Get cost breakdown for a period
     */
    public function getCostBreakdown(Request $request)
    {
        $startTime = microtime(true);
        $days = $request->input('days', 30);
        
        // Get raw data with timestamps - use applyDateFilter for proper handling
        $queryStart = microtime(true);
        $query = EnergyFlowLog::select('created_at', 'grid_power', 'zappi_node', 'car_node_connection', 'car_node_sta');
        $query = $this->applyDateFilter($query, $days);
        $logs = $query->get();
        $queryTime = microtime(true) - $queryStart;
        
        $calcStart = microtime(true);
        
        $totalImportCost = 0;
        $totalExportCredit = 0;
        $peakImportCost = 0;
        $offPeakImportCost = 0;
        $evChargingCost = 0;
        $totalGridImport = 0;
        $totalGridExport = 0;
        
        // Sampling interval is 5 minutes (12 samples per hour)
        $hoursPerSample = 5 / 60; // 5 minutes = 0.0833 hours
        
        // Pre-fetch all rate settings once (massive performance gain)
        $peakRate = \App\Models\CostSetting::getValue('peak_rate', 0.30);
        $offPeakRate = \App\Models\CostSetting::getValue('off_peak_rate', 0.07);
        $evChargingRate = \App\Models\CostSetting::getValue('ev_charging_rate', 0.07);
        $exportCreditRate = \App\Models\CostSetting::getValue('export_credit_rate', 0.15);
        
        // Pre-calculate peak window boundaries
        $peakStartValue = \App\Models\CostSetting::getValue('peak_start', 530);
        $peakEndValue = \App\Models\CostSetting::getValue('peak_end', 2330);
        $peakStartMinutes = intval($peakStartValue / 100) * 60 + intval($peakStartValue % 100);
        $peakEndMinutes = intval($peakEndValue / 100) * 60 + intval($peakEndValue % 100);
        
        foreach ($logs as $log) {
            $gridPower = $log->grid_power ?? 0;
            $zappiNode = abs($log->zappi_node ?? 0);
            $isCharging = $this->isEvCharging($log->car_node_connection, $log->car_node_sta);
            
            // Convert W to kWh using absolute value
            $kwhThisSample = abs($gridPower) * $hoursPerSample / 1000;
            
            // Grid import (positive grid_power means importing from grid)
            if ($gridPower > 0) {
                $totalGridImport += $kwhThisSample;
                
                // Calculate cost based on time and EV status
                if ($isCharging && $zappiNode > 0) {
                    // EV charging from grid
                    $cost = $kwhThisSample * $evChargingRate;
                    $evChargingCost += $cost;
                    $offPeakImportCost += $cost;
                } else {
                    // Regular grid import - check if peak hours using pre-calculated bounds
                    $timestamp = Carbon::parse($log->created_at);
                    $minutes = $timestamp->hour * 60 + $timestamp->minute;
                    $costPerKwh = ($minutes >= $peakStartMinutes && $minutes < $peakEndMinutes) ? $peakRate : $offPeakRate;
                    
                    $cost = $kwhThisSample * $costPerKwh;
                    
                    if ($costPerKwh == $peakRate) {
                        $peakImportCost += $cost;
                    } else {
                        $offPeakImportCost += $cost;
                    }
                }
                
                $totalImportCost += $cost;
            }
            
            // Grid export (negative grid_power means exporting to grid)
            if ($gridPower < 0) {
                $totalGridExport += $kwhThisSample;
                $credit = $kwhThisSample * $exportCreditRate;
                $totalExportCredit += $credit;
            }
        }
        
        // Format peak times for display
        $peakStartFormatted = sprintf('%02d:%02d', intval($peakStartValue / 100), $peakStartValue % 100);
        $peakEndFormatted = sprintf('%02d:%02d', intval($peakEndValue / 100), $peakEndValue % 100);
        
        $calcTime = microtime(true) - $calcStart;
        $totalTime = microtime(true) - $startTime;
        
        // Log performance metrics
        Log::info('Cost breakdown performance', [
            'days' => $days,
            'row_count' => $logs->count(),
            'query_time_ms' => round($queryTime * 1000, 2),
            'calculation_time_ms' => round($calcTime * 1000, 2),
            'total_time_ms' => round($totalTime * 1000, 2)
        ]);
        
        return response()->json([
            'total_import_cost' => round($totalImportCost, 2),
            'total_export_credit' => round($totalExportCredit, 2),
            'peak_import_cost' => round($peakImportCost, 2),
            'off_peak_import_cost' => round($offPeakImportCost, 2),
            'ev_charging_cost' => round($evChargingCost, 2),
            'total_grid_import_kwh' => round($totalGridImport, 2),
            'total_grid_export_kwh' => round($totalGridExport, 2),
            'net_cost' => round($totalImportCost - $totalExportCredit, 2),
            'rates' => [
                'peak_rate' => '£' . number_format($peakRate, 2) . '/kWh',
                'off_peak_rate' => '£' . number_format($offPeakRate, 2) . '/kWh',
                'ev_charging_rate' => '£' . number_format($evChargingRate, 2) . '/kWh',
                'export_credit' => '£' . number_format($exportCreditRate, 2) . '/kWh',
                'peak_hours' => $peakStartFormatted . '-' . $peakEndFormatted
            ]
        ]);
    }

    /**
     * Get daily cost breakdown
     */
    public function getDailyCostBreakdown(Request $request)
    {
        $startTime = microtime(true);
        $days = $request->input('days', 30);
        
        $queryStart = microtime(true);
        $query = EnergyFlowLog::select('created_at', 'grid_power', 'zappi_node', 'car_node_connection', 'car_node_sta');
        $query = $this->applyDateFilter($query, $days);
        $logs = $query->orderBy('created_at', 'asc')->get();
        $queryTime = microtime(true) - $queryStart;
        
        $calcStart = microtime(true);
        $hoursPerSample = 5 / 60; // 5 minutes = 0.0833 hours
        
        // Pre-fetch all rate settings once (massive performance gain)
        $peakRate = \App\Models\CostSetting::getValue('peak_rate', 0.30);
        $offPeakRate = \App\Models\CostSetting::getValue('off_peak_rate', 0.07);
        $evChargingRate = \App\Models\CostSetting::getValue('ev_charging_rate', 0.07);
        $exportCreditRate = \App\Models\CostSetting::getValue('export_credit_rate', 0.15);
        
        // Pre-calculate peak window boundaries
        $peakStartValue = \App\Models\CostSetting::getValue('peak_start', 530);
        $peakEndValue = \App\Models\CostSetting::getValue('peak_end', 2330);
        $peakStartMinutes = intval($peakStartValue / 100) * 60 + intval($peakStartValue % 100);
        $peakEndMinutes = intval($peakEndValue / 100) * 60 + intval($peakEndValue % 100);
        
        // Group by date
        $dailyData = [];
        
        foreach ($logs as $log) {
            $date = Carbon::parse($log->created_at)->format('Y-m-d');
            
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'date' => $date,
                    'import_cost' => 0,
                    'export_credit' => 0,
                    'peak_import_cost' => 0,
                    'off_peak_import_cost' => 0,
                    'ev_charging_cost' => 0,
                    'grid_import_kwh' => 0,
                    'grid_export_kwh' => 0
                ];
            }
            
            $gridPower = $log->grid_power ?? 0;
            $zappiNode = abs($log->zappi_node ?? 0);
            $isCharging = $this->isEvCharging($log->car_node_connection, $log->car_node_sta);
            
            $kwhThisSample = abs($gridPower) * $hoursPerSample / 1000;
            
            if ($gridPower > 0) {
                $dailyData[$date]['grid_import_kwh'] += $kwhThisSample;
                
                if ($isCharging && $zappiNode > 0) {
                    $cost = $kwhThisSample * $evChargingRate;
                    $dailyData[$date]['ev_charging_cost'] += $cost;
                    $dailyData[$date]['off_peak_import_cost'] += $cost;
                } else {
                    // Regular grid import - check if peak hours using pre-calculated bounds
                    $timestamp = Carbon::parse($log->created_at);
                    $minutes = $timestamp->hour * 60 + $timestamp->minute;
                    $costPerKwh = ($minutes >= $peakStartMinutes && $minutes < $peakEndMinutes) ? $peakRate : $offPeakRate;
                    
                    $cost = $kwhThisSample * $costPerKwh;
                    
                    if ($costPerKwh == $peakRate) {
                        $dailyData[$date]['peak_import_cost'] += $cost;
                    } else {
                        $dailyData[$date]['off_peak_import_cost'] += $cost;
                    }
                }
                
                $dailyData[$date]['import_cost'] += $cost;
            }
            
            if ($gridPower < 0) {
                $dailyData[$date]['grid_export_kwh'] += $kwhThisSample;
                $credit = $kwhThisSample * $exportCreditRate;
                $dailyData[$date]['export_credit'] += $credit;
            }
        }
        
        // Round values
        foreach ($dailyData as $date => $data) {
            $dailyData[$date]['import_cost'] = round($data['import_cost'], 2);
            $dailyData[$date]['export_credit'] = round($data['export_credit'], 2);
            $dailyData[$date]['peak_import_cost'] = round($data['peak_import_cost'], 2);
            $dailyData[$date]['off_peak_import_cost'] = round($data['off_peak_import_cost'], 2);
            $dailyData[$date]['ev_charging_cost'] = round($data['ev_charging_cost'], 2);
            $dailyData[$date]['grid_import_kwh'] = round($data['grid_import_kwh'], 2);
            $dailyData[$date]['grid_export_kwh'] = round($data['grid_export_kwh'], 2);
            $dailyData[$date]['net_cost'] = round($data['import_cost'] - $data['export_credit'], 2);
        }
        
        $calcTime = microtime(true) - $calcStart;
        $totalTime = microtime(true) - $startTime;
        
        // Log performance metrics
        Log::info('Daily cost breakdown performance', [
            'days' => $days,
            'row_count' => $logs->count(),
            'query_time_ms' => round($queryTime * 1000, 2),
            'calculation_time_ms' => round($calcTime * 1000, 2),
            'total_time_ms' => round($totalTime * 1000, 2)
        ]);
        
        return response()->json(array_values($dailyData));
    }
}

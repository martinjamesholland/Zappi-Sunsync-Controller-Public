<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnergyFlowLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EnergyFlowController extends Controller
{
    public function getAvailableDates()
    {
        try {
            // First, check if we have any records at all
            $count = EnergyFlowLog::count();
            if ($count === 0) {
                \Log::info('No records found in EnergyFlowLog table');
                return response()->json([]);
            }
            
            // Get distinct dates using a simpler approach
            $dates = EnergyFlowLog::selectRaw('DISTINCT DATE(created_at) as date')
                ->orderBy('date')
                ->get()
                ->pluck('date')
                ->toArray();
            
            // If that fails, try a more basic approach
            if (empty($dates)) {
                \Log::info('First query returned no dates, trying alternative query');
                $results = EnergyFlowLog::selectRaw('created_at')
                    ->orderBy('created_at')
                    ->get();
                    
                $dates = [];
                foreach ($results as $result) {
                    $date = date('Y-m-d', strtotime($result->created_at));
                    if (!in_array($date, $dates)) {
                        $dates[] = $date;
                    }
                }
            }
            
            \Log::info('Found ' . count($dates) . ' available dates');
            return response()->json($dates);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error fetching available dates: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Failed to fetch available dates',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $timeframe = $request->input('timeframe', '24h');
        $date = $request->input('date');

        $query = EnergyFlowLog::query();

        if ($date) {
            // If a specific date is requested, get data for that day
            $query->whereDate('created_at', $date);
        } else {
            // Otherwise use the timeframe
            switch ($timeframe) {
                case '1h':
                    $query->where('created_at', '>=', now()->subHour());
                    break;
                case '6h':
                    $query->where('created_at', '>=', now()->subHours(6));
                    break;
                case '24h':
                    $query->where('created_at', '>=', now()->subDay());
                    break;
                case '48h':
                    $query->where('created_at', '>=', now()->subHours(48));
                    break;
                case '72h':
                    $query->where('created_at', '>=', now()->subHours(72));
                    break;
                case '7d':
                    $query->where('created_at', '>=', now()->subDays(7));
                    break;
                default:
                    $query->where('created_at', '>=', now()->subDay());
            }
        }

        $data = $query->orderBy('created_at')->get();

        return response()->json($data);
    }
} 
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\JsonResponse;

class CommandController extends Controller
{
    /**
     * Run the energy flow data update command
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateEnergyFlowData(Request $request): JsonResponse
    {
        // Validate API key for security
        $configApiKey = config('services.api.key');
        $requestApiKey = $request->query('api_key');
        
        if (empty($configApiKey) || $requestApiKey !== $configApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 401);
        }
        
        // Run the command
        try {
            $exitCode = Artisan::call('app:update-energy-flow-data');
            
            // Get the command output
            $output = Artisan::output();
            
            return response()->json([
                'success' => $exitCode === 0,
                'exit_code' => $exitCode,
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to run command: ' . $e->getMessage()
            ], 500);
        }
    }
}

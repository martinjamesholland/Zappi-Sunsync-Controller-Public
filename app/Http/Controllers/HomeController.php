<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SunSyncService;
use App\Services\MyEnergiApiService;
use App\Services\DataMaskingService;
use App\Models\EnergyFlowLog;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    private SunSyncService $sunSyncService;
    private MyEnergiApiService $myEnergiApiService;
    private DataMaskingService $dataMaskingService;

    public function __construct(
        SunSyncService $sunSyncService,
        MyEnergiApiService $myEnergiApiService,
        DataMaskingService $dataMaskingService
    ) {
        $this->sunSyncService = $sunSyncService;
        $this->myEnergiApiService = $myEnergiApiService;
        $this->dataMaskingService = $dataMaskingService;
    }

    public function index(): View
    {
        // Get SunSync data
        $username = config('services.sunsync.username');
        $password = config('services.sunsync.password');
        $sunSyncData = [];
        $sunSyncApiRequests = [];
        $plantInfo = null;

        if (!empty($username) && !empty($password)) {
            $authResponse = $this->sunSyncService->authenticate($username, $password);
            if ($authResponse && !isset($authResponse['error'])) {
                $plantInfo = $this->sunSyncService->getPlantInfo();
                if ($plantInfo && isset($plantInfo['id'])) {
                    $inverterInfo = $this->sunSyncService->getInverterInfo($plantInfo['id']);
                    if ($inverterInfo && isset($inverterInfo['sn'])) {
                        $sunSyncData = $this->sunSyncService->getInverterFlowInfo($inverterInfo['sn']);
                        // Add plantInfo to sunSyncData
                        $sunSyncData['plantInfo'] = $plantInfo;
                        
                        // Log the raw SunSync data
                        Log::info('Raw SunSync Data:', $sunSyncData);
                    } else {
                        Log::warning('Inverter info missing or invalid');
                    }
                } else {
                    Log::warning('Plant info missing or invalid');
                }
            } else {
                Log::warning('SunSync authentication failed', ['response' => $authResponse]);
            }
            $sunSyncApiRequests = $this->sunSyncService->getApiRequests();
        } else {
            Log::warning('SunSync credentials not configured');
        }

        // Get Zappi data
        $zappiData = $this->myEnergiApiService->getStatus();
        $zappiApiRequests = $this->myEnergiApiService->getApiRequests();

        // Log Zappi data for debugging
        Log::info('Zappi Data:', $zappiData);

        // Store the latest status in the database if we have both data sets
        if (!empty($sunSyncData) && !empty($zappiData)) {
            try {
                Log::info('Attempting to store energy flow log...');
                // Store the data with timestamps using the storeEnergyFlowLog method
                $this->storeEnergyFlowLog($sunSyncData, $zappiData);
                Log::info('Successfully stored energy flow log');
            } catch (\Exception $e) {
                Log::error('Error storing energy flow log: ' . $e->getMessage());
                Log::error('Stack trace: ' . $e->getTraceAsString());
            }
        } else {
            Log::warning('Cannot store energy flow log: Missing data', [
                'has_sunsync_data' => !empty($sunSyncData),
                'has_zappi_data' => !empty($zappiData)
            ]);
        }

        // Mask sensitive data
        $maskedSunSyncData = $this->dataMaskingService->maskSensitiveData($sunSyncData ?? []);
        $maskedZappiData = $this->dataMaskingService->maskSensitiveData($zappiData ?? []);
        $maskedPlantInfo = $plantInfo ? $this->dataMaskingService->maskSensitiveData($plantInfo) : null;
        $maskedSunSyncApiRequests = array_map(
            fn($request) => $this->dataMaskingService->maskSensitiveData($request),
            $sunSyncApiRequests
        );
        $maskedZappiApiRequests = array_map(
            fn($request) => $this->dataMaskingService->maskSensitiveData($request),
            $zappiApiRequests
        );

        return view('home', [
            'sunSyncData' => $maskedSunSyncData,
            'zappiData' => $maskedZappiData,
            'plantInfo' => $maskedPlantInfo,
            'sunSyncApiRequests' => $maskedSunSyncApiRequests,
            'zappiApiRequests' => $maskedZappiApiRequests
        ]);
    }

    private function storeEnergyFlowLog($sunSyncData, $zappiData)
    {
        try {
            Log::info('Starting storeEnergyFlowLog method');
            Log::info('SunSync Data received:', $sunSyncData);
            Log::info('Zappi Data received:', $zappiData);

            // Get SunSync timestamp from plantInfo
            $sunSyncTimestamp = null;
            if (isset($sunSyncData['updateAt'])) {
                $sunSyncTimestamp = \Carbon\Carbon::parse($sunSyncData['updateAt']);
            } elseif (isset($sunSyncData['plantInfo']['updateAt'])) {
                $sunSyncTimestamp = \Carbon\Carbon::parse($sunSyncData['plantInfo']['updateAt']);
            }

            // Convert Zappi timestamp to Carbon instance
            $zappiTimestamp = null;
            if (isset($zappiData['zappi'][0]['dat']) && isset($zappiData['zappi'][0]['tim'])) {
                $zappiTimestamp = \Carbon\Carbon::createFromFormat(
                    'd-m-Y H:i:s',
                    $zappiData['zappi'][0]['dat'] . ' ' . $zappiData['zappi'][0]['tim'],
                    'UTC'
                )->setTimezone('Europe/London');
            }

            // Log the timestamps for debugging
            Log::info('Timestamps being stored:', [
                'sunsync_updated_at' => $sunSyncTimestamp,
                'zappi_updated_at' => $zappiTimestamp,
                'sunsync_data_keys' => array_keys($sunSyncData)
            ]);

            // Calculate the values
            $homeLoadPower = $sunSyncData['homeLoadPower'] ?? 0;
            $upsLoadPower = $sunSyncData['upsLoadPower'] ?? 0;
            $smartLoadPower = $sunSyncData['smartLoadPower'] ?? 0;
            $zappiDiv = $zappiData['zappi'][0]['div'] ?? 0;
            $zappiGrd = $zappiData['zappi'][0]['grd'] ?? 0;
            $zappiGen = $zappiData['zappi'][0]['gen'] ?? 0;

            $data = [
                'pv1_power' => $sunSyncData['pv'][0]['power'] ?? 0,
                'pv2_power' => $sunSyncData['pv'][1]['power'] ?? 0,
                'total_pv_power' => ($sunSyncData['pv'][0]['power'] ?? 0) + ($sunSyncData['pv'][1]['power'] ?? 0),
                'grid_power' => $zappiGrd ?? 0,
                'grid_power_sunsync' => ($sunSyncData['toGrid'] ?? true) ? -abs($sunSyncData['gridOrMeterPower'] ?? 0) : abs($sunSyncData['gridOrMeterPower'] ?? 0),
                'battery_power' => ($sunSyncData['toBat'] ?? true) ? -abs($sunSyncData['battPower'] ?? 0) : abs($sunSyncData['battPower'] ?? 0),
                'battery_soc' => $sunSyncData['soc'] ?? 0,
                'ups_load_power' => $upsLoadPower,
                'smart_load_power' => $smartLoadPower,
                'home_load_power' => $zappiGrd + $zappiGen - $zappiDiv,
                'home_load_sunsync' => $homeLoadPower - $zappiDiv,
                'combined_load_node_sunsync' => $upsLoadPower + $smartLoadPower + $homeLoadPower - $zappiDiv,
                'combined_load_node' => $upsLoadPower + $smartLoadPower + $zappiGrd + $zappiGen - $zappiDiv,
                'zappi_node' => $zappiDiv,
                'car_node_connection' => $zappiData['zappi'][0]['pst'] ?? null,
                'car_node_Mode' => $zappiData['zappi'][0]['zmo'] ?? null,
                'car_node_status' => $zappiData['zappi'][0]['sta'] ?? null,
                'last_consumption' => $zappiData['zappi'][0]['che'] ?? 0,
                'sunsync_updated_at' => $sunSyncTimestamp,
                'zappi_updated_at' => $zappiTimestamp
            ];

            Log::info('Data being stored:', $data);

            $energyFlowLog = EnergyFlowLog::create($data);
            Log::info('Successfully created EnergyFlowLog record:', $energyFlowLog->toArray());

        } catch (\Exception $e) {
            Log::error('Error storing energy flow log: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e; // Re-throw the exception to be caught by the caller
        }
    }
} 
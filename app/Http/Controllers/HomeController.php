<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SunSyncService;
use App\Services\MyEnergiApiService;
use App\Services\DataMaskingService;
use App\Services\EnergyFlowService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    private SunSyncService $sunSyncService;
    private MyEnergiApiService $myEnergiApiService;
    private DataMaskingService $dataMaskingService;
    private EnergyFlowService $energyFlowService;

    public function __construct(
        SunSyncService $sunSyncService,
        MyEnergiApiService $myEnergiApiService,
        DataMaskingService $dataMaskingService,
        EnergyFlowService $energyFlowService
    ) {
        $this->sunSyncService = $sunSyncService;
        $this->myEnergiApiService = $myEnergiApiService;
        $this->dataMaskingService = $dataMaskingService;
        $this->energyFlowService = $energyFlowService;
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
                $energyFlowLog = $this->energyFlowService->logEnergyFlow($sunSyncData, $zappiData);
                Log::info('Successfully stored energy flow log with ID: ' . $energyFlowLog->id);
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

        // Get inverter info and settings for status display
        $inverterSettings = null;
        $inverterInfo = null;
        try {
            if ($plantInfo && isset($plantInfo['id'])) {
                $inverterInfo = $this->sunSyncService->getInverterInfo($plantInfo['id']);
                if ($inverterInfo && isset($inverterInfo['sn'])) {
                    $inverterSettings = $this->sunSyncService->getInverterSettings($inverterInfo['sn']);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to get inverter settings for display', [
                'error' => $e->getMessage()
            ]);
        }

        return view('home', [
            'sunSyncData' => $maskedSunSyncData,
            'zappiData' => $maskedZappiData,
            'plantInfo' => $maskedPlantInfo,
            'sunSyncApiRequests' => $maskedSunSyncApiRequests,
            'zappiApiRequests' => $maskedZappiApiRequests,
            'inverterInfo' => $inverterInfo,
            'inverterSettings' => $inverterSettings
        ]);
    }
} 
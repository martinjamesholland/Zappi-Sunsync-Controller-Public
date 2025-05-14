<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SunSyncService;
use App\Services\MyEnergiApiService;
use App\Services\DataMaskingService;
use Illuminate\View\View;

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
                    }
                }
            }
            $sunSyncApiRequests = $this->sunSyncService->getApiRequests();
        }

        // Get Zappi data
        $zappiData = $this->myEnergiApiService->getStatus();
        $zappiApiRequests = $this->myEnergiApiService->getApiRequests();

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
} 
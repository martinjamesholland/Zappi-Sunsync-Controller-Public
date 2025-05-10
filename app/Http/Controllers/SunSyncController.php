<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SunSyncService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SunSyncController extends Controller
{
    private SunSyncService $sunSyncService;

    public function __construct(SunSyncService $sunSyncService)
    {
        $this->sunSyncService = $sunSyncService;
    }

    public function dashboard(): View
    {
        // Get credentials from environment variables
        $username = config('services.sunsync.username');
        $password = config('services.sunsync.password');

        if (empty($username) || empty($password)) {
            return view('sunsync.error', [
                'message' => 'SunSync credentials are not configured. Please go to Settings and add your SunSync username and password.',
                'showSettingsLink' => true
            ]);
        }

        // Authenticate
        $authResponse = $this->sunSyncService->authenticate($username, $password);
        if (!$authResponse || isset($authResponse['error'])) {
            $errorMessage = 'Failed to authenticate with SunSync. Please check your credentials in Settings.';
            if (isset($authResponse['message'])) {
                $errorMessage .= ' API Error: ' . $authResponse['message'];
            }
            return view('sunsync.error', [
                'message' => $errorMessage,
                'showSettingsLink' => true,
                'apiError' => $authResponse['details'] ?? null
            ]);
        }

        // Get plant info
        $plantInfo = $this->sunSyncService->getPlantInfo();
        if (!$plantInfo) {
            return view('sunsync.error', [
                'message' => 'Failed to get plant information from SunSync. Please check your credentials in Settings.',
                'showSettingsLink' => true
            ]);
        }

        // Get inverter info
        $inverterInfo = $this->sunSyncService->getInverterInfo($plantInfo['id']);
        if (!$inverterInfo) {
            return view('sunsync.error', [
                'message' => 'Failed to get inverter information from SunSync. Please check your credentials in Settings.',
                'showSettingsLink' => true
            ]);
        }

        // Get inverter settings
        $inverterSettings = $this->sunSyncService->getInverterSettings($inverterInfo['sn']);
        if (!$inverterSettings) {
            return view('sunsync.error', [
                'message' => 'Failed to get inverter settings from SunSync. Please check your credentials in Settings.',
                'showSettingsLink' => true
            ]);
        }
        
        // Get inverter flow information
        $inverterFlowInfo = $this->sunSyncService->getInverterFlowInfo($inverterInfo['sn']);
        if (!$inverterFlowInfo) {
            return view('sunsync.error', [
                'message' => 'Failed to get inverter flow information from SunSync. Please check your credentials in Settings.',
                'showSettingsLink' => true
            ]);
        }

        return view('sunsync.dashboard', [
            'plantInfo' => $plantInfo,
            'inverterInfo' => $inverterInfo,
            'inverterSettings' => $inverterSettings,
            'inverterFlowInfo' => $inverterFlowInfo,
            'authResponse' => $authResponse,
            'apiRequests' => $this->sunSyncService->getApiRequests()
        ]);
    }
} 
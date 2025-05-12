<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MyEnergiApiService;
use App\Services\SunSyncService;
use App\Services\EvChargingSettingsService;
use App\Services\DataMaskingService;
use App\Models\SunSyncSetting;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

class EvChargingController extends Controller
{
    private MyEnergiApiService $myEnergiApiService;
    private SunSyncService $sunSyncService;
    private EvChargingSettingsService $settingsService;
    private DataMaskingService $dataMaskingService;

    public function __construct(
        MyEnergiApiService $myEnergiApiService,
        SunSyncService $sunSyncService,
        EvChargingSettingsService $settingsService,
        DataMaskingService $dataMaskingService
    ) {
        $this->myEnergiApiService = $myEnergiApiService;
        $this->sunSyncService = $sunSyncService;
        $this->settingsService = $settingsService;
        $this->dataMaskingService = $dataMaskingService;
    }

    public function updateSystemMode(): View|JsonResponse
    {
        $logs = [];
        $apiCalls = [];
        $currentTime = Carbon::now();
        $isTestMode = request()->has('test_mode');
        $isCronMode = request()->has('cron_mode');

        // Check for required credentials
        $zappiSerial = config('myenergi.serial', env('ZAPPI_SERIAL'));
        $zappiPassword = config('myenergi.password', env('ZAPPI_PASSWORD'));
        $sunSyncUsername = config('services.sunsync.username');
        $sunSyncPassword = config('services.sunsync.password');

        // Validate APP_KEY for test mode
        if ($isTestMode) {
            $providedKey = request()->input('app_key');
            $appKey = config('app.key');
            
            if (!$providedKey || $providedKey !== $appKey) {
                $logs[] = "Error: Invalid or missing APP_KEY for test mode";
                return $this->handleResponse($isCronMode, $logs, $apiCalls, false, 'Invalid or missing APP_KEY. Test mode requires valid APP_KEY.');
            }
        }

        if (empty($zappiSerial) || empty($zappiPassword)) {
            $logs[] = "Error: Zappi credentials are not configured";
            return $this->handleResponse($isCronMode, $logs, $apiCalls, false, 'Zappi credentials are not configured. Please go to Settings and add your Zappi serial number and API key.');
        }

        if (empty($sunSyncUsername) || empty($sunSyncPassword)) {
            $logs[] = "Error: SunSync credentials are not configured";
            return $this->handleResponse($isCronMode, $logs, $apiCalls, false, 'SunSync credentials are not configured. Please go to Settings and add your SunSync username and password.');
        }
        
        // Get settings from CSV
        $settings = $this->settingsService->getSettings();
        
        // Get Zappi status
        $zappiStatus = $this->myEnergiApiService->getStatus();
        $apiCalls[] = [
            'name' => 'MyEnergi API - Get Zappi Status',
            'endpoint' => 'GET /api/zappi/status',
            'request' => null,
            'response' => $zappiStatus
        ];
        $logs[] = "Retrieved Zappi status at " . $currentTime->format('Y-m-d H:i:s');
        
        if ($isTestMode) {
            $logs[] = "TEST MODE: Simulating Zappi charging status";
            $isCharging = true;
        } else {
            if (!isset($zappiStatus['zappi']) || empty($zappiStatus['zappi'])) {
                $logs[] = "Error: No Zappi data available";
                return $this->handleResponse($isCronMode, $logs, $apiCalls, false, 'Failed to get Zappi status. Please check your Zappi credentials in Settings.');
            }

            $zappi = $zappiStatus['zappi'][0];
            $isCharging = ($zappi['pst'] ?? '') === 'C2';
        }
        
        $logs[] = "EV Status: " . ($isCharging ? "Charging" : "Not Charging");

        // Check if current time is between night start and end times
        $nightStart = $settings['night_start'] ?: '23:30';
        $nightEnd = $settings['night_end'] ?: '05:30';
        
        // Ensure time values are in correct format
        if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $nightStart)) {
            $nightStart = '23:30';
        }
        if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $nightEnd)) {
            $nightEnd = '05:30';
        }
        
        try {
            $isNightTime = $currentTime->between(
                $currentTime->copy()->setTimeFromTimeString($nightStart),
                $currentTime->copy()->addDay()->setTimeFromTimeString($nightEnd)
            );
        } catch (\Exception $e) {
            Log::error('Error parsing time values', [
                'night_start' => $nightStart,
                'night_end' => $nightEnd,
                'error' => $e->getMessage()
            ]);
            $isNightTime = false;
        }
        $logs[] = "Time Check: " . ($isNightTime ? "Night Time ({$nightStart}-{$nightEnd})" : "Day Time");

        // Define default values from settings
        $defaultSettings = [
            'sellTime5' => $settings['default_sell_time'] ?: '22:00',
            'cap5' => $settings['default_cap'] ?: '20',
            'time5on' => "false"
        ];

        // Try to get cached settings first
        $cachedRecord = SunSyncSetting::where('last_updated', '>=', Carbon::now()->subMinutes(4))
            ->latest()
            ->first();

        if ($cachedRecord) {
            $currentSettings = $cachedRecord->settings;
            $inverterSn = $cachedRecord->inverter_sn;
            $logs[] = "Using cached settings from " . $cachedRecord->last_updated->format('Y-m-d H:i:s');
        } else {
            // Get plant and inverter info
            $plantInfo = $this->sunSyncService->getPlantInfo();
            $apiCalls[] = [
                'name' => 'SunSync API - Get Plant Info',
                'endpoint' => 'GET /api/v1/plants',
                'request' => null,
                'response' => $plantInfo
            ];
            
            // Add a debug log entry
            $logs[] = "Debug: Plant Info API Call added to apiCalls array - " . (is_null($plantInfo) ? "Response is NULL" : "Response has data");
            
            if (!$plantInfo) {
                $logs[] = "Error: Failed to get plant information";
                return $this->handleResponse($isCronMode, $logs, $apiCalls, false, 'Failed to get SunSync plant information. Please check your SunSync credentials in Settings.');
            }

            $inverterInfo = $this->sunSyncService->getInverterInfo($plantInfo['id']);
            $apiCalls[] = [
                'name' => 'SunSync API - Get Inverter Info',
                'endpoint' => "GET /api/v1/plant/{$plantInfo['id']}/inverters",
                'request' => ['plantId' => $plantInfo['id']],
                'response' => $inverterInfo
            ];
            if (!$inverterInfo) {
                $logs[] = "Error: Failed to get inverter information";
                return $this->handleResponse($isCronMode, $logs, $apiCalls, false, 'Failed to get SunSync inverter information. Please check your SunSync credentials in Settings.');
            }

            $inverterSn = $inverterInfo['sn'];
            $currentSettings = $this->sunSyncService->getInverterSettings($inverterSn);
            $apiCalls[] = [
                'name' => 'SunSync API - Get Inverter Settings',
                'endpoint' => "GET /api/v1/common/setting/{$inverterSn}/read",
                'request' => ['inverterSn' => $inverterSn],
                'response' => $currentSettings
            ];
        }

        if (!$currentSettings) {
            $logs[] = "Error: Failed to get current inverter settings";
            return $this->handleResponse($isCronMode, $logs, $apiCalls, false, 'Failed to get SunSync inverter settings. Please check your SunSync credentials in Settings.');
        }

        // Prepare new settings
        $newSettings = $currentSettings;
        
        if ($isCharging && !$isNightTime) {
            // Round down current time to nearest 30 minutes
            $roundedTime = $currentTime->copy()->setMinute(floor($currentTime->minute / 30) * 30);
            $newSettings['sellTime5'] = $roundedTime->format('H:i');
            $newSettings['cap5'] = "100";
            $newSettings['time5on'] = true;  // Boolean true
            $logs[] = "Updating settings for charging during day time:";
            $logs[] = "- sellTime5 set to " . $newSettings['sellTime5'];
            $logs[] = "- cap5 set to " . $newSettings['cap5'];
            $logs[] = "- time5on set to " . ($newSettings['time5on'] ? "true" : "false");
        } else {
            // Check if current settings already match default values
            $isAlreadyDefault = $currentSettings['sellTime5'] === $defaultSettings['sellTime5'] &&
                               $currentSettings['cap5'] === $defaultSettings['cap5'] &&
                               $currentSettings['time5on'] === $defaultSettings['time5on'];

            if ($isAlreadyDefault) {
                $logs[] = "Settings already at default values, no update needed";
                return $this->handleResponse($isCronMode, $logs, $apiCalls, true);
            }

            // Reset to default values
            $newSettings['sellTime5'] = $defaultSettings['sellTime5'];
            $newSettings['cap5'] = $defaultSettings['cap5'];
            $newSettings['time5on'] = $defaultSettings['time5on'];
            $logs[] = "Resetting settings to default values:";
            $logs[] = "- sellTime5 set to " . $newSettings['sellTime5'];
            $logs[] = "- cap5 set to " . $newSettings['cap5'];
            $logs[] = "- time5on set to " . $newSettings['time5on'];
        }

        // Always get current settings from API before making changes
        $apiCurrentSettings = $this->sunSyncService->getInverterSettings($inverterSn);
        $apiCalls[] = [
            'name' => 'SunSync API - Get Current Settings',
            'endpoint' => "GET /api/v1/common/setting/{$inverterSn}/read",
            'request' => ['inverterSn' => $inverterSn],
            'response' => $apiCurrentSettings
        ];

        if (!$apiCurrentSettings) {
            $logs[] = "Error: Failed to get current settings from API";
            return $this->handleResponse($isCronMode, $logs, $apiCalls, false, 'Failed to get current SunSync settings. Please check your SunSync credentials in Settings.');
        }

        // Update settings if they have changed
        if ($newSettings['sellTime5'] !== $apiCurrentSettings['sellTime5'] ||
            $newSettings['cap5'] !== $apiCurrentSettings['cap5'] ||
            $newSettings['time5on'] !== $apiCurrentSettings['time5on']) {
            
            // Only send the settings we're changing
            $updateSettings = [
                'sn' => $inverterSn,
                'sellTime5' => $newSettings['sellTime5'],
                'cap5' => $newSettings['cap5'],
                'time5on' => $newSettings['time5on']  // Will be boolean true or string "false"
            ];
            
            $success = $this->sunSyncService->updateSystemModeSettings($inverterSn, $updateSettings);
            $apiCalls[] = [
                'name' => 'SunSync API - Update System Mode Settings',
                'endpoint' => "POST /api/v1/common/setting/{$inverterSn}/set",
                'request' => $updateSettings,
                'response' => ['success' => $success]
            ];
            $logs[] = "Settings update " . ($success ? "successful" : "failed");
            return $this->handleResponse($isCronMode, $logs, $apiCalls, $success);
        } else {
            $logs[] = "No settings update needed - values already match";
            return $this->handleResponse($isCronMode, $logs, $apiCalls, true);
        }
    }

    public function updateSettings(): View|JsonResponse|RedirectResponse
    {
        // Get current settings first
        $currentSettings = $this->settingsService->getSettings();
        
        // Only update the fields that were submitted
        $settings = $currentSettings;
        if (request()->has('default_sell_time')) {
            $settings['default_sell_time'] = request('default_sell_time');
        }
        if (request()->has('default_cap')) {
            $settings['default_cap'] = request('default_cap');
        }
        if (request()->has('night_start')) {
            $settings['night_start'] = request('night_start');
        }
        if (request()->has('night_end')) {
            $settings['night_end'] = request('night_end');
        }

        // Validate time formats for any time fields that were submitted
        foreach (['default_sell_time', 'night_start', 'night_end'] as $timeField) {
            if (request()->has($timeField) && !preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $settings[$timeField])) {
                return redirect()->route('ev-charging.status')
                    ->with('error', "Invalid time format for {$timeField}. Please use HH:MM format.");
            }
        }

        // Validate cap value if it was submitted
        if (request()->has('default_cap') && (!is_numeric($settings['default_cap']) || $settings['default_cap'] < 0 || $settings['default_cap'] > 100)) {
            return redirect()->route('ev-charging.status')
                ->with('error', 'Default cap must be a number between 0 and 100.');
        }

        $success = $this->settingsService->updateSettings($settings);

        if (request()->has('cron_mode')) {
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Settings updated successfully' : 'Failed to update settings',
                'settings' => $this->settingsService->getSettings()
            ]);
        }

        return redirect()->route('ev-charging.status')
            ->with($success ? 'success' : 'error', 
                  $success ? 'Settings updated successfully' : 'Failed to update settings');
    }

    private function handleResponse(bool $isCronMode, array $logs, array $apiCalls, bool $success, ?string $errorMessage = null): View|JsonResponse
    {
        // Mask sensitive data in API calls
        $maskedApiCalls = [];
        foreach ($apiCalls as $apiCall) {
            $maskedApiCall = $this->dataMaskingService->maskSensitiveData($apiCall);
            // Ensure response is formatted correctly even if null
            if (!isset($maskedApiCall['response']) || $maskedApiCall['response'] === null) {
                $maskedApiCall['response'] = [];
            }
            $maskedApiCalls[] = $maskedApiCall;
        }
        
        if ($isCronMode) {
            return response()->json([
                'success' => $success,
                'message' => $errorMessage,
                'logs' => $logs,
                'apiCalls' => $maskedApiCalls
            ], $success ? 200 : 400);
        }

        if (request()->ajax()) {
            return response()->json([
                'success' => $success,
                'message' => $errorMessage,
                'logs' => $logs,
                'apiCalls' => $maskedApiCalls
            ], $success ? 200 : 400);
        }

        return view('ev-charging.status', [
            'success' => $success,
            'message' => $errorMessage,
            'logs' => $logs,
            'apiCalls' => $maskedApiCalls,
            'settings' => $this->settingsService->getSettings()
        ]);
    }
} 
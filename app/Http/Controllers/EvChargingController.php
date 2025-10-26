<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MyEnergiApiService;
use App\Services\SunSyncService;
use App\Services\EvChargingSettingsService;
use App\Services\DataMaskingService;
use App\Services\BatteryDischargeService;
use App\Models\SunSyncSetting;
use App\Http\Requests\UpdateEvSettingsRequest;
use App\Enums\ZappiStatus;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;

class EvChargingController extends Controller
{
    private MyEnergiApiService $myEnergiApiService;
    private SunSyncService $sunSyncService;
    private EvChargingSettingsService $settingsService;
    private DataMaskingService $dataMaskingService;
    private BatteryDischargeService $batteryDischargeService;

    public function __construct(
        MyEnergiApiService $myEnergiApiService,
        SunSyncService $sunSyncService,
        EvChargingSettingsService $settingsService,
        DataMaskingService $dataMaskingService,
        BatteryDischargeService $batteryDischargeService
    ) {
        $this->myEnergiApiService = $myEnergiApiService;
        $this->sunSyncService = $sunSyncService;
        $this->settingsService = $settingsService;
        $this->dataMaskingService = $dataMaskingService;
        $this->batteryDischargeService = $batteryDischargeService;
    }

    public function updateSystemMode(): View|JsonResponse|Response
    {
        $logs = [];
        $apiCalls = [];
        $currentTime = Carbon::now()->timezone('Europe/London');
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
            $zappiStatusEnum = ZappiStatus::fromValueOrNull($zappi['pst'] ?? null);
            $isCharging = $zappiStatusEnum?->isCharging() ?? false;
        }
        
        $logs[] = "EV Status: " . ($isCharging ? "Charging" : "Not Charging");

        // Check if current time is between night start and end times
        $nightStart = $settings['night_start'] ?: config('sunsync.defaults.night_start', '23:30');
        $nightEnd = $settings['night_end'] ?: config('sunsync.defaults.night_end', '05:30');
        
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
            'sellTime5' => $settings['default_sell_time'] ?: config('sunsync.defaults.sell_time', '22:00'),
            'cap5' => $settings['default_cap'] ?: config('sunsync.defaults.cap', '20'),
            'time5on' => "false"
        ];

        // Get plant and inverter info - always fetch fresh data
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
            
            // Add diagnostic information
            try {
                // Check if we can access the API at all
                $testResponse = Http::timeout(10)->get('https://api.sunsynk.net/status');
                if ($testResponse->successful()) {
                    $logs[] = "Debug: SunSync API appears to be online but authentication may have failed";
                } else {
                    $logs[] = "Debug: SunSync API appears to be offline or unreachable (HTTP " . $testResponse->status() . ")";
                }
            } catch (\Exception $e) {
                $logs[] = "Debug: SunSync API connection error: " . $e->getMessage();
            }
            
            // Check credentials
            $username = config('services.sunsync.username');
            $logs[] = "Debug: SunSync username is " . (empty($username) ? "empty" : "configured");
            
            // Suggest solutions
            $logs[] = "Troubleshooting steps:";
            $logs[] = "1. Check your internet connection";
            $logs[] = "2. Verify SunSync credentials in Settings";
            $logs[] = "3. Try again later as the API might be temporarily unavailable";
            
            return $this->handleResponse($isCronMode, $logs, $apiCalls, false, 'Failed to get SunSync plant information. Please check your SunSync credentials in Settings or try again later.');
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

        if (!$currentSettings) {
            $logs[] = "Error: Failed to get current inverter settings";
            return $this->handleResponse($isCronMode, $logs, $apiCalls, false, 'Failed to get SunSync inverter settings. Please check your SunSync credentials in Settings.');
        }

        // ========================================================================
        // BATTERY DISCHARGE TO GRID LOGIC (PRIORITY OVER EV CHARGING)
        // ========================================================================
        
        if ($this->batteryDischargeService->isDischargeEnabled()) {
            $logs[] = "Battery discharge feature is enabled - checking discharge rules";
            
            // Get current battery SOC
            $currentSoc = $this->batteryDischargeService->getCurrentBatterySoc();
            $apiCalls[] = [
                'name' => 'SunSync API - Get Battery SOC',
                'endpoint' => "GET /api/v1/inverter/{$inverterSn}/flow",
                'request' => ['inverterSn' => $inverterSn],
                'response' => ['soc' => $currentSoc]
            ];
            
            if ($currentSoc === null) {
                $logs[] = "Warning: Unable to get current battery SOC - skipping discharge logic";
            } else {
                $logs[] = "Current battery SOC: {$currentSoc}%";
                
                // Check if we should enable discharge
                $dischargeDecision = $this->batteryDischargeService->shouldEnableDischarge(
                    $zappiStatus,
                    $currentSoc,
                    $currentTime
                );
                
                $logs[] = "Discharge decision: " . ($dischargeDecision['shouldDischarge'] ? 'ENABLE' : 'DISABLE');
                $logs[] = "Reason: " . $dischargeDecision['reason'];
                
                if ($dischargeDecision['startTime']) {
                    $logs[] = "Calculated discharge window: {$dischargeDecision['startTime']->format('H:i')} - {$dischargeDecision['stopTime']->format('H:i')}";
                }
                
                if ($dischargeDecision['shouldDischarge']) {
                    // Enable discharge mode
                    $dischargeToSoc = (int) ($settings['discharge_to_soc'] ?? 20);
                    $logs[] = "Enabling discharge mode (discharge to {$dischargeToSoc}% SOC)";
                    
                    $success = $this->sunSyncService->enableDischargeMode($inverterSn, $dischargeToSoc);
                    $apiCalls[] = [
                        'name' => 'SunSync API - Enable Discharge Mode',
                        'endpoint' => "POST /api/v1/common/setting/{$inverterSn}/set",
                        'request' => [
                            'sysWorkMode' => '0',
                            'discharge_to_soc' => $dischargeToSoc
                        ],
                        'response' => ['success' => $success]
                    ];
                    
                    if ($success) {
                        $logs[] = "Discharge mode enabled successfully";
                        return $this->handleResponse($isCronMode, $logs, $apiCalls, true);
                    } else {
                        $logs[] = "Error: Failed to enable discharge mode";
                        return $this->handleResponse($isCronMode, $logs, $apiCalls, false, 'Failed to enable discharge mode');
                    }
                } else {
                    // Check if we need to return to normal mode
                    $currentWorkMode = $currentSettings['sysWorkMode'] ?? '2';
                    
                    if ($currentWorkMode === '0') {
                        // Currently in discharge mode, need to return to normal
                        $logs[] = "Currently in discharge mode - returning to normal mode";
                        
                        $success = $this->sunSyncService->disableDischargeMode($inverterSn, $settings);
                        $apiCalls[] = [
                            'name' => 'SunSync API - Disable Discharge Mode (Return to Normal)',
                            'endpoint' => "POST /api/v1/common/setting/{$inverterSn}/set",
                            'request' => [
                                'sysWorkMode' => '2'
                            ],
                            'response' => ['success' => $success]
                        ];
                        
                        if ($success) {
                            $logs[] = "Returned to normal mode successfully";
                            return $this->handleResponse($isCronMode, $logs, $apiCalls, true);
                        } else {
                            $logs[] = "Error: Failed to return to normal mode";
                            return $this->handleResponse($isCronMode, $logs, $apiCalls, false, 'Failed to return to normal mode');
                        }
                    } else {
                        $logs[] = "Not in discharge mode and conditions not met - no action needed";
                        // Continue to EV charging logic below
                    }
                }
            }
        } else {
            $logs[] = "Battery discharge feature is disabled - proceeding with EV charging logic";
        }
        
        // ========================================================================
        // EV CHARGING LOGIC (ONLY IF NOT IN DISCHARGE MODE)
        // ========================================================================
        
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
                               $currentSettings['time5on'] === $defaultSettings['time5on'] &&
                               $currentSettings['sellTime2'] === '03:00';

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

        // Check if the specific settings (sn, sellTime5, cap5, time5on) are different
        $settingsChanged = false;

      
        
        // Compare serial number
        if ($inverterSn !== ($apiCurrentSettings['sn'] ?? '')) {
            $settingsChanged = true;
            $logs[] = "Serial number mismatch: {$inverterSn} vs " . ($apiCurrentSettings['sn'] ?? 'not set');
        }

        // Compare sellTime2
        if ('03:00' !== ($apiCurrentSettings['sellTime2'] ?? '')) {
            $settingsChanged = true;
            $logs[] = "Time 2 mismatch: 03:00 vs " . ($apiCurrentSettings['sellTime2'] ?? 'not set');
        }
        
        // Compare sellTime5
        if ($newSettings['sellTime5'] !== ($apiCurrentSettings['sellTime5'] ?? '')) {
            $settingsChanged = true;
            $logs[] = "Sell time mismatch: {$newSettings['sellTime5']} vs " . ($apiCurrentSettings['sellTime5'] ?? 'not set');
        }
        
        // Compare cap5
        if ($newSettings['cap5'] !== ($apiCurrentSettings['cap5'] ?? '')) {
            $settingsChanged = true;
            $logs[] = "Cap mismatch: {$newSettings['cap5']} vs " . ($apiCurrentSettings['cap5'] ?? 'not set');
        }
        
        // Compare time5on with special handling for boolean vs string values
        $newTime5onValue = $newSettings['time5on'];
        $currentTime5onValue = $apiCurrentSettings['time5on'] ?? false;
        
        // Normalize both to boolean for comparison
        $newTime5onBool = is_bool($newTime5onValue) ? $newTime5onValue : ($newTime5onValue === 'true' || $newTime5onValue === '1' || $newTime5onValue === 1);
        $currentTime5onBool = is_bool($currentTime5onValue) ? $currentTime5onValue : ($currentTime5onValue === 'true' || $currentTime5onValue === '1' || $currentTime5onValue === 1);
        
        if ($newTime5onBool !== $currentTime5onBool) {
            $settingsChanged = true;
            $logs[] = "Time5on mismatch: " . ($newTime5onBool ? 'true' : 'false') . " vs " . ($currentTime5onBool ? 'true' : 'false');
        }

        // Update settings if they have changed
        if ($settingsChanged) {
            // Only send the settings we're changing
            $updateSettings = [
                'sn' => $inverterSn,

                "sellTime1" => $settings['sell_time_1'] ?? "00:00",
                "sellTime2" => $settings['sell_time_2'] ?? "02:00",
                "sellTime3" => $settings['sell_time_3'] ?? "04:30",
                "sellTime4" => $settings['sell_time_4'] ?? "05:30",
                "sellTime5" => $newSettings['sellTime5'],
                "sellTime6" => $settings['sell_time_6'] ?? "23:30",

                "sellTime1Pac" => "5000",
                "sellTime2Pac" => "5000",
                "sellTime3Pac" => "5000",
                "sellTime4Pac" => "5000",
                "sellTime5Pac" => "5000",
                "sellTime6Pac" => "5000",

                "cap1" => $settings['cap_1'] ?? "100",
                "cap2" => $settings['cap_2'] ?? "100",
                "cap3" => $settings['cap_3'] ?? "100",
                "cap4" => $settings['cap_4'] ?? "25",
                "cap5" => $newSettings['cap5'],
                "cap6" => $settings['cap_6'] ?? "100",

                "sellTime1Volt" => "49",
                "sellTime2Volt" => "49",
                "sellTime3Volt" => "49",
                "sellTime4Volt" => "49",
                "sellTime5Volt" => "49",
                "sellTime6Volt" => "49",

                "mondayOn" => true,
                "tuesdayOn" => true,
                "wednesdayOn" => true,
                "thursdayOn" => true,
                "fridayOn" => true,
                "saturdayOn" => true,
                "sundayOn" => true,

                "time1on" => ($settings['time_1_on'] ?? 'true') === 'true' ? true : "false",
                "time2on" => ($settings['time_2_on'] ?? 'true') === 'true' ? true : "false",
                "time3on" => ($settings['time_3_on'] ?? 'true') === 'true' ? true : "false",
                "time4on" => ($settings['time_4_on'] ?? 'false') === 'true' ? true : "false", 
                "time5on" => $newSettings['time5on'],  // Will be boolean true or string "false"
                "time6on" => ($settings['time_6_on'] ?? 'true') === 'true' ? true : "false",

                "genTime1on" => "false",
                "genTime2on" => "false",
                "genTime3on" => "false",
                "genTime4on" => "false",
                "genTime5on" => "false",
                "genTime6on" => "false"
            ];
            
            $logs[] = "Settings have changed, updating...";
            $logs[] = "- sn: " . $inverterSn . " (current: " . ($apiCurrentSettings['sn'] ?? 'not set') . ")";
            $logs[] = "- sellTime5: " . $newSettings['sellTime5'] . " (current: " . ($apiCurrentSettings['sellTime5'] ?? 'not set') . ")";
            $logs[] = "- cap5: " . $newSettings['cap5'] . " (current: " . ($apiCurrentSettings['cap5'] ?? 'not set') . ")";
            $logs[] = "- time5on: " . ($newSettings['time5on'] ? 'true' : 'false') . " (current: " . (($apiCurrentSettings['time5on'] ?? false) ? 'true' : 'false') . ")";
            
            $success = $this->sunSyncService->updateSystemModeSettings($inverterSn, $updateSettings);
            $apiCalls[] = [
                'name' => 'SunSync API - Update System Mode Settings',
                'endpoint' => "POST /api/v1/common/setting/{$inverterSn}/set",
                'request' => $updateSettings,
                'response' => ['success' => $success]
            ];
            sleep(1);
            $success = $this->sunSyncService->updateSystemModeSettings($inverterSn, $updateSettings);
            
            $apiCalls[] = [
                'name' => 'SunSync API - Update System Mode Settings',
                'endpoint' => "POST /api/v1/common/setting/{$inverterSn}/set",
                'request' => $updateSettings,
                'response' => ['success' => $success]
            ];
            $logs[] = "Settings update " . ($success ? "successful" : "failed");
            $logs[] = "=== API CALLS SUMMARY ===";
            foreach ($apiCalls as $index => $apiCall) {
                $logs[] = "API Call " . ($index + 1) . ": " . ($apiCall['name'] ?? 'Unknown');
                $logs[] = "  Endpoint: " . ($apiCall['endpoint'] ?? 'N/A');
                $logs[] = "  Request: " . json_encode($apiCall['request'] ?? []);
                $logs[] = "  Response: " . json_encode($apiCall['response'] ?? []);
            }
            $logs[] = "=== END API CALLS ===";
            return $this->handleResponse($isCronMode, $logs, $apiCalls, $success);
        } else {
            $logs[] = "No settings update needed - all values already match:";
            $logs[] = "- sn: " . $inverterSn;
            $logs[] = "- sellTime5: " . $newSettings['sellTime5'];
            $logs[] = "- cap5: " . $newSettings['cap5'];
            $logs[] = "- time5on: " . ($newTime5onBool ? 'true' : 'false');
            return $this->handleResponse($isCronMode, $logs, $apiCalls, true);
        }
    }

    public function updateSettings(UpdateEvSettingsRequest $request): View|JsonResponse|RedirectResponse|Response
    {
        // Get validated data
        $validated = $request->validated();
        
        // Get current settings first
        $currentSettings = $this->settingsService->getSettings();
        
        // Handle checkbox values - checkboxes not sent when unchecked
        // Convert to 'true'/'false' strings for consistency with API
        foreach (['time_1_on', 'time_2_on', 'time_3_on', 'time_4_on', 'time_6_on'] as $timeOnField) {
            if (array_key_exists($timeOnField, $validated)) {
                $validated[$timeOnField] = $validated[$timeOnField] ? 'true' : 'false';
            } elseif ($request->has('sell_time_1') || $request->has('sell_time_2')) {
                // If any time slot field is present but this checkbox isn't, it means unchecked
                $validated[$timeOnField] = 'false';
            }
        }
        
        // Handle discharge_enabled checkbox (checkboxes only send value when checked)
        if (isset($validated['discharge_enabled']) && $validated['discharge_enabled'] === 'true') {
            $validated['discharge_enabled'] = 'true';
        } elseif ($request->has('battery_size_wh') || $request->has('discharge_rate_w') || $request->has('discharge_check_time')) {
            // If any discharge field is present but checkbox isn't checked, set to false
            $validated['discharge_enabled'] = 'false';
        }
        
        // Merge with validated input
        $settings = array_merge($currentSettings, array_filter($validated, fn($value) => $value !== null));

        $success = $this->settingsService->updateSettings($settings);

        if (request()->has('cron_mode')) {
            // Check if plain text output is requested
            if (request()->input('format') !== 'json') {
                $timestamp = now()->timezone('Europe/London')->format('Y-m-d H:i:s');
                $textOutput = "=== EV CHARGING SETTINGS UPDATE: {$timestamp} ===\n\n";
                $textOutput .= "Status: " . ($success ? "SUCCESS" : "FAILED") . "\n";
                $textOutput .= "Message: " . ($success ? "Settings updated successfully" : "Failed to update settings") . "\n\n";
                $textOutput .= "Current Settings:\n";
                
                foreach ($this->settingsService->getSettings() as $key => $value) {
                    $textOutput .= "- {$key}: {$value}\n";
                }
                
                $textOutput .= "\n========== END OF REPORT ==========\n";
                
                return response($textOutput, $success ? 200 : 400)
                    ->header('Content-Type', 'text/plain');
            }
            
            // Default JSON response
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

    private function handleResponse(bool $isCronMode, array $logs, array $apiCalls, bool $success, ?string $errorMessage = null): View|JsonResponse|Response
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
            // Check if plain text output is requested (default for cron mode)
            if (request()->input('format') !== 'json') {
                // Create a plain text output
                $timestamp = now()->timezone('Europe/London')->format('Y-m-d H:i:s');
                $textOutput = "=== EV CHARGING STATUS UPDATE: {$timestamp} ===\n\n";
                $textOutput .= "Status: " . ($success ? "SUCCESS" : "FAILED") . "\n";
                
                if ($errorMessage) {
                    $textOutput .= "Error: {$errorMessage}\n";
                }
                
                $textOutput .= "\n--- LOGS ---\n";
                foreach ($logs as $log) {
                    $textOutput .= $log . "\n";
                }
                
                $textOutput .= "\n--- API CALLS ---\n";
                foreach ($maskedApiCalls as $index => $call) {
                    $textOutput .= ($index + 1) . ". " . ($call['name'] ?? 'API Call') . "\n";
                    $textOutput .= "   Endpoint: " . ($call['endpoint'] ?? 'Unknown') . "\n";
                    if (isset($call['response']) && !empty($call['response'])) {
                        $textOutput .= "   Response: Success\n";
                    } else {
                        $textOutput .= "   Response: Empty or Failed\n";
                    }
                    $textOutput .= "\n";
                }
                
                $textOutput .= "========== END OF REPORT ==========\n";
                
                return response($textOutput, $success ? 200 : 400)
                    ->header('Content-Type', 'text/plain');
            }
            
            // Default JSON response (backwards compatibility)
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

        // Get current inverter settings to display
        $inverterSettings = null;
        $inverterInfo = null;
        try {
            $plantInfo = $this->sunSyncService->getPlantInfo();
            if ($plantInfo) {
                $inverterInfo = $this->sunSyncService->getInverterInfo($plantInfo['id']);
                if ($inverterInfo) {
                    $inverterSn = $inverterInfo['sn'];
                    $inverterSettings = $this->sunSyncService->getInverterSettings($inverterSn);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to get inverter settings for display', [
                'error' => $e->getMessage()
            ]);
        }

        return view('ev-charging.status', [
            'success' => $success,
            'message' => $errorMessage,
            'logs' => $logs,
            'apiCalls' => $maskedApiCalls,
            'settings' => $this->settingsService->getSettings(),
            'inverterSettings' => $inverterSettings,
            'inverterInfo' => $inverterInfo
        ]);
    }

    /**
     * Get current inverter settings via AJAX
     */
    public function getInverterSettings(): JsonResponse
    {
        try {
            $plantInfo = $this->sunSyncService->getPlantInfo();
            if (!$plantInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get plant information'
                ], 400);
            }

            $inverterInfo = $this->sunSyncService->getInverterInfo($plantInfo['id']);
            if (!$inverterInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get inverter information'
                ], 400);
            }

            $inverterSn = $inverterInfo['sn'];
            $inverterSettings = $this->sunSyncService->getInverterSettings($inverterSn);
            
            if (!$inverterSettings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get inverter settings'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'settings' => $inverterSettings,
                'inverterSn' => $inverterSn
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get inverter settings', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching inverter settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Immediately sync current settings to inverter with verification and retry logic
     */
    public function syncToInverterNow()
    {
        // Check if streaming is requested
        if (request()->input('stream') === 'true') {
            // Verify CSRF token for GET requests
            if (request()->isMethod('get')) {
                $token = request()->input('_token');
                if (!$token || $token !== csrf_token()) {
                    abort(403, 'CSRF token mismatch');
                }
            }
            
            return response()->stream(function () {
                $this->streamSyncProgress();
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
                'Connection' => 'keep-alive',
            ]);
        }
        
        // Original non-streaming response
        return $this->syncToInverterNowLegacy();
    }
    
    /**
     * Stream sync progress in real-time using Server-Sent Events
     */
    private function streamSyncProgress(): void
    {
        $maxRetries = 3;
        $stepId = 0;
        
        try {
            // Helper function to send SSE message
            $sendEvent = function($event, $data) {
                echo "event: {$event}\n";
                echo "data: " . json_encode($data) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };
            
            // Step 1: Get current settings from CSV
            $stepId++;
            $sendEvent('step', ['id' => $stepId, 'step' => 1, 'message' => 'Reading local settings...', 'status' => 'in_progress']);
            usleep(100000); // 0.1 second delay for visibility
            $settings = $this->settingsService->getSettings();
            $sendEvent('step', ['id' => $stepId, 'step' => 1, 'message' => 'Reading local settings...', 'status' => 'success']);
            
            // Step 2: Get plant info
            $stepId++;
            $sendEvent('step', ['id' => $stepId, 'step' => 2, 'message' => 'Connecting to SunSync API...', 'status' => 'in_progress']);
            usleep(100000); // 0.1 second delay
            $plantInfo = $this->sunSyncService->getPlantInfo();
            if (!$plantInfo) {
                $sendEvent('step', ['id' => $stepId, 'step' => 2, 'message' => 'Failed to get plant information', 'status' => 'error']);
                $sendEvent('complete', ['success' => false, 'message' => 'Failed to get plant information']);
                return;
            }
            $sendEvent('step', [
                'id' => $stepId,
                'step' => 2, 
                'message' => 'Connecting to SunSync API...', 
                'status' => 'success',
                'request' => ['endpoint' => 'GET /api/v1/plants'],
                'response' => $this->dataMaskingService->maskSensitiveData(['plantInfo' => $plantInfo])
            ]);

            // Step 3: Get inverter info
            $stepId++;
            $sendEvent('step', ['id' => $stepId, 'step' => 3, 'message' => 'Getting inverter information...', 'status' => 'in_progress']);
            usleep(100000); // 0.1 second delay
            $inverterInfo = $this->sunSyncService->getInverterInfo($plantInfo['id']);
            if (!$inverterInfo) {
                $sendEvent('step', ['id' => $stepId, 'step' => 3, 'message' => 'Failed to get inverter information', 'status' => 'error']);
                $sendEvent('complete', ['success' => false, 'message' => 'Failed to get inverter information']);
                return;
            }
            $inverterSn = $inverterInfo['sn'];
            $sendEvent('step', [
                'id' => $stepId,
                'step' => 3, 
                'message' => "Getting inverter information... (SN: {$inverterSn})", 
                'status' => 'success',
                'request' => ['endpoint' => "GET /api/v1/plant/{$plantInfo['id']}/inverters"],
                'response' => $this->dataMaskingService->maskSensitiveData(['inverterInfo' => $inverterInfo])
            ]);
            
            // Prepare settings to send to inverter
            $updateSettings = [
                'sn' => $inverterSn,
                "sellTime1" => $settings['sell_time_1'] ?? "00:00",
                "sellTime2" => $settings['sell_time_2'] ?? "02:00",
                "sellTime3" => $settings['sell_time_3'] ?? "04:00",
                "sellTime4" => $settings['sell_time_4'] ?? "05:30",
                "sellTime5" => $settings['default_sell_time'] ?? "22:00",
                "sellTime6" => $settings['sell_time_6'] ?? "23:30",
                "sellTime1Pac" => "5000",
                "sellTime2Pac" => "5000",
                "sellTime3Pac" => "5000",
                "sellTime4Pac" => "5000",
                "sellTime5Pac" => "5000",
                "sellTime6Pac" => "5000",
                "cap1" => $settings['cap_1'] ?? "100",
                "cap2" => $settings['cap_2'] ?? "100",
                "cap3" => $settings['cap_3'] ?? "100",
                "cap4" => $settings['cap_4'] ?? "25",
                "cap5" => $settings['default_cap'] ?? "20",
                "cap6" => $settings['cap_6'] ?? "100",
                "sellTime1Volt" => "49",
                "sellTime2Volt" => "49",
                "sellTime3Volt" => "49",
                "sellTime4Volt" => "49",
                "sellTime5Volt" => "49",
                "sellTime6Volt" => "49",
                "mondayOn" => true,
                "tuesdayOn" => true,
                "wednesdayOn" => true,
                "thursdayOn" => true,
                "fridayOn" => true,
                "saturdayOn" => true,
                "sundayOn" => true,
                "time1on" => ($settings['time_1_on'] ?? 'true') === 'true' ? true : "false",
                "time2on" => ($settings['time_2_on'] ?? 'true') === 'true' ? true : "false",
                "time3on" => ($settings['time_3_on'] ?? 'true') === 'true' ? true : "false",
                "time4on" => ($settings['time_4_on'] ?? 'false') === 'true' ? true : "false",
                "time5on" => "false",
                "time6on" => ($settings['time_6_on'] ?? 'true') === 'true' ? true : "false",
                "genTime1on" => "false",
                "genTime2on" => "false",
                "genTime3on" => "false",
                "genTime4on" => "false",
                "genTime5on" => "false",
                "genTime6on" => "false"
            ];
            
            // Retry loop
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                // Step 4: Send settings to inverter
                $stepId++;
                $step4Id = $stepId;
                $sendEvent('step', [
                    'id' => $step4Id,
                    'step' => 4, 
                    'message' => "Sending settings to inverter (Attempt {$attempt}/{$maxRetries})...", 
                    'status' => 'in_progress'
                ]);
                
                usleep(100000); // 0.1 second delay
                $success = $this->sunSyncService->updateSystemModeSettings($inverterSn, $updateSettings, true);
                
                if (!$success) {
                    $sendEvent('step', [
                        'id' => $step4Id,
                        'step' => 4, 
                        'message' => "Sending settings to inverter (Attempt {$attempt}/{$maxRetries})...", 
                        'status' => 'error',
                        'request' => [
                            'endpoint' => "POST /api/v1/common/setting/{$inverterSn}/set",
                            'body' => $this->dataMaskingService->maskSensitiveData($updateSettings)
                        ],
                        'response' => ['success' => false, 'error' => 'Update failed']
                    ]);
                    
                    if ($attempt < $maxRetries) {
                        $stepId++;
                        $sendEvent('step', [
                            'id' => $stepId,
                            'step' => 4, 
                            'message' => "Failed to send settings, retrying...", 
                            'status' => 'warning'
                        ]);
                        sleep(2);
                        continue;
                    } else {
                        $sendEvent('complete', [
                            'success' => false,
                            'message' => 'Failed to sync settings to inverter after ' . $maxRetries . ' attempts'
                        ]);
                        return;
                    }
                }
                
                $sendEvent('step', [
                    'id' => $step4Id,
                    'step' => 4, 
                    'message' => "Sending settings to inverter (Attempt {$attempt}/{$maxRetries})...", 
                    'status' => 'success',
                    'request' => [
                        'endpoint' => "POST /api/v1/common/setting/{$inverterSn}/set",
                        'body' => $this->dataMaskingService->maskSensitiveData($updateSettings)
                    ],
                    'response' => ['success' => true]
                ]);
                
                // Step 5: Wait for inverter to process
                $stepId++;
                $step5Id = $stepId;
                $sendEvent('step', [
                    'id' => $step5Id,
                    'step' => 5, 
                    'message' => 'Waiting for inverter to process changes (3 seconds)...', 
                    'status' => 'in_progress'
                ]);
                sleep(3);
                $sendEvent('step', [
                    'id' => $step5Id,
                    'step' => 5, 
                    'message' => 'Waiting for inverter to process changes (3 seconds)...', 
                    'status' => 'success'
                ]);
                
                // Step 6: Verify settings
                $stepId++;
                $step6Id = $stepId;
                $sendEvent('step', [
                    'id' => $step6Id,
                    'step' => 6, 
                    'message' => "Verifying settings (Attempt {$attempt}/{$maxRetries})...", 
                    'status' => 'in_progress'
                ]);
                
                usleep(100000); // 0.1 second delay
                $currentSettings = $this->sunSyncService->getInverterSettings($inverterSn);
                
                if (!$currentSettings) {
                    $sendEvent('step', [
                        'id' => $step6Id,
                        'step' => 6, 
                        'message' => "Verifying settings (Attempt {$attempt}/{$maxRetries})...", 
                        'status' => 'error',
                        'request' => ['endpoint' => "GET /api/v1/common/setting/{$inverterSn}/read"],
                        'response' => ['error' => 'No data returned']
                    ]);
                    
                    if ($attempt < $maxRetries) {
                        $stepId++;
                        $sendEvent('step', [
                            'id' => $stepId,
                            'step' => 6, 
                            'message' => "Could not verify settings, retrying sync...", 
                            'status' => 'warning'
                        ]);
                        sleep(2);
                        continue;
                    } else {
                        $sendEvent('complete', [
                            'success' => false,
                            'message' => 'Settings may have been saved but verification failed'
                        ]);
                        return;
                    }
                }
                
                // Verify key settings
                $verificationErrors = [];
                $fieldsToVerify = [
                    'sellTime1', 'sellTime2', 'sellTime3', 'sellTime4', 'sellTime5', 'sellTime6',
                    'cap1', 'cap2', 'cap3', 'cap4', 'cap5', 'cap6'
                ];
                
                foreach ($fieldsToVerify as $field) {
                    if (isset($updateSettings[$field]) && isset($currentSettings[$field])) {
                        if ($updateSettings[$field] != $currentSettings[$field]) {
                            $verificationErrors[] = "{$field}: expected {$updateSettings[$field]}, got {$currentSettings[$field]}";
                        }
                    }
                }
                
                if (!empty($verificationErrors)) {
                    $sendEvent('step', [
                        'id' => $step6Id,
                        'step' => 6, 
                        'message' => "Verifying settings (Attempt {$attempt}/{$maxRetries})... - Mismatches found: " . count($verificationErrors), 
                        'status' => 'warning',
                        'request' => ['endpoint' => "GET /api/v1/common/setting/{$inverterSn}/read"],
                        'response' => $this->dataMaskingService->maskSensitiveData(['currentSettings' => $currentSettings]),
                        'verificationErrors' => $verificationErrors,
                        'comparison' => [
                            'expected' => array_intersect_key($updateSettings, array_flip($fieldsToVerify)),
                            'actual' => array_intersect_key($currentSettings, array_flip($fieldsToVerify))
                        ]
                    ]);
                    
                    if ($attempt < $maxRetries) {
                        $stepId++;
                        $sendEvent('step', [
                            'id' => $stepId,
                            'step' => 6, 
                            'message' => "Settings verification failed, retrying sync...", 
                            'status' => 'warning',
                            'details' => implode(', ', array_slice($verificationErrors, 0, 3))
                        ]);
                        sleep(2);
                        continue;
                    } else {
                        $sendEvent('complete', [
                            'success' => true,
                            'partial' => true,
                            'message' => 'Settings saved but some values may not have updated correctly',
                            'verificationErrors' => $verificationErrors
                        ]);
                        return;
                    }
                }
                
                // Success!
                $sendEvent('step', [
                    'id' => $step6Id,
                    'step' => 6, 
                    'message' => "Verification successful - All settings match!", 
                    'status' => 'success',
                    'request' => ['endpoint' => "GET /api/v1/common/setting/{$inverterSn}/read"],
                    'response' => $this->dataMaskingService->maskSensitiveData(['currentSettings' => $currentSettings]),
                    'comparison' => [
                        'expected' => array_intersect_key($updateSettings, array_flip($fieldsToVerify)),
                        'actual' => array_intersect_key($currentSettings, array_flip($fieldsToVerify))
                    ]
                ]);
                
                $stepId++;
                $sendEvent('step', [
                    'id' => $stepId,
                    'step' => 7, 
                    'message' => "✓ Sync completed successfully on attempt {$attempt}", 
                    'status' => 'success'
                ]);
                
                $sendEvent('complete', [
                    'success' => true,
                    'message' => "Settings synced and verified successfully (Attempt {$attempt}/{$maxRetries})",
                    'attempt' => $attempt
                ]);
                
                Log::info('Manual sync to inverter successful', [
                    'inverter_sn' => $inverterSn,
                    'attempt' => $attempt,
                    'settings' => $updateSettings
                ]);
                
                return;
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to sync settings to inverter', [
                'error' => $e->getMessage()
            ]);
            
            $stepId++;
            $sendEvent('step', [
                'id' => $stepId,
                'step' => 'error',
                'message' => 'Exception: ' . $e->getMessage(),
                'status' => 'error'
            ]);
            
            $sendEvent('complete', [
                'success' => false,
                'message' => 'An error occurred while syncing to inverter: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Legacy non-streaming sync (original implementation)
     */
    private function syncToInverterNowLegacy(): JsonResponse
    {
        $steps = [];
        $maxRetries = 3;
        
        try {
            // Step 1: Get current settings from CSV
            $steps[] = ['step' => 1, 'message' => 'Reading local settings...', 'status' => 'in_progress'];
            $settings = $this->settingsService->getSettings();
            $steps[count($steps) - 1]['status'] = 'success';
            
            // Step 2: Get plant info
            $steps[] = ['step' => 2, 'message' => 'Connecting to SunSync API...', 'status' => 'in_progress'];
            $plantInfo = $this->sunSyncService->getPlantInfo();
            if (!$plantInfo) {
                $steps[count($steps) - 1]['status'] = 'error';
                $steps[count($steps) - 1]['response'] = ['error' => 'No data returned'];
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get plant information',
                    'steps' => $steps
                ], 400);
            }
            $steps[count($steps) - 1]['status'] = 'success';
            $steps[count($steps) - 1]['request'] = ['endpoint' => 'GET /api/v1/plants'];
            $steps[count($steps) - 1]['response'] = $this->dataMaskingService->maskSensitiveData(['plantInfo' => $plantInfo]);

            // Step 3: Get inverter info
            $steps[] = ['step' => 3, 'message' => 'Getting inverter information...', 'status' => 'in_progress'];
            $inverterInfo = $this->sunSyncService->getInverterInfo($plantInfo['id']);
            if (!$inverterInfo) {
                $steps[count($steps) - 1]['status'] = 'error';
                $steps[count($steps) - 1]['response'] = ['error' => 'No data returned'];
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get inverter information',
                    'steps' => $steps
                ], 400);
            }
            $inverterSn = $inverterInfo['sn'];
            $steps[count($steps) - 1]['status'] = 'success';
            $steps[count($steps) - 1]['message'] .= " (SN: {$inverterSn})";
            $steps[count($steps) - 1]['request'] = ['endpoint' => "GET /api/v1/plant/{$plantInfo['id']}/inverters"];
            $steps[count($steps) - 1]['response'] = $this->dataMaskingService->maskSensitiveData(['inverterInfo' => $inverterInfo]);
            
            // Prepare settings to send to inverter
            $updateSettings = [
                'sn' => $inverterSn,

                "sellTime1" => $settings['sell_time_1'] ?? "00:00",
                "sellTime2" => $settings['sell_time_2'] ?? "02:00",
                "sellTime3" => $settings['sell_time_3'] ?? "04:00",
                "sellTime4" => $settings['sell_time_4'] ?? "05:30",
                "sellTime5" => $settings['default_sell_time'] ?? "22:00",
                "sellTime6" => $settings['sell_time_6'] ?? "23:30",

                "sellTime1Pac" => "5000",
                "sellTime2Pac" => "5000",
                "sellTime3Pac" => "5000",
                "sellTime4Pac" => "5000",
                "sellTime5Pac" => "5000",
                "sellTime6Pac" => "5000",

                "cap1" => $settings['cap_1'] ?? "100",
                "cap2" => $settings['cap_2'] ?? "100",
                "cap3" => $settings['cap_3'] ?? "100",
                "cap4" => $settings['cap_4'] ?? "25",
                "cap5" => $settings['default_cap'] ?? "20",
                "cap6" => $settings['cap_6'] ?? "100",

                "sellTime1Volt" => "49",
                "sellTime2Volt" => "49",
                "sellTime3Volt" => "49",
                "sellTime4Volt" => "49",
                "sellTime5Volt" => "49",
                "sellTime6Volt" => "49",

                "mondayOn" => true,
                "tuesdayOn" => true,
                "wednesdayOn" => true,
                "thursdayOn" => true,
                "fridayOn" => true,
                "saturdayOn" => true,
                "sundayOn" => true,

                "time1on" => ($settings['time_1_on'] ?? 'true') === 'true' ? true : "false",
                "time2on" => ($settings['time_2_on'] ?? 'true') === 'true' ? true : "false",
                "time3on" => ($settings['time_3_on'] ?? 'true') === 'true' ? true : "false",
                "time4on" => ($settings['time_4_on'] ?? 'false') === 'true' ? true : "false",
                "time5on" => "false",  // Default to false for slot 5
                "time6on" => ($settings['time_6_on'] ?? 'true') === 'true' ? true : "false",

                "genTime1on" => "false",
                "genTime2on" => "false",
                "genTime3on" => "false",
                "genTime4on" => "false",
                "genTime5on" => "false",
                "genTime6on" => "false"
            ];
            
            // Retry loop
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                // Step 4: Send settings to inverter
                $stepIndex = count($steps);
                $steps[] = [
                    'step' => 4, 
                    'message' => "Sending settings to inverter (Attempt {$attempt}/{$maxRetries})...", 
                    'status' => 'in_progress'
                ];
                
                $success = $this->sunSyncService->updateSystemModeSettings($inverterSn, $updateSettings, true); // Force update
                
                // Add request details
                $steps[$stepIndex]['request'] = [
                    'endpoint' => "POST /api/v1/common/setting/{$inverterSn}/set",
                    'body' => $this->dataMaskingService->maskSensitiveData($updateSettings)
                ];
                
                if (!$success) {
                    $steps[$stepIndex]['status'] = 'error';
                    $steps[$stepIndex]['response'] = ['success' => false, 'error' => 'Update failed'];
                    
                    if ($attempt < $maxRetries) {
                        $steps[] = [
                            'step' => 4, 
                            'message' => "Failed to send settings, retrying...", 
                            'status' => 'warning'
                        ];
                        sleep(1); // Wait 2 seconds before retry
                        continue;
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to sync settings to inverter after ' . $maxRetries . ' attempts',
                            'steps' => $steps
                        ], 400);
                    }
                }
                
                $steps[$stepIndex]['status'] = 'success';
                $steps[$stepIndex]['response'] = ['success' => true];
                
                // Step 5: Wait for inverter to process
                $steps[] = [
                    'step' => 5, 
                    'message' => 'Waiting for inverter to process changes (10 seconds)...', 
                    'status' => 'in_progress'
                ];
                sleep(10);
                $steps[count($steps) - 1]['status'] = 'success';
                
                // Step 6: Verify settings
                $stepIndex = count($steps);
                $steps[] = [
                    'step' => 6, 
                    'message' => "Verifying settings (Attempt {$attempt}/{$maxRetries})...", 
                    'status' => 'in_progress'
                ];
                
                $currentSettings = $this->sunSyncService->getInverterSettings($inverterSn);
                
                // Add request details
                $steps[$stepIndex]['request'] = [
                    'endpoint' => "GET /api/v1/common/setting/{$inverterSn}/read"
                ];
                
                if (!$currentSettings) {
                    $steps[$stepIndex]['status'] = 'error';
                    $steps[$stepIndex]['response'] = ['error' => 'No data returned'];
                    
                    if ($attempt < $maxRetries) {
                        $steps[] = [
                            'step' => 6, 
                            'message' => "Could not verify settings, retrying sync...", 
                            'status' => 'warning'
                        ];
                        sleep(5);
                        continue;
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Settings may have been saved but verification failed',
                            'steps' => $steps
                        ], 400);
                    }
                }
                
                $steps[$stepIndex]['response'] = $this->dataMaskingService->maskSensitiveData(['currentSettings' => $currentSettings]);
                
                // Verify key settings
                $verificationErrors = [];
                $fieldsToVerify = [
                    'sellTime1', 'sellTime2', 'sellTime3', 'sellTime4', 'sellTime5', 'sellTime6',
                    'cap1', 'cap2', 'cap3', 'cap4', 'cap5', 'cap6'
                ];
                
                foreach ($fieldsToVerify as $field) {
                    if (isset($updateSettings[$field]) && isset($currentSettings[$field])) {
                        if ($updateSettings[$field] != $currentSettings[$field]) {
                            $verificationErrors[] = "{$field}: expected {$updateSettings[$field]}, got {$currentSettings[$field]}";
                        }
                    }
                }
                
                if (!empty($verificationErrors)) {
                    $steps[$stepIndex]['status'] = 'warning';
                    $steps[$stepIndex]['message'] .= " - Mismatches found: " . count($verificationErrors);
                    $steps[$stepIndex]['verificationErrors'] = $verificationErrors;
                    $steps[$stepIndex]['comparison'] = [
                        'expected' => array_intersect_key($updateSettings, array_flip($fieldsToVerify)),
                        'actual' => array_intersect_key($currentSettings, array_flip($fieldsToVerify))
                    ];
                    
                    if ($attempt < $maxRetries) {
                        $steps[] = [
                            'step' => 6, 
                            'message' => "Settings verification failed, retrying sync...", 
                            'status' => 'warning',
                            'details' => implode(', ', array_slice($verificationErrors, 0, 3)),
                            'verificationErrors' => $verificationErrors
                        ];
                        sleep(5);
                        continue;
                    } else {
                        $steps[] = [
                            'step' => 6, 
                            'message' => "Settings partially saved but some mismatches remain", 
                            'status' => 'warning',
                            'details' => implode(', ', array_slice($verificationErrors, 0, 5)),
                            'verificationErrors' => $verificationErrors
                        ];
                        
                        Log::warning('Sync verification found mismatches after all retries', [
                            'inverter_sn' => $inverterSn,
                            'errors' => $verificationErrors
                        ]);
                        
                        return response()->json([
                            'success' => true,
                            'partial' => true,
                            'message' => 'Settings saved but some values may not have updated correctly',
                            'steps' => $steps,
                            'verificationErrors' => $verificationErrors
                        ]);
                    }
                }
                
                // Add verification comparison even on success
                $steps[$stepIndex]['comparison'] = [
                    'expected' => array_intersect_key($updateSettings, array_flip($fieldsToVerify)),
                    'actual' => array_intersect_key($currentSettings, array_flip($fieldsToVerify))
                ];
                
                // Success!
                $steps[$stepIndex]['status'] = 'success';
                $steps[$stepIndex]['message'] = "Verification successful - All settings match!";
                
                $steps[] = [
                    'step' => 7, 
                    'message' => "✓ Sync completed successfully on attempt {$attempt}", 
                    'status' => 'success'
                ];
                
                Log::info('Manual sync to inverter successful', [
                    'inverter_sn' => $inverterSn,
                    'attempt' => $attempt,
                    'settings' => $updateSettings
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => "Settings synced and verified successfully (Attempt {$attempt}/{$maxRetries})",
                    'steps' => $steps,
                    'attempt' => $attempt
                ]);
            }
            
            // This shouldn't be reached, but just in case
            return response()->json([
                'success' => false,
                'message' => 'Unexpected error in sync process',
                'steps' => $steps
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Failed to sync settings to inverter', [
                'error' => $e->getMessage()
            ]);
            
            $steps[] = [
                'step' => 'error',
                'message' => 'Exception: ' . $e->getMessage(),
                'status' => 'error'
            ];
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while syncing to inverter: ' . $e->getMessage(),
                'steps' => $steps
            ], 500);
        }
    }
} 
<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BatteryDischargeService
{
    private EvChargingSettingsService $settingsService;
    private SunSyncService $sunSyncService;

    public function __construct(
        EvChargingSettingsService $settingsService,
        SunSyncService $sunSyncService
    ) {
        $this->settingsService = $settingsService;
        $this->sunSyncService = $sunSyncService;
    }

    /**
     * Check if battery discharge feature is enabled
     */
    public function isDischargeEnabled(): bool
    {
        $settings = $this->settingsService->getSettings();
        return ($settings['discharge_enabled'] ?? 'false') === 'true';
    }

    /**
     * Calculate hours needed to discharge battery from current SOC to target SOC
     * 
     * Formula (accounting for house load during waiting period):
     * 1. Energy available = Battery Size × (Current SOC - Target SOC)
     * 2. Initial discharge time = Energy available / Discharge Rate
     * 3. Initial start time = Stop Time - Initial discharge time
     * 4. Waiting period = Initial start time - Check time
     * 5. House consumption during waiting = Waiting period × House Load
     * 6. Adjusted energy = Energy available - House consumption
     * 7. Final discharge hours = Adjusted energy / Discharge Rate
     * 
     * Note: House drains battery only while waiting (check time to start time).
     *       During discharge, house is powered from grid, not battery.
     * 
     * @param float $currentSoc Current State of Charge (0-100)
     * @param array $settings Discharge settings
     * @param Carbon|null $currentTime Current time (defaults to now)
     * @return float Hours to discharge
     */
    public function calculateDischargeHours(float $currentSoc, array $settings, ?\Carbon\Carbon $currentTime = null): float
    {
        $batterySize = (float) ($settings['battery_size_wh'] ?? 10000);
        $dischargeRate = (float) ($settings['discharge_rate_w'] ?? 2750);
        $houseLoad = (float) ($settings['house_load_w'] ?? 350);
        $dischargeToSoc = (float) ($settings['discharge_to_soc'] ?? 20);
        $checkTime = $settings['discharge_check_time'] ?? '20:00';
        $stopTime = $settings['discharge_stop_time'] ?? '23:45';

        if (!$currentTime) {
            $currentTime = \Carbon\Carbon::now()->timezone('Europe/London');
        }

        // Prevent division by zero
        if ($dischargeRate <= 0) {
            Log::warning('BatteryDischargeService: Discharge rate is zero or negative', [
                'discharge_rate' => $dischargeRate
            ]);
            return 0;
        }

        // Step 1: Calculate total energy available to discharge (Wh)
        $energyAvailable = $batterySize * (($currentSoc - $dischargeToSoc) / 100);

        // Step 2: Calculate initial discharge time estimate (hours)
        $initialDischargeTime = $energyAvailable / $dischargeRate;

        // Step 3: Calculate initial start time (Stop Time - Initial discharge time)
        [$stopHour, $stopMinute] = explode(':', $stopTime);
        $stopDateTime = $currentTime->copy()->setTime((int)$stopHour, (int)$stopMinute, 0);
        
        $initialStartTime = $stopDateTime->copy()->subHours((int)$initialDischargeTime);
        $remainingMinutes = ($initialDischargeTime - floor($initialDischargeTime)) * 60;
        $initialStartTime->subMinutes((int)$remainingMinutes);

        // Step 4: Calculate waiting period (from check time to start time)
        [$checkHour, $checkMinute] = explode(':', $checkTime);
        $checkDateTime = $currentTime->copy()->setTime((int)$checkHour, (int)$checkMinute, 0);
        
        // Calculate hours between check time and start time
        // If start time is after check time, there's a waiting period
        if ($initialStartTime->greaterThan($checkDateTime)) {
            $waitingPeriodHours = $checkDateTime->diffInMinutes($initialStartTime) / 60;
        } else {
            // If start time is before or equal to check time, no waiting period
            $waitingPeriodHours = 0;
        }

        // Step 5: Calculate house consumption during waiting period (Wh)
        $houseConsumption = $waitingPeriodHours * $houseLoad;

        // Step 6: Calculate adjusted energy (Wh)
        $adjustedEnergy = $energyAvailable - $houseConsumption;

        // If house consumption exceeds available energy, return 0
        if ($adjustedEnergy <= 0) {
            Log::warning('BatteryDischargeService: House consumption during waiting exceeds available energy', [
                'energy_available' => $energyAvailable,
                'waiting_hours' => $waitingPeriodHours,
                'house_consumption' => $houseConsumption
            ]);
            return 0;
        }

        // Step 7: Calculate final discharge hours
        $hours = $adjustedEnergy / $dischargeRate;

        return max(0, $hours); // Never return negative
    }

    /**
     * Calculate when discharge should start based on stop time and discharge duration
     * 
     * @param float $hoursToDischarge Hours needed to discharge
     * @param string $stopTime Stop time in H:i format (e.g., "23:45")
     * @param Carbon $currentTime Current time
     * @return Carbon The calculated start time
     */
    public function calculateDischargeStartTime(
        float $hoursToDischarge,
        string $stopTime,
        Carbon $currentTime
    ): Carbon {
        // Parse stop time
        [$stopHour, $stopMinute] = explode(':', $stopTime);
        
        // Create stop time for today
        $stopDateTime = $currentTime->copy()->setTime((int)$stopHour, (int)$stopMinute, 0);
        
        // If stop time has already passed today, use tomorrow
        if ($stopDateTime->lessThanOrEqualTo($currentTime)) {
            $stopDateTime->addDay();
        }
        
        // Subtract discharge hours to get start time
        $startTime = $stopDateTime->copy()->subHours((int)$hoursToDischarge);
        $remainingMinutes = ($hoursToDischarge - floor($hoursToDischarge)) * 60;
        $startTime->subMinutes((int)$remainingMinutes);
        
        return $startTime;
    }

    /**
     * Rule 1: Check if EV is disconnected (Zappi status check)
     * 
     * Only allow discharge if Zappi pst = 'A' (EV Disconnected)
     * Block discharge for: B1, B2, C1, C2, F (any connection or fault)
     * 
     * @param array $zappiStatus Zappi status from MyEnergi API
     * @return array ['allowed' => bool, 'reason' => string]
     */
    public function checkEvDisconnectedRule(array $zappiStatus): array
    {
        if (!isset($zappiStatus['zappi']) || empty($zappiStatus['zappi'])) {
            return [
                'allowed' => false,
                'reason' => 'Unable to get Zappi status'
            ];
        }

        $zappi = $zappiStatus['zappi'][0];
        $pst = $zappi['pst'] ?? null;

        // Only allow if EV is disconnected ('A')
        if ($pst === 'A') {
            return [
                'allowed' => true,
                'reason' => 'EV disconnected - discharge allowed'
            ];
        }

        // Map status codes to human-readable messages
        $statusMessages = [
            'B1' => 'EV Connected',
            'B2' => 'Waiting for EV',
            'C1' => 'EV Ready to Charge',
            'C2' => 'EV Charging',
            'F' => 'Zappi Fault',
        ];

        $statusMessage = $statusMessages[$pst] ?? "Unknown status ($pst)";

        return [
            'allowed' => false,
            'reason' => "Discharge blocked: $statusMessage"
        ];
    }

    /**
     * Rule 2: Check if battery SOC is above minimum at check time
     * 
     * @param float $currentSoc Current battery SOC (0-100)
     * @param float $minimumSoc Minimum required SOC
     * @param Carbon $currentTime Current time
     * @param string $checkTime Check time in H:i format
     * @return array ['allowed' => bool, 'reason' => string]
     */
    public function checkMinimumSocRule(
        float $currentSoc,
        float $minimumSoc,
        Carbon $currentTime,
        string $checkTime
    ): array {
        // Parse check time
        [$checkHour, $checkMinute] = explode(':', $checkTime);
        $checkDateTime = $currentTime->copy()->setTime((int)$checkHour, (int)$checkMinute, 0);
        
        // Only check this rule if we're at or past the check time
        if ($currentTime->lessThan($checkDateTime)) {
            return [
                'allowed' => true,
                'reason' => "Before check time ($checkTime) - rule not yet active"
            ];
        }

        if ($currentSoc >= $minimumSoc) {
            return [
                'allowed' => true,
                'reason' => "Battery SOC ({$currentSoc}%) is above minimum ({$minimumSoc}%)"
            ];
        }

        return [
            'allowed' => false,
            'reason' => "Battery SOC ({$currentSoc}%) is below minimum ({$minimumSoc}%) at check time"
        ];
    }

    /**
     * Determine if we should be in discharge mode based on all rules and current time
     * 
     * @param array $zappiStatus Zappi status
     * @param float $currentSoc Current battery SOC
     * @param Carbon $currentTime Current time
     * @return array ['shouldDischarge' => bool, 'reason' => string, 'startTime' => ?Carbon, 'stopTime' => ?Carbon]
     */
    public function shouldEnableDischarge(
        array $zappiStatus,
        float $currentSoc,
        Carbon $currentTime
    ): array {
        // Check if feature is enabled
        if (!$this->isDischargeEnabled()) {
            return [
                'shouldDischarge' => false,
                'reason' => 'Battery discharge feature is disabled',
                'startTime' => null,
                'stopTime' => null,
            ];
        }

        $settings = $this->settingsService->getSettings();
        
        $checkTime = $settings['discharge_check_time'] ?? '20:00';
        $stopTime = $settings['discharge_stop_time'] ?? '23:45';
        $minimumSoc = (float) ($settings['discharge_min_soc'] ?? 50);
        $dischargeToSoc = (float) ($settings['discharge_to_soc'] ?? 20);

        // Parse stop time
        [$stopHour, $stopMinute] = explode(':', $stopTime);
        $stopDateTime = $currentTime->copy()->setTime((int)$stopHour, (int)$stopMinute, 0);
        
        // If we're past stop time, we should be in normal mode
        if ($currentTime->greaterThanOrEqualTo($stopDateTime)) {
            return [
                'shouldDischarge' => false,
                'reason' => "Past stop time ($stopTime) - returning to normal mode",
                'startTime' => null,
                'stopTime' => $stopDateTime,
            ];
        }

        // Rule 1: Check EV status
        $evCheck = $this->checkEvDisconnectedRule($zappiStatus);
        if (!$evCheck['allowed']) {
            return [
                'shouldDischarge' => false,
                'reason' => $evCheck['reason'],
                'startTime' => null,
                'stopTime' => $stopDateTime,
            ];
        }

        // Rule 2: Check minimum SOC at check time
        $socCheck = $this->checkMinimumSocRule($currentSoc, $minimumSoc, $currentTime, $checkTime);
        if (!$socCheck['allowed']) {
            return [
                'shouldDischarge' => false,
                'reason' => $socCheck['reason'],
                'startTime' => null,
                'stopTime' => $stopDateTime,
            ];
        }

        // Calculate when discharge should start
        $hoursToDischarge = $this->calculateDischargeHours($currentSoc, $settings, $currentTime);
        $startTime = $this->calculateDischargeStartTime($hoursToDischarge, $stopTime, $currentTime);

        // Check if we're in the discharge window
        if ($currentTime->greaterThanOrEqualTo($startTime) && $currentTime->lessThan($stopDateTime)) {
            // Check if battery is above discharge-to level
            if ($currentSoc > $dischargeToSoc) {
                return [
                    'shouldDischarge' => true,
                    'reason' => "In discharge window ({$startTime->format('H:i')} - {$stopDateTime->format('H:i')}), battery at {$currentSoc}%",
                    'startTime' => $startTime,
                    'stopTime' => $stopDateTime,
                ];
            } else {
                return [
                    'shouldDischarge' => false,
                    'reason' => "Battery has reached discharge-to level ({$dischargeToSoc}%)",
                    'startTime' => $startTime,
                    'stopTime' => $stopDateTime,
                ];
            }
        } else if ($currentTime->lessThan($startTime)) {
            return [
                'shouldDischarge' => false,
                'reason' => "Before discharge start time ({$startTime->format('H:i')})",
                'startTime' => $startTime,
                'stopTime' => $stopDateTime,
            ];
        }

        return [
            'shouldDischarge' => false,
            'reason' => 'Outside discharge window',
            'startTime' => $startTime,
            'stopTime' => $stopDateTime,
        ];
    }

    /**
     * Get current battery SOC from inverter
     * 
     * @return float|null Battery SOC (0-100) or null if unable to retrieve
     */
    public function getCurrentBatterySoc(): ?float
    {
        try {
            // Get plant info
            $plantInfo = $this->sunSyncService->getPlantInfo();
            if (!$plantInfo) {
                Log::warning('BatteryDischargeService: Unable to get plant info');
                return null;
            }

            // Get inverter info
            $inverterInfo = $this->sunSyncService->getInverterInfo($plantInfo['id']);
            if (!$inverterInfo) {
                Log::warning('BatteryDischargeService: Unable to get inverter info');
                return null;
            }

            // Get inverter flow info (contains SOC)
            $flowInfo = $this->sunSyncService->getInverterFlowInfo($inverterInfo['sn']);
            if (!$flowInfo) {
                Log::warning('BatteryDischargeService: Unable to get inverter flow info');
                return null;
            }

            $soc = $flowInfo['soc'] ?? null;
            
            if ($soc === null) {
                Log::warning('BatteryDischargeService: SOC not found in flow info');
                return null;
            }

            return (float) $soc;

        } catch (\Exception $e) {
            Log::error('BatteryDischargeService: Error getting battery SOC', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}


<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EvChargingSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class EvChargingSettingsService
{
    private const SETTINGS_FILE = 'ev-charging-settings.csv';
    
    private function getDefaultSettings(): array
    {
        return [
            // Primary EV charging slot (Slot 5)
            'default_sell_time' => config('sunsync.defaults.sell_time', '22:00'),
            'default_cap' => config('sunsync.defaults.cap', '20'),
            
            // Additional time slots with grid charging capability
            'sell_time_1' => config('sunsync.defaults.sell_time_1', '00:00'),
            'cap_1' => config('sunsync.defaults.cap_1', '100'),
            'time_1_on' => config('sunsync.defaults.time_1_on', 'true'),
            
            'sell_time_2' => config('sunsync.defaults.sell_time_2', '03:00'),
            'cap_2' => config('sunsync.defaults.cap_2', '100'),
            'time_2_on' => config('sunsync.defaults.time_2_on', 'true'),
            
            'sell_time_3' => config('sunsync.defaults.sell_time_3', '05:30'),
            'cap_3' => config('sunsync.defaults.cap_3', '25'),
            'time_3_on' => config('sunsync.defaults.time_3_on', 'false'),
            
            'sell_time_4' => config('sunsync.defaults.sell_time_4', '08:00'),
            'cap_4' => config('sunsync.defaults.cap_4', '25'),
            'time_4_on' => config('sunsync.defaults.time_4_on', 'false'),
            
            'sell_time_6' => config('sunsync.defaults.sell_time_6', '23:30'),
            'cap_6' => config('sunsync.defaults.cap_6', '100'),
            'time_6_on' => config('sunsync.defaults.time_6_on', 'true'),
            
            // Night time range
            'night_start' => config('sunsync.defaults.night_start', '23:30'),
            'night_end' => config('sunsync.defaults.night_end', '05:30'),
            
            // Battery Discharge to Grid Settings
            'battery_size_wh' => config('sunsync.defaults.battery_size_wh', '10000'),
            'discharge_rate_w' => config('sunsync.defaults.discharge_rate_w', '2750'),
            'house_load_w' => config('sunsync.defaults.house_load_w', '350'),
            'discharge_to_soc' => config('sunsync.defaults.discharge_to_soc', '20'),
            'discharge_check_time' => config('sunsync.defaults.discharge_check_time', '20:00'),
            'discharge_min_soc' => config('sunsync.defaults.discharge_min_soc', '50'),
            'discharge_stop_time' => config('sunsync.defaults.discharge_stop_time', '23:45'),
            'discharge_enabled' => config('sunsync.defaults.discharge_enabled', 'false'),
        ];
    }

    /**
     * Check if database table exists and is ready to use
     */
    private function useDatabaseStorage(): bool
    {
        try {
            return Schema::hasTable('ev_charging_settings');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getSettings(): array
    {
        $defaults = $this->getDefaultSettings();
        
        // Prefer database storage if available
        if ($this->useDatabaseStorage()) {
            $settings = EvChargingSetting::getAllSettings();
            
            // If no settings in database, initialize with defaults
            if (empty($settings)) {
                EvChargingSetting::updateSettings($defaults);
                return $defaults;
            }
            
            return array_merge($defaults, $settings);
        }
        
        // Fallback to CSV storage
        if (!Storage::exists(self::SETTINGS_FILE)) {
            $this->saveSettings($defaults);
            return $defaults;
        }

        $content = Storage::get(self::SETTINGS_FILE);
        $lines = explode("\n", trim($content));
        
        // Skip header row
        array_shift($lines);
        
        $settings = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            [$key, $value] = str_getcsv($line);
            $settings[$key] = $value;
        }

        return array_merge($defaults, $settings);
    }

    public function saveSettings(array $settings): bool
    {
        // Prefer database storage if available
        if ($this->useDatabaseStorage()) {
            try {
                EvChargingSetting::updateSettings($settings);
                return true;
            } catch (\Exception $e) {
                // Fall through to CSV storage on error
            }
        }
        
        // Fallback to CSV storage
        $tempFile = tempnam(sys_get_temp_dir(), 'settings_');
        $handle = fopen($tempFile, 'w');
        
        // Write header
        fputcsv($handle, ['key', 'value']);
        
        // Write settings
        foreach ($settings as $key => $value) {
            fputcsv($handle, [$key, $value]);
        }
        
        fclose($handle);
        
        // Move the temp file to the storage location
        $result = Storage::putFileAs('', $tempFile, self::SETTINGS_FILE);
        
        // Clean up the temp file
        unlink($tempFile);
        
        // Return true if the file was successfully written
        return $result !== false;
    }

    public function updateSettings(array $newSettings): bool
    {
        $currentSettings = $this->getSettings();
        $updatedSettings = array_merge($currentSettings, $newSettings);
        return $this->saveSettings($updatedSettings);
    }
} 
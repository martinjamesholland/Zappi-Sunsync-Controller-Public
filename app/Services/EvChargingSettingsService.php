<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class EvChargingSettingsService
{
    private const SETTINGS_FILE = 'ev-charging-settings.csv';
    private const DEFAULT_SETTINGS = [
        'default_sell_time' => '22:00',
        'default_cap' => '20',
        'night_start' => '23:30',
        'night_end' => '05:30'
    ];

    public function getSettings(): array
    {
        if (!Storage::exists(self::SETTINGS_FILE)) {
            $this->saveSettings(self::DEFAULT_SETTINGS);
            return self::DEFAULT_SETTINGS;
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

        return array_merge(self::DEFAULT_SETTINGS, $settings);
    }

    public function saveSettings(array $settings): bool
    {
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
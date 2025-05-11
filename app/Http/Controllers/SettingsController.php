<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    public function index(): View
    {
        $settingsStatus = [
            'ZAPPI_SERIAL' => !empty(env('ZAPPI_SERIAL')),
            'ZAPPI_PASSWORD' => !empty(env('ZAPPI_PASSWORD')),
            'SUNSYNC_USERNAME' => !empty(env('SUNSYNC_USERNAME')),
            'SUNSYNC_PASSWORD' => !empty(env('SUNSYNC_PASSWORD')),
        ];

        return view('settings.index', compact('settingsStatus'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ZAPPI_SERIAL' => 'nullable|string',
            'ZAPPI_PASSWORD' => 'nullable|string',
            'SUNSYNC_USERNAME' => 'nullable|string',
            'SUNSYNC_PASSWORD' => 'nullable|string',
        ]);

        // Filter out empty values to keep existing settings
        $filteredSettings = array_filter($validated, fn($value) => !is_null($value) && $value !== '');
        
        // Get current values for fields not being updated
        $keys = ['ZAPPI_SERIAL', 'ZAPPI_PASSWORD', 'SUNSYNC_USERNAME', 'SUNSYNC_PASSWORD'];
        $currentSettings = [];
        
        foreach ($keys as $key) {
            if (!isset($filteredSettings[$key])) {
                $currentValue = env($key);
                if (!empty($currentValue)) {
                    $currentSettings[$key] = $currentValue;
                }
            }
        }
        
        // Merge current settings with new values
        $settingsToUpdate = array_merge($currentSettings, $filteredSettings);
        
        // Update .env file
        $this->updateEnvFile($settingsToUpdate);

        // Clear config cache
        Artisan::call('config:clear');
        Artisan::call('cache:clear');

        return redirect()->route('settings.index')
            ->with('success', 'Settings updated successfully');
    }

    private function updateEnvFile(array $settings): void
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        foreach ($settings as $key => $value) {
            // If the key exists, update it
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                // If the key doesn't exist, add it
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envFile, $envContent);
    }
} 
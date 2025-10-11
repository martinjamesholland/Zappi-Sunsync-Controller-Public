<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Exception;

class SettingsController extends Controller
{
    public function index(): View
    {
        $settingsStatus = [
            'ZAPPI_SERIAL' => !empty(env('ZAPPI_SERIAL')),
            'ZAPPI_PASSWORD' => !empty(env('ZAPPI_PASSWORD')),
            'SUNSYNC_USERNAME' => !empty(env('SUNSYNC_USERNAME')),
            'SUNSYNC_PASSWORD' => !empty(env('SUNSYNC_PASSWORD')),
            'DB_DATABASE' => !empty(env('DB_DATABASE')),
            'DB_HOST' => !empty(env('DB_HOST')),
            'DB_PORT' => !empty(env('DB_PORT')),
            'DB_USERNAME' => !empty(env('DB_USERNAME')),
            'DB_PASSWORD' => !empty(env('DB_PASSWORD')),
        ];

        // Check for database connection issues
        $dbError = null;
        try {
            DB::connection()->getPdo();
        } catch (QueryException $e) {
            $dbError = $e->getMessage();
            
            // Temporarily switch to file-based sessions for this request
            Config::set('session.driver', 'file');
            Session::setSaveHandler(null);
            
            // Start a file-based session to ensure the form works
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
        } catch (Exception $e) {
            $dbError = $e->getMessage();
        }

        return view('settings.index', compact('settingsStatus', 'dbError'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ZAPPI_SERIAL' => 'nullable|string',
            'ZAPPI_PASSWORD' => 'nullable|string',
            'SUNSYNC_USERNAME' => 'nullable|string',
            'SUNSYNC_PASSWORD' => 'nullable|string',
            'DB_DATABASE' => 'nullable|string',
            'DB_HOST' => 'nullable|string',
            'DB_PORT' => 'nullable|string',
            'DB_USERNAME' => 'nullable|string',
            'DB_PASSWORD' => 'nullable|string',
        ]);

        // Filter out empty values to keep existing settings
        $filteredSettings = array_filter($validated, fn($value) => !is_null($value) && $value !== '');
        
        // Get current values for fields not being updated
        $keys = [
            'ZAPPI_SERIAL', 'ZAPPI_PASSWORD', 'SUNSYNC_USERNAME', 'SUNSYNC_PASSWORD',
            'DB_DATABASE', 'DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD'
        ];
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

        // Check if database settings were changed
        $dbSettingsChanged = false;
        foreach (['DB_DATABASE', 'DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD'] as $dbKey) {
            if (isset($filteredSettings[$dbKey])) {
                $dbSettingsChanged = true;
                break;
            }
        }

        if ($dbSettingsChanged) {
            // Use TempData for this message since we're redirecting
            return redirect()->route('settings.index')
                ->with('success', 'Settings updated successfully. Database settings have been changed - you may need to restart the application for changes to take effect.');
        }

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
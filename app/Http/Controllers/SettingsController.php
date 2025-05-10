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
        $settings = [
            'ZAPPI_SERIAL' => env('ZAPPI_SERIAL'),
            'ZAPPI_PASSWORD' => env('ZAPPI_PASSWORD'),
            'SUNSYNC_USERNAME' => env('SUNSYNC_USERNAME'),
            'SUNSYNC_PASSWORD' => env('SUNSYNC_PASSWORD'),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ZAPPI_SERIAL' => 'required|string',
            'ZAPPI_PASSWORD' => 'required|string',
            'SUNSYNC_USERNAME' => 'required|string',
            'SUNSYNC_PASSWORD' => 'required|string',
        ]);

        // Update .env file
        $this->updateEnvFile($validated);

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
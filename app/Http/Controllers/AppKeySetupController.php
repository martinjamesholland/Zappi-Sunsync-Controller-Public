<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class AppKeySetupController extends Controller
{
    public function __construct()
    {
        // Skip middleware for these routes
        $this->middleware('web')->except(['show', 'generate']);
    }

    public function show()
    {
        // Check if we're in the public directory
        $baseUrl = request()->is('EVBattControl/public*') ? '/EVBattControl/public' : '';
        
        // Check if .env file exists
        $envFile = base_path('.env');
        $envExists = file_exists($envFile);
        
        return view('setup.app-key', [
            'baseUrl' => $baseUrl,
            'envExists' => $envExists,
            'envPath' => $envFile
        ]);
    }

    public function generate(Request $request)
    {
        try {
            // Generate new app key
            $key = 'base64:'.base64_encode(Str::random(32));
            
            // Read the .env file
            $envFile = base_path('.env');
            
            // Create .env file if it doesn't exist
            if (!file_exists($envFile)) {
                // Copy from .env.example if it exists
                $exampleFile = base_path('.env.example');
                if (file_exists($exampleFile)) {
                    copy($exampleFile, $envFile);
                } else {
                    // Create basic .env file
                    file_put_contents($envFile, "APP_NAME=Laravel\nAPP_ENV=local\nAPP_DEBUG=true\nAPP_URL=http://localhost\n\n");
                }
            }
            
            $envContent = file_get_contents($envFile);
            
            // Replace or add APP_KEY
            if (preg_match('/^APP_KEY=.*/m', $envContent)) {
                $envContent = preg_replace('/^APP_KEY=.*/m', 'APP_KEY='.$key, $envContent);
            } else {
                $envContent .= "\nAPP_KEY=".$key;
            }
            
            // Write back to .env file
            if (!is_writable($envFile)) {
                throw new \Exception('The .env file is not writable. Please check file permissions.');
            }
            
            file_put_contents($envFile, $envContent);
            
            // Clear config cache
            Artisan::call('config:clear');
            
            return response()->json([
                'success' => true,
                'message' => 'Application key has been generated and updated successfully.',
                'key' => $key
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate application key: ' . $e->getMessage()
            ], 500);
        }
    }
} 
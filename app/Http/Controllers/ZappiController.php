<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MyEnergiApiService;
use Illuminate\View\View;

class ZappiController extends Controller
{
    private MyEnergiApiService $myEnergiApiService;

    public function __construct(MyEnergiApiService $myEnergiApiService)
    {
        $this->myEnergiApiService = $myEnergiApiService;
    }

    public function status(): View
    {
        $data = $this->myEnergiApiService->getStatus();
        $apiRequests = $this->myEnergiApiService->getApiRequests();

        if ($data === null) {
            $serial = config('myenergi.serial', env('ZAPPI_SERIAL'));
            $password = config('myenergi.password', env('ZAPPI_PASSWORD'));
            
            $missingCredentials = [];
            if (empty($serial)) {
                $missingCredentials[] = 'Zappi Serial Number';
            }
            if (empty($password)) {
                $missingCredentials[] = 'Zappi API Key';
            }

            $message = 'Zappi credentials are not configured. ';
            if (!empty($missingCredentials)) {
                $message .= 'Missing: ' . implode(', ', $missingCredentials) . '. ';
            }
            $message .= 'Please go to Settings and add your Zappi credentials. You can find these in your myenergi account at myaccount.myenergi.com under the API section.';

            return view('zappi.error', [
                'message' => $message,
                'showSettingsLink' => true,
                'apiRequests' => $apiRequests
            ]);
        }

        return view('zappi.status', [
            'data' => $data,
            'apiRequests' => $apiRequests
        ]);
    }
} 
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MyEnergiApiService;
use App\Services\DataMaskingService;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class ZappiController extends Controller
{
    private MyEnergiApiService $myEnergiApiService;
    private DataMaskingService $dataMaskingService;

    public function __construct(
        MyEnergiApiService $myEnergiApiService,
        DataMaskingService $dataMaskingService
    ) {
        $this->myEnergiApiService = $myEnergiApiService;
        $this->dataMaskingService = $dataMaskingService;
    }

    public function status(): View|JsonResponse
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

            if (request()->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => $message
                ], 400);
            }

            return view('zappi.error', [
                'message' => $message,
                'showSettingsLink' => true,
                'apiRequests' => $apiRequests
            ]);
        }

        // Mask sensitive data in responses
        $maskedData = $this->dataMaskingService->maskSensitiveData($data);
        $maskedApiRequests = [];
        
        foreach ($apiRequests as $request) {
            $maskedApiRequests[] = $this->dataMaskingService->maskSensitiveData($request);
        }

        if (request()->ajax()) {
            return response()->json([
                'data' => $maskedData,
                'apiRequests' => $maskedApiRequests
            ]);
        }

        return view('zappi.status', [
            'data' => $maskedData,
            'apiRequests' => $maskedApiRequests,
            'zappiData' => $data
        ]);
    }
} 
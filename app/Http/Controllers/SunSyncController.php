<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SunSyncService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class SunSyncController extends Controller
{
    private SunSyncService $sunSyncService;

    public function __construct(SunSyncService $sunSyncService)
    {
        $this->sunSyncService = $sunSyncService;
    }

    private function maskEmail(string $email): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        list($localPart, $domain) = explode('@', $email);
        list($domainName, $tld) = explode('.', $domain);

        $maskedLocal = $localPart[0] . str_repeat('*', strlen($localPart) - 1);
        $maskedDomain = str_repeat('*', strlen($domainName));

        return $maskedLocal . '@' . $maskedDomain . '.' . $tld;
    }

    private function maskPlantId(string $plantId): string
    {
        if (strlen($plantId) <= 2) {
            return $plantId;
        }
        
        // Keep first and last digit, mask everything else
        return substr($plantId, 0, 1) . str_repeat('*', strlen($plantId) - 2) . substr($plantId, -1);
    }

    private function maskSerialNumber(string $serialNumber): string
    {
        if (strlen($serialNumber) <= 2) {
            return $serialNumber;
        }
        
        // Keep first and last digit, mask everything else
        return substr($serialNumber, 0, 1) . str_repeat('*', strlen($serialNumber) - 2) . substr($serialNumber, -1);
    }

    private function maskEmailsInResponse(array &$response): void
    {
        if (is_array($response)) {
            foreach ($response as $key => &$value) {
                if (is_array($value)) {
                    $this->maskEmailsInResponse($value);
                } elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $value = $this->maskEmail($value);
                }
            }
        }
    }

    private function maskSensitiveDataInResponse(array &$response): void
    {
        if (is_array($response)) {
            foreach ($response as $key => &$value) {
                // Skip thumbUrl as we want to preserve it for image display
                if ($key === 'thumbUrl') {
                    continue;
                }
                
                // Recursively process nested arrays
                if (is_array($value)) {
                    $this->maskSensitiveDataInResponse($value);
                } 
                // Mask serial numbers
                elseif ($key === 'sn' && is_string($value)) {
                    $value = $this->maskSerialNumber($value);
                }
                // Mask plant IDs - handle both string and integer values
                elseif ($key === 'id' && (is_string($value) || is_numeric($value))) {
                    $value = $this->maskPlantId((string)$value);
                }
                // Mask email addresses
                elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $value = $this->maskEmail($value);
                }
                // Mask addresses
                elseif ($key === 'address' && is_string($value)) {
                    $value = '********';
                }
                // Mask refresh tokens but keep last 5 characters
                elseif ($key === 'refresh_token' && is_string($value)) {
                    $lastFive = substr($value, -5);
                    $value = str_repeat('*', strlen($value) - 5) . $lastFive;
                }
            }
        }
    }

    public function dashboard(): View|JsonResponse
    {
        // Get credentials from environment variables
        $username = config('services.sunsync.username');
        $password = config('services.sunsync.password');

        if (empty($username) || empty($password)) {
            $errorMessage = 'SunSync credentials are not configured. Please go to Settings and add your SunSync username and password.';
            if (request()->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => $errorMessage
                ], 400);
            }
            return view('sunsync.error', [
                'message' => $errorMessage,
                'showSettingsLink' => true
            ]);
        }

        // Authenticate
        $authResponse = $this->sunSyncService->authenticate($username, $password);
        if (!$authResponse || isset($authResponse['error'])) {
            $errorMessage = 'Failed to authenticate with SunSync. Please check your credentials in Settings.';
            if (isset($authResponse['message'])) {
                $errorMessage .= ' API Error: ' . $authResponse['message'];
            }
            if (request()->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => $errorMessage,
                    'details' => $authResponse['details'] ?? null
                ], 401);
            }
            return view('sunsync.error', [
                'message' => $errorMessage,
                'showSettingsLink' => true,
                'apiError' => $authResponse['details'] ?? null
            ]);
        }

        // Get plant info
        $plantInfo = $this->sunSyncService->getPlantInfo();
        if (!$plantInfo) {
            $errorMessage = 'Failed to get plant information from SunSync. Please check your credentials in Settings.';
            if (request()->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => $errorMessage
                ], 500);
            }
            return view('sunsync.error', [
                'message' => $errorMessage,
                'showSettingsLink' => true
            ]);
        }

        // Store the original plant ID for API calls
        $originalPlantId = $plantInfo['id'];
        
        // Get API requests for tracking
        $apiRequests = $this->sunSyncService->getApiRequests();
        
        // Get inverter info - use the original plant ID for API calls
        $inverterInfo = $this->sunSyncService->getInverterInfo($originalPlantId);
        $apiCalls['get_inverter_info'] = [
            'name' => 'Get Inverter Information',
            'url' => "GET /api/v1/plant/{$this->maskPlantId((string)$originalPlantId)}/inverters",
            'method' => 'GET',
            'headers' => [
                'Authorization' => 'Bearer ' . ($authResponse['access_token'] ?? '********')
            ],
            'body' => null,
            'endpoint' => "GET /api/v1/plant/{$this->maskPlantId((string)$originalPlantId)}/inverters",
            'request' => ['plantId' => $this->maskPlantId((string)$originalPlantId)],
            'response' => $inverterInfo
        ];
        if (!$inverterInfo) {
            $errorMessage = 'Failed to get inverter information from SunSync. Please check your credentials in Settings.';
            if (request()->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => $errorMessage
                ], 500);
            }
            return view('sunsync.error', [
                'message' => $errorMessage,
                'showSettingsLink' => true
            ]);
        }

        // Store original serial number for API calls
        $originalSerialNumber = $inverterInfo['sn'];
        
        // Get inverter settings
        $inverterSettings = $this->sunSyncService->getInverterSettings($originalSerialNumber);
        $apiCalls['get_inverter_settings'] = [
            'name' => 'Get Inverter Settings',
            'url' => "GET /api/v1/common/setting/{$this->maskSerialNumber((string)$originalSerialNumber)}/read",
            'method' => 'GET',
            'headers' => [
                'Authorization' => 'Bearer ' . ($authResponse['access_token'] ?? '********')
            ],
            'body' => null,
            'endpoint' => "GET /api/v1/common/setting/{$this->maskSerialNumber((string)$originalSerialNumber)}/read",
            'request' => ['inverterSn' => $this->maskSerialNumber((string)$originalSerialNumber)],
            'response' => $inverterSettings
        ];
        if (!$inverterSettings) {
            $errorMessage = 'Failed to get inverter settings from SunSync. Please check your credentials in Settings.';
            if (request()->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => $errorMessage
                ], 500);
            }
            return view('sunsync.error', [
                'message' => $errorMessage,
                'showSettingsLink' => true
            ]);
        }
        
        // Get inverter flow information
        $inverterFlowInfo = $this->sunSyncService->getInverterFlowInfo($originalSerialNumber);
        $apiCalls['get_inverter_flow_info'] = [
            'name' => 'Get Inverter Flow Information',
            'url' => "GET /api/v1/common/setting/{$this->maskSerialNumber((string)$originalSerialNumber)}/read",
            'method' => 'GET',
            'headers' => [
                'Authorization' => 'Bearer ' . ($authResponse['access_token'] ?? '********')
            ],
            'body' => null,
            'endpoint' => "GET /api/v1/common/setting/{$this->maskSerialNumber((string)$originalSerialNumber)}/read",
            'request' => ['inverterSn' => $this->maskSerialNumber((string)$originalSerialNumber)],
            'response' => $inverterFlowInfo
        ];
        if (!$inverterFlowInfo) {
            $errorMessage = 'Failed to get inverter flow information from SunSync. Please check your credentials in Settings.';
            if (request()->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => $errorMessage
                ], 500);
            }
            return view('sunsync.error', [
                'message' => $errorMessage,
                'showSettingsLink' => true
            ]);
        }

        $data = [
            'plantInfo' => $plantInfo,
            'inverterInfo' => $inverterInfo,
            'inverterSettings' => $inverterSettings,
            'inverterFlowInfo' => $inverterFlowInfo,
            'authResponse' => $authResponse,
            'apiRequests' => array_merge($apiRequests, $apiCalls)
        ];

        // Apply masking to all sensitive data in the response
        $this->maskSensitiveDataInResponse($data);

        if (request()->ajax()) {
            return response()->json($data);
        }

        return view('sunsync.dashboard', $data);
    }
} 
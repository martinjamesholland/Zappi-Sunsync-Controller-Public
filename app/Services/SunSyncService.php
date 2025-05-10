<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SunSyncService
{
    private string $baseUrl = 'https://api.sunsynk.net';
    private ?string $accessToken = null;
    private array $apiRequests = [];

    public function __construct()
    {
        $this->accessToken = Cache::get('sunsynk_access_token');
    }

    /**
     * Mask sensitive data in request/response arrays
     */
    private function maskSensitiveData(array $data): array
    {
        $masked = $data;
        
        // Mask password fields
        if (isset($masked['password'])) {
            $masked['password'] = '********';
        }
        
        // Mask access tokens but show last 5 chars
        if (isset($masked['access_token'])) {
            $token = $masked['access_token'];
            $masked['access_token'] = '********' . substr($token, -5);
        }
        
        // Mask authorization headers but show last 5 chars
        if (isset($masked['headers']['Authorization'])) {
            $auth = $masked['headers']['Authorization'];
            if (strpos($auth, 'Bearer ') === 0) {
                $token = substr($auth, 7); // Remove 'Bearer ' prefix
                $masked['headers']['Authorization'] = 'Bearer ********' . substr($token, -5);
            }
        }
        
        // Recursively process nested arrays
        foreach ($masked as $key => $value) {
            if (is_array($value)) {
                $masked[$key] = $this->maskSensitiveData($value);
            }
        }
        
        return $masked;
    }

    public function authenticate(string $username, string $password): ?array
    {
        if (empty($username) || empty($password)) {
            Log::error('SunSync authentication failed: Missing credentials');
            return null;
        }

        try {
            $requestData = [
                'grant_type' => 'password',
                'username' => $username,
                'password' => $password
            ];

            $response = Http::post($this->baseUrl . '/oauth/token', $requestData);

            // Store request details with masked sensitive data
            $this->apiRequests['auth'] = $this->maskSensitiveData([
                'url' => $this->baseUrl . '/oauth/token',
                'method' => 'POST',
                'headers' => [],
                'body' => $requestData,
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['access_token'])) {
                    $this->accessToken = $data['data']['access_token'];
                    Cache::put('sunsynk_access_token', $this->accessToken, now()->addMinutes(60));
                    return $data;
                }
            }

            // Get detailed error information
            $errorData = $response->json();
            $errorMessage = 'Authentication failed';
            
            if (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } elseif (isset($errorData['error'])) {
                $errorMessage = $errorData['error'];
            }

            Log::error('SunSync authentication failed', [
                'error' => $errorMessage,
                'status' => $response->status(),
                'response' => $errorData
            ]);

            return [
                'error' => true,
                'message' => $errorMessage,
                'status' => $response->status(),
                'details' => $errorData
            ];
        } catch (\Exception $e) {
            Log::error('SunSync authentication failed', [
                'error' => $e->getMessage()
            ]);
            return [
                'error' => true,
                'message' => 'Connection error: ' . $e->getMessage(),
                'status' => 500
            ];
        }
    }

    public function getPlantInfo(): ?array
    {
        if (!$this->accessToken) {
            Log::error('SunSync get plant info failed: No access token');
            return null;
        }

        try {
            $requestData = [
                'page' => 1,
                'limit' => 10,
                'name' => '',
                'status' => ''
            ];

            $response = Http::withToken($this->accessToken)
                ->get($this->baseUrl . '/api/v1/plants', $requestData);

            // Store request details with masked sensitive data
            $this->apiRequests['plant_info'] = $this->maskSensitiveData([
                'url' => $this->baseUrl . '/api/v1/plants',
                'method' => 'GET',
                'headers' => ['Authorization' => 'Bearer ' . $this->accessToken],
                'body' => $requestData,
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['infos'][0] ?? null;
            }
            Log::error('SunSync get plant info failed: API request failed');
            return null;
        } catch (\Exception $e) {
            Log::error('SunSync get plant info failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getInverterInfo($plantId): ?array
    {
        if (!$this->accessToken) {
            Log::error('SunSync get inverter info failed: No access token');
            return null;
        }

        // Convert to string if it's an integer
        $plantId = (string) $plantId;

        try {
            $requestData = [
                'page' => 1,
                'limit' => 10,
                'status' => -1,
                'sn' => '',
                'id' => $plantId,
                'type' => -2
            ];

            $response = Http::withToken($this->accessToken)
                ->get($this->baseUrl . "/api/v1/plant/{$plantId}/inverters", $requestData);

            // Store request details with masked sensitive data
            $this->apiRequests['inverter_info'] = $this->maskSensitiveData([
                'url' => $this->baseUrl . "/api/v1/plant/{$plantId}/inverters",
                'method' => 'GET',
                'headers' => ['Authorization' => 'Bearer ' . $this->accessToken],
                'body' => $requestData,
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['infos'][0] ?? null;
            }
            Log::error('SunSync get inverter info failed: API request failed');
            return null;
        } catch (\Exception $e) {
            Log::error('SunSync get inverter info failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getInverterSettings($inverterSn): ?array
    {
        if (!$this->accessToken) {
            Log::error('SunSync get inverter settings failed: No access token');
            return null;
        }

        // Convert to string if it's not already
        $inverterSn = (string) $inverterSn;

        try {
            $requestData = [
                'type' => 'all',
                'sn' => $inverterSn
            ];

            $response = Http::withToken($this->accessToken)
                ->get($this->baseUrl . "/api/v1/common/setting/{$inverterSn}/read", $requestData);

            // Store request details with masked sensitive data
            $this->apiRequests['inverter_settings'] = $this->maskSensitiveData([
                'url' => $this->baseUrl . "/api/v1/common/setting/{$inverterSn}/read",
                'method' => 'GET',
                'headers' => ['Authorization' => 'Bearer ' . $this->accessToken],
                'body' => $requestData,
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $settings = $data['data'] ?? null;
                
                if ($settings) {
                    // Cache the settings
                    \App\Models\SunSyncSetting::updateSettings($inverterSn, $settings);
                }
                
                return $settings;
            }
            Log::error('SunSync get inverter settings failed: API request failed');
            return null;
        } catch (\Exception $e) {
            Log::error('SunSync get inverter settings failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getInverterFlowInfo($inverterSn): ?array
    {
        if (!$this->accessToken) {
            Log::error('SunSync get inverter flow info failed: No access token');
            return null;
        }

        // Convert to string if it's not already
        $inverterSn = (string) $inverterSn;

        try {
            $response = Http::withToken($this->accessToken)
                ->get($this->baseUrl . "/api/v1/inverter/{$inverterSn}/flow");

            // Store request details with masked sensitive data
            $this->apiRequests['inverter_flow'] = $this->maskSensitiveData([
                'url' => $this->baseUrl . "/api/v1/inverter/{$inverterSn}/flow",
                'method' => 'GET',
                'headers' => ['Authorization' => 'Bearer ' . $this->accessToken],
                'body' => [],
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? null;
            }
            Log::error('SunSync get inverter flow info failed: API request failed');
            return null;
        } catch (\Exception $e) {
            Log::error('SunSync get inverter flow info failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getApiRequests(): array
    {
        return $this->apiRequests;
    }

    public function updateSystemModeSettings(string $inverterSn, array $settings): bool
    {
        if (!$this->accessToken) {
            Log::error('SunSync update system mode settings failed: No access token');
            return false;
        }

        try {
            // Get current settings first
            $currentSettings = $this->getInverterSettings($inverterSn);
            if (!$currentSettings) {
                Log::error('SunSync update system mode settings failed: Could not get current settings');
                return false;
            }

            // Merge new settings with existing settings
            $mergedSettings = array_merge($currentSettings, $settings);

            $response = Http::withToken($this->accessToken)
                ->post($this->baseUrl . "/api/v1/common/setting/{$inverterSn}/set", $mergedSettings);

            if ($response->successful()) {
                $data = $response->json();
                $success = $data['success'] ?? false;
                
                if ($success) {
                    // Update cached settings after successful API call
                    \App\Models\SunSyncSetting::updateSettings($inverterSn, $mergedSettings);
                }
                
                return $success;
            }
            
            Log::error('SunSync update system mode settings failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('SunSync update system mode settings failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Return sample plant information for testing or when API credentials are not available.
     *
     * @return array
     */
    private function getSamplePlantInfo(): array
    {
        return [
            'id' => '123456',
            'name' => 'Demo Plant',
            'thumbUrl' => 'https://example.com/thumb.jpg',
            'status' => 1,
            'address' => '123 Solar Way, Sunny Town',
            'pac' => 5000,
            'efficiency' => 0.85,
            'etoday' => 15.75,
            'etotal' => 12500.5,
            'updateAt' => date('Y-m-d\TH:i:s\Z'),
            'createAt' => '2023-01-01T00:00:00.000+00:00'
        ];
    }

    /**
     * Return sample inverter information for testing or when API credentials are not available.
     *
     * @return array
     */
    private function getSampleInverterInfo(): array
    {
        return [
            'id' => '98765',
            'sn' => 'DEMO54321',
            'alias' => 'Demo Inverter',
            'gsn' => 'E123456789',
            'status' => 1,
            'type' => 2,
            'commTypeName' => 'RS485',
            'version' => [
                'masterVer' => '3.4.5.6',
                'softVer' => '1.6.7.8',
                'hardVer' => '2.0.0',
                'hmiVer' => 'E.5.6.7',
                'bmsVer' => '1.2.3'
            ],
            'model' => 'SunSync 5kW',
            'pac' => 3500,
            'etoday' => 12.5,
            'etotal' => 9876.5,
            'updateAt' => date('Y-m-d\TH:i:s\Z')
        ];
    }

    /**
     * Return sample inverter settings for testing or when API credentials are not available.
     *
     * @return array
     */
    private function getSampleInverterSettings(): array
    {
        return [
            'sn' => 'DEMO54321',
            'safetyType' => '0',
            'battMode' => '-1',
            'solarSell' => '1',
            'pvMaxLimit' => '5000',
            'energyMode' => '1',
            'sysWorkMode' => '2',
            'batteryOn' => '1',
            'chargeVolt' => '56.1',
            'floatVolt' => '51.5',
            'zeroExportPower' => '20',
            'solarMaxSellPower' => '6500'
        ];
    }

    /**
     * Return sample inverter flow information for testing or when API credentials are not available.
     *
     * @return array
     */
    private function getSampleInverterFlowInfo(): array
    {
        return [
            'custCode' => 29,
            'meterCode' => 0,
            'pvPower' => 4500,
            'battPower' => 1200,
            'gridOrMeterPower' => 3000,
            'loadOrEpsPower' => 600,
            'genPower' => 0,
            'minPower' => 0,
            'soc' => 85.0,
            'smartLoadPower' => 200,
            'upsLoadPower' => 150,
            'homeLoadPower' => 250,
            'pvTo' => true,
            'toLoad' => true,
            'toSmartLoad' => true,
            'toUpsLoad' => true,
            'toHomeLoad' => true,
            'toGrid' => true,
            'toBat' => true,
            'batTo' => false,
            'gridTo' => false,
            'genTo' => false,
            'minTo' => false,
            'existsGen' => false,
            'existsMin' => false,
            'existsGrid' => true,
            'genOn' => false,
            'microOn' => false,
            'existsMeter' => false,
            'bmsCommFaultFlag' => null,
            'existsThreeLoad' => true,
            'existsSmartLoad' => true,
            'pv' => [
                [
                    'power' => 2300,
                    'toInv' => true
                ],
                [
                    'power' => 2200,
                    'toInv' => true
                ]
            ],
            'existThinkPower' => false
        ];
    }
} 
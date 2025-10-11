<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class SunSyncService
{
    private string $baseUrl = 'https://api.sunsynk.net';
    private ?string $accessToken = null;
    private array $apiRequests = [];
    private DataMaskingService $dataMaskingService;

    public function __construct(DataMaskingService $dataMaskingService)
    {
        $this->accessToken = Cache::get('sunsynk_access_token');
        $this->dataMaskingService = $dataMaskingService;
    }

    /**
     * Mask sensitive data in request/response arrays
     */
    private function maskSensitiveData(array $data): array
    {
        return $this->dataMaskingService->maskSensitiveData($data);
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
                
                // Check for different API response formats
                if (isset($data['data']['access_token'])) {
                    $this->accessToken = $data['data']['access_token'];
                    // Store token with proper expiration
                    $expiresIn = $data['data']['expires_in'] ?? 3600; // Default to 1 hour if not provided
                    Cache::put('sunsynk_access_token', $this->accessToken, now()->addSeconds($expiresIn - 60)); // Subtract a minute for safety
                    return $data;
                } elseif (isset($data['access_token'])) {
                    // Alternative format sometimes returned by OAuth servers
                    $this->accessToken = $data['access_token'];
                    // Store token with proper expiration
                    $expiresIn = $data['expires_in'] ?? 3600; // Default to 1 hour if not provided
                    Cache::put('sunsynk_access_token', $this->accessToken, now()->addSeconds($expiresIn - 60)); // Subtract a minute for safety
                    return $data;
                }
            }

            // Get detailed error information
            $errorData = $response->json();
            $errorMessage = 'Authentication failed';
            
            // Check various error message formats
            if (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } elseif (isset($errorData['error'])) {
                $errorMessage = $errorData['error'];
            } elseif (isset($errorData['error_description'])) {
                $errorMessage = $errorData['error_description'];
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
        } catch (ConnectionException $e) {
            Log::error('SunSync connection failed', [
                'error' => $e->getMessage()
            ]);
            return [
                'error' => true,
                'message' => 'Unable to connect to SunSync API. Please check your internet connection.',
                'status' => 503
            ];
        } catch (RequestException $e) {
            Log::error('SunSync request failed', [
                'error' => $e->getMessage(),
                'status' => $e->response?->status()
            ]);
            return [
                'error' => true,
                'message' => 'SunSync API request failed: ' . $e->getMessage(),
                'status' => $e->response?->status() ?? 500
            ];
        } catch (\Exception $e) {
            Log::critical('Unexpected SunSync authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'error' => true,
                'message' => 'Unexpected error during authentication',
                'status' => 500
            ];
        }
    }

    /**
     * Ensure the service is authenticated before making API calls
     */
    private function ensureAuthenticated(): bool
    {
        if ($this->accessToken) {
            return true;
        }
        
        Log::info('SunSync access token missing, attempting to authenticate');
        $username = config('services.sunsync.username');
        $password = config('services.sunsync.password');
        
        if (empty($username) || empty($password)) {
            Log::error('SunSync authentication failed: Missing credentials');
            return false;
        }
        
        $authResult = $this->authenticate($username, $password);
        return $authResult && !isset($authResult['error']) && $this->accessToken !== null;
    }

    public function getPlantInfo(): ?array
    {
        if (!$this->ensureAuthenticated()) {
            Log::error('SunSync get plant info failed: Authentication failed');
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

            $responseData = $response->json();
            
            // If we get an unauthorized response, try to re-authenticate once
            if ($response->status() === 401) {
                Log::info('SunSync access token expired, re-authenticating');
                $username = config('services.sunsync.username');
                $password = config('services.sunsync.password');
                
                $authResult = $this->authenticate($username, $password);
                if (!$authResult || isset($authResult['error']) || !$this->accessToken) {
                    Log::error('SunSync get plant info failed: Re-authentication failed');
                    return null;
                }
                
                // Try the request again with the new token
                $response = Http::withToken($this->accessToken)
                    ->get($this->baseUrl . '/api/v1/plants', $requestData);
                    
                $responseData = $response->json();
            }
            
            // Create a deep copy for the API requests log that will be masked
            $responseDataForLog = json_decode(json_encode($responseData), true);
            
            // Explicitly mask IDs in the plant info response displayed in logs
            if (isset($responseDataForLog['data']['infos']) && is_array($responseDataForLog['data']['infos'])) {
                foreach ($responseDataForLog['data']['infos'] as &$info) {
                    if (isset($info['id'])) {
                        $strId = (string)$info['id'];
                        if (strlen($strId) > 2) {
                            $info['id'] = substr($strId, 0, 1) . str_repeat('*', strlen($strId) - 2) . substr($strId, -1);
                        }
                    }
                }
            }
            
            // Store request details with masked sensitive data
            $this->apiRequests['plant_info'] = $this->maskSensitiveData([
                'url' => $this->baseUrl . '/api/v1/plants',
                'method' => 'GET',
                'headers' => ['Authorization' => 'Bearer ' . $this->accessToken],
                'body' => $requestData,
                'response' => $responseDataForLog
            ]);

            if ($response->successful()) {
                $data = $responseData;
                return $data['data']['infos'][0] ?? null;
            }
            
            Log::error('SunSync get plant info failed: API request failed', [
                'status' => $response->status(),
                'response' => $responseData
            ]);
            return null;
        } catch (ConnectionException $e) {
            Log::error('SunSync connection failed for plant info', [
                'error' => $e->getMessage()
            ]);
            return null;
        } catch (RequestException $e) {
            Log::error('SunSync request failed for plant info', [
                'error' => $e->getMessage(),
                'status' => $e->response?->status()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::critical('Unexpected error getting plant info', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    public function getInverterInfo($plantId): ?array
    {
        if (!$this->ensureAuthenticated()) {
            Log::error('SunSync get inverter info failed: Authentication failed');
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
                
            // If we get an unauthorized response, try to re-authenticate once
            if ($response->status() === 401) {
                Log::info('SunSync access token expired, re-authenticating for inverter info');
                $username = config('services.sunsync.username');
                $password = config('services.sunsync.password');
                
                $authResult = $this->authenticate($username, $password);
                if (!$authResult || isset($authResult['error']) || !$this->accessToken) {
                    Log::error('SunSync get inverter info failed: Re-authentication failed');
                    return null;
                }
                
                // Try the request again with the new token
                $response = Http::withToken($this->accessToken)
                    ->get($this->baseUrl . "/api/v1/plant/{$plantId}/inverters", $requestData);
            }

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
            Log::error('SunSync get inverter info failed: API request failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return null;
        } catch (ConnectionException $e) {
            Log::error('SunSync connection failed for inverter info', [
                'error' => $e->getMessage()
            ]);
            return null;
        } catch (RequestException $e) {
            Log::error('SunSync request failed for inverter info', [
                'error' => $e->getMessage(),
                'status' => $e->response?->status()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::critical('Unexpected error getting inverter info', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getInverterSettings($inverterSn): ?array
    {
        if (!$this->ensureAuthenticated()) {
            Log::error('SunSync get inverter settings failed: Authentication failed');
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
                
            // If we get an unauthorized response, try to re-authenticate once
            if ($response->status() === 401) {
                Log::info('SunSync access token expired, re-authenticating for inverter settings');
                $username = config('services.sunsync.username');
                $password = config('services.sunsync.password');
                
                $authResult = $this->authenticate($username, $password);
                if (!$authResult || isset($authResult['error']) || !$this->accessToken) {
                    Log::error('SunSync get inverter settings failed: Re-authentication failed');
                    return null;
                }
                
                // Try the request again with the new token
                $response = Http::withToken($this->accessToken)
                    ->get($this->baseUrl . "/api/v1/common/setting/{$inverterSn}/read", $requestData);
            }

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
            Log::error('SunSync get inverter settings failed: API request failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return null;
        } catch (ConnectionException $e) {
            Log::error('SunSync connection failed for inverter settings', [
                'error' => $e->getMessage()
            ]);
            return null;
        } catch (RequestException $e) {
            Log::error('SunSync request failed for inverter settings', [
                'error' => $e->getMessage(),
                'status' => $e->response?->status()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::critical('Unexpected error getting inverter settings', [
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
        } catch (ConnectionException $e) {
            Log::error('SunSync connection failed for inverter flow info', [
                'error' => $e->getMessage()
            ]);
            return null;
        } catch (RequestException $e) {
            Log::error('SunSync request failed for inverter flow info', [
                'error' => $e->getMessage(),
                'status' => $e->response?->status()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::critical('Unexpected error getting inverter flow info', [
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
        if (!$this->ensureAuthenticated()) {
            Log::error('SunSync update system mode settings failed: Authentication failed');
            return false;
        }

        try {
            // Get current settings first
            $currentSettings = $this->getInverterSettings($inverterSn);
            if (!$currentSettings) {
                Log::error('SunSync update system mode settings failed: Could not get current settings');
                return false;
            }

            // Check if specific settings have changed
            $hasChanges = false;
            
            // Only check the specific settings we care about (sn, sellTime5, cap5, time5on)
            $keysToCheck = ['sn', 'sellTime5', 'cap5', 'time5on', 'sellTime2'];
            
            foreach ($keysToCheck as $key) {
                if (!isset($settings[$key])) {
                    continue;
                }
                if ($key === 'sellTime2') {
                    $newValue = '03:00';
                } else{
                $newValue = $settings[$key];
                }
                $currentValue = $currentSettings[$key] ?? null;
                
                // Handle boolean vs string "false" comparison
                if ($key === 'time5on') {
                    // Normalize both values to boolean for comparison
                    $newValueBool = is_bool($newValue) ? $newValue : $newValue === 'true' || $newValue === '1' || $newValue === 1;
                    $currentValueBool = is_bool($currentValue) ? $currentValue : $currentValue === 'true' || $currentValue === '1' || $currentValue === 1;
                    
                    if ($newValueBool !== $currentValueBool) {
                        $hasChanges = true;
                        Log::info("SunSync setting {$key} changed: " . json_encode($currentValueBool) . " -> " . json_encode($newValueBool));
                    }
                } else if ($newValue !== $currentValue) {
                    $hasChanges = true;
                    Log::info("SunSync setting {$key} changed: {$currentValue} -> {$newValue}");
                }

            }
            
           //  If no changes, return success without making API call
            if (!$hasChanges) {
                Log::info('SunSync update not needed - no setting changes detected');
                return true;
            }

            // Merge new settings with existing settings
            $mergedSettings = array_merge($currentSettings, $settings);

            $response = Http::withToken($this->accessToken)
                ->post($this->baseUrl . "/api/v1/common/setting/{$inverterSn}/set", $mergedSettings);
                
            // If we get an unauthorized response, try to re-authenticate once
            if ($response->status() === 401) {
                Log::info('SunSync access token expired, re-authenticating for updating settings');
                $username = config('services.sunsync.username');
                $password = config('services.sunsync.password');
                
                $authResult = $this->authenticate($username, $password);
                if (!$authResult || isset($authResult['error']) || !$this->accessToken) {
                    Log::error('SunSync update system mode settings failed: Re-authentication failed');
                    return false;
                }
                
                // Try the request again with the new token
                $response = Http::withToken($this->accessToken)
                    ->post($this->baseUrl . "/api/v1/common/setting/{$inverterSn}/set", $mergedSettings);
            }

            if ($response->successful()) {
                $data = $response->json();
                $success = $data['success'] ?? false;
                
                if ($success) {
                    // Update cached settings after successful API call
                    \App\Models\SunSyncSetting::updateSettings($inverterSn, $mergedSettings);
                    Log::info('SunSync settings updated successfully');
                }
                
                return $success;
            }
            
            Log::error('SunSync update system mode settings failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return false;
        } catch (ConnectionException $e) {
            Log::error('SunSync connection failed for updating settings', [
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (RequestException $e) {
            Log::error('SunSync request failed for updating settings', [
                'error' => $e->getMessage(),
                'status' => $e->response?->status()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::critical('Unexpected error updating system mode settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
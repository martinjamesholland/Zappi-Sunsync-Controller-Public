<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MyEnergiApiService
{
    private Client $client;
    private ?string $serial;
    private ?string $password;
    private string $baseUrl;
    private array $apiRequests = [];
    private DataMaskingService $dataMaskingService;

    public function __construct(DataMaskingService $dataMaskingService)
    {
        $this->serial = config('myenergi.serial', env('ZAPPI_SERIAL'));
        $this->password = config('myenergi.password', env('ZAPPI_PASSWORD'));
        $this->baseUrl = 'https://s18.myenergi.net';
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10.0,
            'http_errors' => false,
        ]);
        $this->dataMaskingService = $dataMaskingService;
    }

    private function maskSensitiveData(array $data): array
    {
        return $this->dataMaskingService->maskSensitiveData($data);
    }

    /**
     * Get the current status of the Zappi charger.
     *
     * @return array|null
     */
    public function getStatus(): ?array
    {
        if (empty($this->serial) || empty($this->password)) {
            Log::error('MyEnergi API request failed: Missing credentials');
            return null;
        }

        $endpoint = "/cgi-jstatus-Z{$this->serial}";
        try {
            $response = $this->client->request('GET', $endpoint, [
                'auth' => [$this->serial, $this->password, 'digest'],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            // Track the API request
            $requestData = $this->maskSensitiveData([
                'url' => $this->baseUrl . $endpoint,
                'method' => 'GET',
                'headers' => [
                    'Authorization' => 'Digest ' . base64_encode($this->serial . ':' . $this->password),
                    'Accept' => 'application/json'
                ],
                'body' => null,
                'response' => json_decode($response->getBody()->getContents(), true)
            ]);
            $this->apiRequests[] = $requestData;

            if ($response->getStatusCode() === 200) {
                return $requestData['response'];
            }

            Log::warning('MyEnergi API non-200 response', [
                'status' => $response->getStatusCode(),
            ]);
            return null;
        } catch (GuzzleException $e) {
            Log::error('MyEnergi API request failed', [
                'error' => $e->getMessage(),
            ]);

            // Track the failed request
            $requestData = $this->maskSensitiveData([
                'url' => $this->baseUrl . $endpoint,
                'method' => 'GET',
                'headers' => [
                    'Authorization' => 'Digest ' . base64_encode($this->serial . ':' . $this->password),
                    'Accept' => 'application/json'
                ],
                'body' => null,
                'response' => ['error' => $e->getMessage()]
            ]);
            $this->apiRequests[] = $requestData;

            return null;
        }
    }

    private function makeRequest($endpoint, $method = 'GET', $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        $headers = [
            'Authorization' => 'Basic ' . base64_encode($this->serial . ':' . $this->password),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $requestData = [
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'body' => $data,
            'response' => null
        ];

        try {
            $response = Http::withHeaders($headers)
                ->{strtolower($method)}($url, $data);

            $requestData['response'] = $response->json();
            $this->apiRequests[] = $this->maskSensitiveData($requestData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('MyEnergi API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('MyEnergi API Exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            $requestData['response'] = ['error' => $e->getMessage()];
            $this->apiRequests[] = $this->maskSensitiveData($requestData);

            return null;
        }
    }

    public function getApiRequests(): array
    {
        return $this->apiRequests;
    }
} 
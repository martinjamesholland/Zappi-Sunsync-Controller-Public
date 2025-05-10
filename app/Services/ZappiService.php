class ZappiService
{
    private $apiRequests = [];

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
            $this->apiRequests[] = $requestData;

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Zappi API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Zappi API Exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            $requestData['response'] = ['error' => $e->getMessage()];
            $this->apiRequests[] = $requestData;

            return null;
        }
    }

    public function getApiRequests()
    {
        return $this->apiRequests;
    }
} 
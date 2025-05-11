<?php

declare(strict_types=1);

namespace App\Services;

class DataMaskingService
{
    /**
     * Mask sensitive data in array
     *
     * @param array $data The data to mask
     * @param array $fieldsToMask Field names to mask (e.g., 'sn', 'sno', 'inverterSn')
     * @return array The masked data
     */
    public function maskSensitiveData(array $data, array $fieldsToMask = []): array
    {
        $defaultFieldsToMask = [
            'sn', 'sno', 'serialNumber', 'inverterSn', 'serial', 'deviceId'
        ];
        
        $fieldsToMask = array_merge($defaultFieldsToMask, $fieldsToMask);
        
        return $this->maskRecursively($data, $fieldsToMask);
    }
    
    /**
     * Mask endpoint URLs containing sensitive data
     *
     * @param string $url The URL to mask
     * @return string The masked URL
     */
    public function maskSensitiveUrl(string $url): string
    {
        // Match SunSync API patterns like /api/v1/common/setting/2211246642/read
        $url = preg_replace(
            '/(\/api\/v1\/common\/setting\/)([0-9]+)(\/[a-zA-Z]+)/',
            '$1******$3',
            $url
        );
        
        // Match MyEnergi API patterns like /cgi-jstatus-Z12345678
        $url = preg_replace(
            '/(\/cgi-jstatus-Z)([0-9]+)/',
            '$1******',
            $url
        );
        
        return $url;
    }
    
    /**
     * Recursively mask sensitive data in nested arrays
     *
     * @param array $data The data to mask
     * @param array $fieldsToMask Fields to mask
     * @return array
     */
    private function maskRecursively(array $data, array $fieldsToMask): array
    {
        $result = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->maskRecursively($value, $fieldsToMask);
            } else if (in_array($key, $fieldsToMask) && is_scalar($value)) {
                // Mask but keep first 2 and last 2 characters
                $strValue = (string)$value;
                if (strlen($strValue) > 6) {
                    $result[$key] = substr($strValue, 0, 2) . '******' . substr($strValue, -2);
                } else {
                    $result[$key] = '******';
                }
            } else if ($key === 'endpoint' && is_string($value)) {
                $result[$key] = $this->maskSensitiveUrl($value);
            } else if ($key === 'url' && is_string($value)) {
                $result[$key] = $this->maskSensitiveUrl($value);
            } else if ($key === 'password' && is_string($value)) {
                $result[$key] = '********';
            } else if (($key === 'access_token' || $key === 'Authorization') && is_string($value)) {
                $token = $value;
                if (strpos($value, 'Bearer ') === 0) {
                    $token = substr($value, 7); // Remove 'Bearer ' prefix
                    $result[$key] = 'Bearer ********' . substr($token, -5);
                } else {
                    $result[$key] = '********' . substr($token, -5);
                }
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
} 
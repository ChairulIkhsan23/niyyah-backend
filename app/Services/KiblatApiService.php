<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class KiblatApiService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = config('islamic-api.base_urls.kiblat');
    }
    
    /**
     * Get Qibla direction for a location using Aladhan API
     */
    public function getQiblaDirection(float $latitude, float $longitude)
    {
        $cacheKey = "kiblat_{$latitude}_{$longitude}";
        
        return $this->getCachedOrFetch($cacheKey, function () use ($latitude, $longitude) {
            // Aladhan API menggunakan path parameter
            $response = $this->makeRequest('get', "qibla/{$latitude}/{$longitude}");
            
            // Cek response dari Aladhan API
            if ($response && isset($response['code']) && $response['code'] == 200) {
                return [
                    'direction' => $response['data']['direction'] ?? null,
                    'latitude' => $response['data']['latitude'] ?? $latitude,
                    'longitude' => $response['data']['longitude'] ?? $longitude
                ];
            }
            
            return null;
        }, 86400);
    }
}
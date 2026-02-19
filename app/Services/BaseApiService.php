<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseApiService
{
    protected $baseUrl;
    protected $timeout;
    protected $retryAttempts;
    protected $cachePrefix;
    
    public function __construct()
    {
        $this->timeout = config('islamic-api.timeout', 30);
        $this->retryAttempts = config('islamic-api.retry_attempts', 3);
        $this->cachePrefix = strtolower(class_basename($this));
    }
    
    /**
     * Make HTTP request with retry mechanism
     */
    protected function makeRequest(string $method, string $endpoint, array $options = [])
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        
        $attempt = 0;
        
        while ($attempt < $this->retryAttempts) {
            try {
                $response = Http::timeout($this->timeout)
                    ->withOptions($options)
                    ->$method($url);
                
                if ($response->successful()) {
                    return $response->json();
                }
                
                Log::warning("API request failed (attempt " . ($attempt + 1) . ")", [
                    'url' => $url,
                    'status' => $response->status()
                ]);
                
            } catch (\Exception $e) {
                Log::error("API request error (attempt " . ($attempt + 1) . ")", [
                    'url' => $url,
                    'error' => $e->getMessage()
                ]);
            }
            
            $attempt++;
            
            if ($attempt < $this->retryAttempts) {
                sleep(1); // Wait 1 second before retry
            }
        }
        
        return null;
    }
    
    /**
     * Get cached data or fetch from API
     */
    protected function getCachedOrFetch(string $key, callable $callback, int $duration = null)
    {
        $cacheKey = $this->cachePrefix . '_' . $key;
        $duration = $duration ?? config('islamic-api.cache_duration.default', 3600);
        
        return Cache::remember($cacheKey, $duration, $callback);
    }
    
    /**
     * Clear cache for specific key
     */
    protected function clearCache(string $key)
    {
        Cache::forget($this->cachePrefix . '_' . $key);
    }
}
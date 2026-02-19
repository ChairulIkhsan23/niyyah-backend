<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SholatApiService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = config('islamic-api.base_urls.sholat');
    }
    
    /**
     * Get list of cities
     */
    public function getCities()
    {
        return $this->getCachedOrFetch('cities', function () {
            $response = $this->makeRequest('get', 'sholat/kota/semua');
            return $response['data'] ?? [];
        }, config('islamic-api.cache_duration.quran_surah')); // Long cache for cities
    }
    
    /**
     * Get prayer schedule for a city
     */
    public function getPrayerSchedule(string $cityId, int $year, int $month)
    {
        $cacheKey = "schedule_{$cityId}_{$year}_{$month}";
        
        return $this->getCachedOrFetch($cacheKey, function () use ($cityId, $year, $month) {
            $response = $this->makeRequest('get', "sholat/jadwal/{$cityId}/{$year}/{$month}");
            
            if ($response && isset($response['data'])) {
                return [
                    'city' => $response['data']['lokasi'] ?? null,
                    'schedule' => $response['data']['jadwal'] ?? [],
                    'month' => $response['data']['bulan'] ?? null,
                    'year' => $response['data']['tahun'] ?? null
                ];
            }
            
            return null;
        }, config('islamic-api.cache_duration.sholat_jadwal'));
    }
    
    /**
     * Get today's schedule for a city
     */
    public function getTodaySchedule(string $cityId)
    {
        $today = now();
        $schedule = $this->getPrayerSchedule($cityId, $today->year, $today->month);
        
        if ($schedule && isset($schedule['schedule'])) {
            $todayDate = $today->format('Y-m-d');
            
            foreach ($schedule['schedule'] as $day) {
                if (isset($day['date']) && $day['date'] === $todayDate) {
                    return [
                        'date' => $day['tanggal'], // Untuk display
                        'imsak' => $day['imsak'],
                        'subuh' => $day['subuh'],
                        'terbit' => $day['terbit'],
                        'dhuha' => $day['dhuha'],
                        'dzuhur' => $day['dzuhur'],
                        'ashar' => $day['ashar'],
                        'maghrib' => $day['maghrib'],
                        'isya' => $day['isya']
                    ];
                }
            }
        }
        
        return null;
    }
}
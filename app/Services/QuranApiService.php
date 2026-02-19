<?php

namespace App\Services;

class QuranApiService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = config('islamic-api.base_urls.quran');
    }
    
    /**
     * Get all surah
     */
    public function getAllSurah()
    {
        return $this->getCachedOrFetch('all_surah', function () {
            $response = $this->makeRequest('get', 'chapters');
            return $response['chapters'] ?? [];
        }, config('islamic-api.cache_duration.quran_surah'));
    }
    
    /**
     * Get specific surah by ID
     */
    public function getSurah(int $surahId)
    {
        return $this->getCachedOrFetch("surah_{$surahId}", function () use ($surahId) {
            $response = $this->makeRequest('get', "chapters/{$surahId}");
            return $response['chapter'] ?? null;
        }, config('islamic-api.cache_duration.quran_surah'));
    }
    
    /**
     * Get verses of a surah
     */
    public function getSurahVerses(int $surahId, int $page = 1, int $perPage = 10)
    {
        return $this->getCachedOrFetch("surah_{$surahId}_verses_{$page}", function () use ($surahId, $page, $perPage) {
            $response = $this->makeRequest('get', "quran/verses/uthmani", [
                'query' => [
                    'chapter_number' => $surahId,
                    'page' => $page,
                    'limit' => $perPage
                ]
            ]);
            return $response['verses'] ?? [];
        }, config('islamic-api.cache_duration.quran_surah'));
    }
    
    /**
     * Search in Quran
     */
    public function searchQuran(string $query)
    {
        $cacheKey = 'search_' . md5($query);
        
        return $this->getCachedOrFetch($cacheKey, function () use ($query) {
            $response = $this->makeRequest('get', 'search', [
                'query' => ['q' => $query]
            ]);
            return $response['search'] ?? [];
        }, 3600); // Cache search for 1 hour only
    }
}
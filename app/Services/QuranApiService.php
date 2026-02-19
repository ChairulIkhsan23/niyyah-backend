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

    // ============ METHOD BARU UNTUK TRACKER ============

    /**
     * Get surah info for dropdown/selector
     */
    public function getSurahListForSelector()
    {
        $surah = $this->getAllSurah();
        
        return array_map(function($item) {
            return [
                'id' => $item['id'],
                'name' => $item['name_simple'] ?? $item['name_arabic'] ?? 'Surah ' . $item['id'],
                'name_arabic' => $item['name_arabic'] ?? '',
                'verses_count' => $item['verses_count'] ?? 0,
                'revelation_place' => $item['revelation_place'] ?? 'makkah'
            ];
        }, $surah);
    }

    /**
     * Get surah detail with basic info for bookmark display
     */
    public function getSurahBasicInfo(int $surahId)
    {
        $surah = $this->getSurah($surahId);
        
        if (!$surah) {
            return null;
        }
        
        return [
            'id' => $surah['id'],
            'name' => $surah['name_simple'] ?? $surah['name_arabic'] ?? 'Surah ' . $surah['id'],
            'name_arabic' => $surah['name_arabic'] ?? '',
            'verses_count' => $surah['verses_count'] ?? 0,
            'translated_name' => $surah['translated_name']['name'] ?? ''
        ];
    }

    /**
     * Get verse text for a specific ayah
     */
    public function getVerseText(int $surahId, int $verseNumber)
    {
        $cacheKey = "verse_text_{$surahId}_{$verseNumber}";
        
        return $this->getCachedOrFetch($cacheKey, function () use ($surahId, $verseNumber) {
            $response = $this->makeRequest('get', "quran/verses/uthmani", [
                'query' => [
                    'chapter_number' => $surahId,
                    'verse_number' => $verseNumber,
                    'limit' => 1
                ]
            ]);
            
            $verse = $response['verses'][0] ?? null;
            
            if ($verse) {
                return [
                    'text_arabic' => $verse['text_uthmani'] ?? $verse['text'] ?? '',
                    'surah_id' => $surahId,
                    'verse_number' => $verseNumber,
                    'juz' => $verse['juz_number'] ?? null,
                    'page' => $verse['page_number'] ?? null
                ];
            }
            
            return null;
        }, 86400 * 30); // Cache 30 hari
    }
}
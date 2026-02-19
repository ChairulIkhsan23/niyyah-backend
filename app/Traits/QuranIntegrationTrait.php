<?php

namespace App\Traits;

use App\Services\QuranApiService;
use Illuminate\Support\Facades\Log;

trait QuranIntegrationTrait
{
    protected $quranService;
    
    /**
     * Set Quran service (panggil di constructor)
     */
    protected function setQuranService(QuranApiService $quranService)
    {
        $this->quranService = $quranService;
    }
    
    /**
     * Get surah name by ID
     */
    protected function getSurahName(int $surahId)
    {
        try {
            $surah = $this->quranService->getSurahBasicInfo($surahId);
            return $surah['name'] ?? 'Surah ' . $surahId;
        } catch (\Exception $e) {
            Log::error('Failed to get surah name: ' . $e->getMessage());
            return 'Surah ' . $surahId;
        }
    }
    
    /**
     * Enrich bookmark with surah info
     */
    protected function enrichBookmarkWithSurahInfo($bookmark)
    {
        if (!$bookmark || !$bookmark->surah) {
            return $bookmark;
        }
        
        try {
            $surahInfo = $this->quranService->getSurahBasicInfo($bookmark->surah);
            
            $bookmark->surah_name = $surahInfo['name'] ?? null;
            $bookmark->surah_name_arabic = $surahInfo['name_arabic'] ?? null;
            $bookmark->surah_verses_count = $surahInfo['verses_count'] ?? null;
            
        } catch (\Exception $e) {
            Log::error('Failed to enrich bookmark: ' . $e->getMessage());
        }
        
        return $bookmark;
    }
    
    /**
     * Enrich Quran log with surah info
     */
    protected function enrichQuranLogWithSurahInfo($quranLog)
    {
        if (!$quranLog || !$quranLog->surah) {
            return $quranLog;
        }
        
        try {
            $surahInfo = $this->quranService->getSurahBasicInfo($quranLog->surah);
            
            $quranLog->surah_name = $surahInfo['name'] ?? null;
            $quranLog->surah_name_arabic = $surahInfo['name_arabic'] ?? null;
            
            // Jika ada ayat, ambil teks ayat
            if ($quranLog->ayah) {
                $verse = $this->quranService->getVerseText($quranLog->surah, $quranLog->ayah);
                $quranLog->verse_text = $verse['text_arabic'] ?? null;
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to enrich quran log: ' . $e->getMessage());
        }
        
        return $quranLog;
    }
    
    /**
     * Get list of surah for dropdown
     */
    protected function getSurahSelector()
    {
        try {
            return $this->quranService->getSurahListForSelector();
        } catch (\Exception $e) {
            Log::error('Failed to get surah selector: ' . $e->getMessage());
            
            // Fallback: return basic list
            return array_map(function($i) {
                return [
                    'id' => $i,
                    'name' => 'Surah ' . $i,
                    'name_arabic' => '',
                    'verses_count' => 0
                ];
            }, range(1, 114));
        }
    }
}
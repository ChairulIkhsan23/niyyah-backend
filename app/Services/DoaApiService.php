<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class DoaApiService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = config('islamic-api.base_urls.doa');
    }
    
    /**
     * Get all daily prayers from Otang API
     * Endpoint: GET /v1/doa
     */
    public function getDailyPrayers()
    {
        return $this->getCachedOrFetch('daily_prayers', function () {
            try {
                // Panggil endpoint /v1/doa
                $response = $this->makeRequest('get', '/v1/doa');
                
                // Cek response dari API Otang
                if ($response && isset($response['status']) && $response['status'] == 200) {
                    return $this->formatDoaResponse($response['data']);
                }
                
                Log::warning('Otang API response invalid', ['response' => $response]);
                return $this->getLocalDailyPrayers();
                
            } catch (\Exception $e) {
                Log::error('Error fetching daily prayers: ' . $e->getMessage());
                return $this->getLocalDailyPrayers();
            }
        }, 86400); // Cache 24 jam
    }
    
    /**
     * Get prayers by source/kategori
     * Endpoint: GET /v1/doa?source={source}
     */
    public function getPrayersBySource(string $source)
    {
        return $this->getCachedOrFetch("prayers_source_{$source}", function () use ($source) {
            try {
                $response = $this->makeRequest('get', "/v1/doa?source={$source}");
                
                if ($response && isset($response['status']) && $response['status'] == 200) {
                    return $this->formatDoaResponse($response['data']);
                }
                
                return [];
                
            } catch (\Exception $e) {
                Log::error("Error fetching prayers by source {$source}: " . $e->getMessage());
                return [];
            }
        }, 86400);
    }
    
    /**
     * Search prayers by title
     * Endpoint: GET /v1/doa/find?query={query}
     */
    public function searchPrayers(string $query)
    {
        $cacheKey = 'search_' . md5($query);
        
        return $this->getCachedOrFetch($cacheKey, function () use ($query) {
            try {
                $response = $this->makeRequest('get', "/v1/doa/find?query=" . urlencode($query));
                
                if ($response && isset($response['status']) && $response['status'] == 200) {
                    return $this->formatDoaResponse($response['data']);
                }
                
                return [];
                
            } catch (\Exception $e) {
                Log::error("Error searching prayers for '{$query}': " . $e->getMessage());
                return [];
            }
        }, 3600); // Cache search 1 jam
    }
    
    /**
     * Format response from Otang API to match app structure
     */
    private function formatDoaResponse($data)
    {
        if (empty($data) || !is_array($data)) {
            return [];
        }
        
        $formatted = [];
        
        foreach ($data as $index => $item) {
            // Struktur dari API Otang: 
            // { "judul": "...", "arab": "...", "indo": "...", "source": "..." }
            $formatted[] = [
                'id' => $item['id'] ?? ($index + 1),
                'judul' => $item['judul'] ?? 'Doa',
                'arabic' => $item['arab'] ?? '',  // API pakai 'arab' → kita mapping ke 'arabic'
                'latin' => $item['latin'] ?? '',   // API tidak punya latin? Bisa dikosongkan
                'arti' => $item['indo'] ?? '',     // API pakai 'indo' → kita mapping ke 'arti'
                'source' => $item['source'] ?? 'umum',
                'kategori' => $item['source'] ?? 'Doa Harian'
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Get morning/evening prayers (dzikir pagi petang)
     * Endpoint: GET /v1/dzikir/pagi-petang (asumsi)
     */
    public function getMorningEveningPrayers()
    {
        return $this->getCachedOrFetch('morning_evening', function () {
            try {
                // Coba panggil endpoint dzikir pagi petang
                $response = $this->makeRequest('get', '/v1/dzikir/pagi-petang');
                
                if ($response && isset($response['status']) && $response['status'] == 200) {
                    return $this->formatDzikirResponse($response['data']);
                }
                
            } catch (\Exception $e) {
                Log::error('Error fetching morning evening prayers: ' . $e->getMessage());
            }
        }, 86400);
    }
    
    /**
     * Format dzikir response
     */
    private function formatDzikirResponse($data)
    {
        if (empty($data) || !is_array($data)) {
            return [];
        }
        
        $formatted = [];
        
        foreach ($data as $index => $item) {
            $formatted[] = [
                'id' => $item['id'] ?? ($index + 1),
                'waktu' => $item['waktu'] ?? ($item['kategori'] ?? 'Pagi/Petang'),
                'judul' => $item['judul'] ?? 'Dzikir',
                'arabic' => $item['arab'] ?? $item['lafadz'] ?? '',
                'latin' => $item['latin'] ?? '',
                'arti' => $item['indo'] ?? $item['terjemah'] ?? '',
                'jumlah' => $item['jumlah'] ?? null,
                'source' => $item['source'] ?? 'Hadits Shahih'
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Local backup data for daily prayers
     */
    private function getLocalDailyPrayers()
    {
        return [
            [
                'id' => 1,
                'judul' => 'Doa Sebelum Tidur',
                'arabic' => 'بِسْمِكَ اللَّهُمَّ أَحْيَا وَأَمُوتُ',
                'latin' => 'Bismika allahumma ahya wa amut',
                'arti' => 'Dengan nama-Mu Ya Allah aku hidup dan aku mati',
                'source' => 'harian'
            ],
            [
                'id' => 2,
                'judul' => 'Doa Bangun Tidur',
                'arabic' => 'الْحَمْدُ لِلَّهِ الَّذِي أَحْيَانَا بَعْدَ مَا أَمَاتَنَا وَإِلَيْهِ النُّشُورُ',
                'latin' => 'Alhamdulillahilladzi ahyaana ba\'da ma amaatana wa ilaihin nusyur',
                'arti' => 'Segala puji bagi Allah yang menghidupkan kami setelah mematikan kami',
                'source' => 'harian'
            ],
            [
                'id' => 3,
                'judul' => 'Doa Masuk Kamar Mandi',
                'arabic' => 'اللَّهُمَّ إِنِّي أَعُوذُ بِكَ مِنَ الْخُبُثِ وَالْخَبَائِثِ',
                'latin' => 'Allahumma inni a\'udzu bika minal khubutsi wal khabaits',
                'arti' => 'Ya Allah, aku berlindung kepada-Mu dari gangguan setan laki-laki dan setan perempuan',
                'source' => 'harian'
            ]
        ];
    }
}
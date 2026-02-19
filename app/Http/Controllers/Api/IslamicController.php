<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QuranApiService;
use App\Services\SholatApiService;
use App\Services\DoaApiService;
use App\Services\KiblatApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IslamicController extends Controller
{
    protected $quranService;
    protected $sholatService;
    protected $doaService;
    protected $kiblatService;
    
    public function __construct(
        QuranApiService $quranService,
        SholatApiService $sholatService,
        DoaApiService $doaService,
        KiblatApiService $kiblatService
    ) {
        $this->quranService = $quranService;
        $this->sholatService = $sholatService;
        $this->doaService = $doaService;
        $this->kiblatService = $kiblatService;
    }
    
    /**
     * @OA\Get(
     *     path="/islamic/quran/surah",
     *     summary="Get all surah",
     *     description="Mengambil daftar semua surah dalam Al-Quran",
     *     operationId="getAllSurah",
     *     tags={"Islamic - Quran"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Daftar surah berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar surah berhasil diambil"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getSurah()
    {
        try {
            $surah = $this->quranService->getAllSurah();
            
            return response()->json([
                'success' => true,
                'message' => 'Daftar surah berhasil diambil',
                'data' => $surah
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching surah: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar surah'
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/islamic/quran/surah/{id}",
     *     summary="Get surah detail",
     *     description="Mengambil detail surah berdasarkan ID",
     *     operationId="getSurahDetail",
     *     tags={"Islamic - Quran"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID surah (1-114)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detail surah berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Detail surah berhasil diambil"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Surah tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Surah tidak ditemukan")
     *         )
     *     )
     * )
     */
    public function getSurahDetail($id)
    {
        try {
            $surah = $this->quranService->getSurah($id);
            
            if (!$surah) {
                return response()->json([
                    'success' => false,
                    'message' => 'Surah tidak ditemukan'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Detail surah berhasil diambil',
                'data' => $surah
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching surah detail: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail surah'
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/islamic/sholat/jadwal",
     *     summary="Get prayer schedule",
     *     description="Mengambil jadwal sholat bulanan untuk kota tertentu",
     *     operationId="getPrayerSchedule",
     *     tags={"Islamic - Sholat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         required=false,
     *         description="Nama kota (default dari profil user)",
     *         @OA\Schema(type="string", example="bandung")
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         required=false,
     *         description="Bulan (1-12, default bulan saat ini)",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         required=false,
     *         description="Tahun (default tahun saat ini)",
     *         @OA\Schema(type="integer", example=2026)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jadwal sholat berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Jadwal sholat berhasil diambil"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function getPrayerSchedule(Request $request)
    {
        try {
            $user = $request->user();
            $city = $request->input('city', $user->city);
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);
            
            // You need city ID mapping here
            $cityId = $this->getCityId($city);
            
            if (!$cityId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kota tidak ditemukan'
                ], 404);
            }
            
            $schedule = $this->sholatService->getPrayerSchedule($cityId, $year, $month);
            
            return response()->json([
                'success' => true,
                'message' => 'Jadwal sholat berhasil diambil',
                'data' => $schedule
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching prayer schedule: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil jadwal sholat'
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/islamic/sholat/hari-ini",
     *     summary="Get today's prayer schedule",
     *     description="Mengambil jadwal sholat untuk hari ini",
     *     operationId="getTodaySchedule",
     *     tags={"Islamic - Sholat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         required=false,
     *         description="Nama kota (default dari profil user)",
     *         @OA\Schema(type="string", example="bandung")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jadwal sholat hari ini berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Jadwal sholat hari ini berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="date", type="string", example="Kamis, 19/02/2026"),
     *                 @OA\Property(property="imsak", type="string", example="04:28"),
     *                 @OA\Property(property="subuh", type="string", example="04:38"),
     *                 @OA\Property(property="terbit", type="string", example="05:47"),
     *                 @OA\Property(property="dhuha", type="string", example="06:19"),
     *                 @OA\Property(property="dzuhur", type="string", example="12:07"),
     *                 @OA\Property(property="ashar", type="string", example="15:16"),
     *                 @OA\Property(property="maghrib", type="string", example="18:20"),
     *                 @OA\Property(property="isya", type="string", example="19:26")
     *             )
     *         )
     *     )
     * )
     */
    public function getTodaySchedule(Request $request)
    {
        try {
            $user = $request->user();
            $city = $request->input('city', $user->city);
            
            $cityId = $this->getCityId($city);
            
            if (!$cityId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kota tidak ditemukan'
                ], 404);
            }
            
            $today = $this->sholatService->getTodaySchedule($cityId);
            
            return response()->json([
                'success' => true,
                'message' => 'Jadwal sholat hari ini berhasil diambil',
                'data' => $today
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching today schedule: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil jadwal sholat hari ini'
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/islamic/doa/harian",
     *     summary="Get daily prayers",
     *     description="Mengambil kumpulan doa harian",
     *     operationId="getDailyPrayers",
     *     tags={"Islamic - Doa"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Doa harian berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Doa harian berhasil diambil"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getDailyPrayers()
    {
        try {
            $prayers = $this->doaService->getDailyPrayers();
            
            return response()->json([
                'success' => true,
                'message' => 'Doa harian berhasil diambil',
                'data' => $prayers
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching daily prayers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil doa harian'
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/islamic/doa/pagi-petang",
     *     summary="Get morning/evening prayers",
     *     description="Mengambil kumpulan doa pagi dan petang",
     *     operationId="getMorningEveningPrayers",
     *     tags={"Islamic - Doa"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Doa pagi petang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Doa pagi petang berhasil diambil"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getMorningEveningPrayers()
    {
        try {
            $prayers = $this->doaService->getMorningEveningPrayers();
            
            return response()->json([
                'success' => true,
                'message' => 'Doa pagi petang berhasil diambil',
                'data' => $prayers
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching morning evening prayers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil doa pagi petang'
            ], 500);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/islamic/kiblat",
     *     summary="Get Qibla direction",
     *     description="Mengambil arah kiblat berdasarkan kota user",
     *     operationId="getQiblaDirection",
     *     tags={"Islamic - Kiblat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         required=false,
     *         description="Nama kota (default dari profil user)",
     *         @OA\Schema(type="string", example="bandung")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Arah kiblat berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Arah kiblat berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="direction", type="number", format="float", example=295.1672751348155),
     *                 @OA\Property(property="latitude", type="number", format="float", example=-6.9175),
     *                 @OA\Property(property="longitude", type="number", format="float", example=107.6191)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Koordinat kota tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Koordinat kota tidak ditemukan")
     *         )
     *     )
     * )
     */
    public function getQiblaDirection(Request $request)
    {
        try {
            $user = $request->user();
            $city = $request->input('city', $user->city);
            
            // You need latitude/longitude for the city
            $coordinates = $this->getCityCoordinates($city);
            
            if (!$coordinates) {
                return response()->json([
                    'success' => false,
                    'message' => 'Koordinat kota tidak ditemukan'
                ], 404);
            }
            
            $qibla = $this->kiblatService->getQiblaDirection(
                $coordinates['lat'],
                $coordinates['lng']
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Arah kiblat berhasil diambil',
                'data' => $qibla
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching qibla direction: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil arah kiblat'
            ], 500);
        }
    }
    
    /**
     * Helper: Get city ID from city name
     */
    private function getCityId($city)
    {
        // Simple mapping for demo - you should have proper mapping
        $cities = [
            // BANTEN
            'cilegon' => '1105',
            'serang' => '1106',
            'tangerang' => '1107',
            'tangerang selatan' => '1108',
            
            // JAWA BARAT
            'bandung' => '1219',           // KOTA BANDUNG
            'kab bandung' => '1201',        // KAB. BANDUNG
            'bandung barat' => '1202',
            'bekasi' => '1221',             // KOTA BEKASI
            'kab bekasi' => '1203',
            'bogor' => '1222',              // KOTA BOGOR
            'kab bogor' => '1204',
            'depok' => '1225',
            'cimahi' => '1223',
            'cirebon' => '1224',            // KOTA CIREBON
            'kab cirebon' => '1207',
            'sukabumi' => '1226',            // KOTA SUKABUMI
            'kab sukabumi' => '1216',
            'tasikmalaya' => '1227',         // KOTA TASIKMALAYA
            'kab tasikmalaya' => '1218',
            
            // DKI JAKARTA
            'jakarta' => '1301',             // KOTA JAKARTA
            'jakarta pusat' => '1301',
            'jakarta utara' => '1301',
            'jakarta barat' => '1301',
            'jakarta selatan' => '1301',
            'jakarta timur' => '1301',
            'kepulauan seribu' => '1302',
            
            // JAWA TENGAH
            'semarang' => '1433',            // KOTA SEMARANG
            'kab semarang' => '1423',
            'surakarta' => '1434',           // KOTA SOLO
            'solo' => '1434',
            'magelang' => '1430',            // KOTA MAGELANG
            'kab magelang' => '1416',
            'pekalongan' => '1431',          // KOTA PEKALONGAN
            'kab pekalongan' => '1418',
            'tegal' => '1435',               // KOTA TEGAL
            'kab tegal' => '1426',
            'banjarnegara' => '1401',        // KAB. BANJARNEGARA
            'cilacap' => '1407',
            'purwokerto' => '1402',          // KAB. BANYUMAS (Purwokerto)
            
            // YOGYAKARTA
            'yogyakarta' => '1505',          // KOTA YOGYAKARTA
            'jogja' => '1505',
            'sleman' => '1504',
            'bantul' => '1501',
            'gunungkidul' => '1502',
            'kulon progo' => '1503',
            
            // JAWA TIMUR
            'surabaya' => '1638',            // KOTA SURABAYA
            'malang' => '1634',              // KOTA MALANG
            'kab malang' => '1614',
            'batu' => '1630',
            'kediri' => '1632',              // KOTA KEDIRI
            'kab kediri' => '1609',
            'blitar' => '1631',              // KOTA BLITAR
            'kab blitar' => '1603',
            'madiun' => '1633',               // KOTA MADIUN
            'kab madiun' => '1612',
            'mojokerto' => '1635',            // KOTA MOJOKERTO
            'kab mojokerto' => '1615',
            'pasuruan' => '1636',             // KOTA PASURUAN
            'kab pasuruan' => '1620',
            'probolinggo' => '1637',          // KOTA PROBOLINGGO
            'kab probolinggo' => '1622',
            
            // BALI
            'denpasar' => '1709',
            'badung' => '1701',
            'buleleng' => '1703',
            'gianyar' => '1704',
            'tabanan' => '1708',
            
            // LAMPUNG
            'bandar lampung' => '1014',
            'lampung' => '1014',
            'metro' => '1015',
            
            // SUMATERA UTARA
            'medan' => '0228',
            'binjai' => '0226',
            'pematangsiantar' => '0230',
            'tebing tinggi' => '0233',
            
            // RIAU
            'pekanbaru' => '0412',
            'dumai' => '0411',
            
            // KEPULAUAN RIAU
            'batam' => '0506',
            'tanjung pinang' => '0507',
            
            // JAMBI
            'jambi' => '0610',
            'sungai penuh' => '0611',
            
            // BENGKULU
            'bengkulu' => '0710',
            
            // SUMATERA SELATAN
            'palembang' => '0816',
            'lubuklinggau' => '0814',
            'prabumulih' => '0817',
            
            // BANGKA BELITUNG
            'pangkal pinang' => '0907',
            
            // KALIMANTAN
            'pontianak' => '2013',
            'singkawang' => '2014',
            'palangkaraya' => '2214',
            'banjarmasin' => '2113',
            'banjarbaru' => '2112',
            'samarinda' => '2310',
            'balikpapan' => '2308',
            'bontang' => '2309',
            'tarakan' => '2405',
            
            // SULAWESI
            'makassar' => '2622',
            'parepare' => '2624',
            'palopo' => '2623',
            'manado' => '2914',
            'bitung' => '2912',
            'kotamobagu' => '2913',
            'tomohon' => '2915',
            'palu' => '2813',
            'gorontalo' => '2506',
            'kendari' => '2717',
            'bau bau' => '2716',
            
            // MALUKU
            'ambon' => '3110',
            'tual' => '3111',
            
            // PAPUA
            'jayapura' => '3329',
            'sorong' => '3413',
        ];
        
        return $cities[strtolower($city)] ?? null;
    }
    
    /**
     * Helper: Get coordinates from city name
     */
    private function getCityCoordinates($city)
    {
        $coordinates = [
            'jakarta' => ['lat' => -6.2088, 'lng' => 106.8456],
            'bandung' => ['lat' => -6.9175, 'lng' => 107.6191],
            'surabaya' => ['lat' => -7.2575, 'lng' => 112.7521],
            'yogyakarta' => ['lat' => -7.7956, 'lng' => 110.3695],
            'semarang' => ['lat' => -6.9667, 'lng' => 110.4167],
            'medan' => ['lat' => 3.5952, 'lng' => 98.6722],
            'makassar' => ['lat' => -5.1477, 'lng' => 119.4327],
            'palembang' => ['lat' => -2.9761, 'lng' => 104.7754],
        ];
        
        return $coordinates[strtolower($city)] ?? null;
    }
}
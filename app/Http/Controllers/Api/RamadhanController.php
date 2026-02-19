<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRamadhanDayRequest;
use App\Http\Requests\StoreQuranLogRequest;
use App\Http\Requests\StoreDzikirLogRequest;
use App\Http\Requests\UpdateDzikirLogRequest;
use App\Http\Requests\StoreBookmarkRequest;
use App\Models\RamadhanDay;
use App\Models\QuranLog;
use App\Models\DzikirLog;
use App\Models\Streak;
use App\Models\Bookmark;
use App\Services\QuranApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RamadhanController extends Controller
{
    protected $quranService;
    
    public function __construct(QuranApiService $quranService)
    {
        $this->quranService = $quranService;
    }

    /**
     * @OA\Get(
     *     path="/ramadhan/today",
     *     summary="Get today's record",
     *     description="Mengambil catatan ibadah untuk hari ini",
     *     operationId="getTodayRecord",
     *     tags={"Ramadhan - Daily"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Data hari ini berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data hari ini berhasil diambil"),
     *             @OA\Property(property="data", type="object", nullable=true,
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="date", type="string", format="date", example="2026-02-19"),
     *                 @OA\Property(property="fasting", type="boolean", example=true),
     *                 @OA\Property(property="subuh", type="boolean", example=true),
     *                 @OA\Property(property="dzuhur", type="boolean", example=true),
     *                 @OA\Property(property="ashar", type="boolean", example=true),
     *                 @OA\Property(property="maghrib", type="boolean", example=true),
     *                 @OA\Property(property="isya", type="boolean", example=true),
     *                 @OA\Property(property="tarawih", type="boolean", example=true),
     *                 @OA\Property(property="quran_pages", type="integer", example=5),
     *                 @OA\Property(property="dzikir_total", type="integer", example=99),
     *                 @OA\Property(
     *                     property="quran_logs",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="surah", type="integer"),
     *                         @OA\Property(property="ayah", type="integer", nullable=true),
     *                         @OA\Property(property="pages", type="integer"),
     *                         @OA\Property(property="minutes", type="integer"),
     *                         @OA\Property(property="surah_name", type="string", example="Al-Fatihah"),
     *                         @OA\Property(property="surah_name_arabic", type="string", example="الفاتحة")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="dzikir_logs",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="type", type="string", enum={"tasbih", "tahmid", "takbir", "tahlil", "istighfar"}),
     *                         @OA\Property(property="count", type="integer")
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function today(Request $request)
    {
        $today = Carbon::today()->toDateString();
        
        $day = RamadhanDay::with(['quranLogs', 'dzikirLogs'])
            ->where('user_id', $request->user()->id)
            ->where('date', $today)
            ->first();
            
        if (!$day) {
            return response()->json([
                'success' => true,
                'message' => 'Belum ada catatan untuk hari ini',
                'data' => null
            ], 200);
        }
        
        foreach ($day->quranLogs as $log) {
            $this->enrichQuranLog($log);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Data hari ini berhasil diambil',
            'data' => $day
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/ramadhan/day/{date}",
     *     summary="Get specific day record",
     *     description="Mengambil catatan ibadah untuk tanggal tertentu",
     *     operationId="getDayRecord",
     *     tags={"Ramadhan - Daily"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="path",
     *         required=true,
     *         description="Tanggal dalam format YYYY-MM-DD",
     *         @OA\Schema(type="string", format="date", example="2026-02-19")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data berhasil diambil"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Catatan tidak ditemukan untuk tanggal ini")
     *         )
     *     )
     * )
     */
    public function getDay(Request $request, $date)
    {
        $day = RamadhanDay::with(['quranLogs', 'dzikirLogs'])
            ->where('user_id', $request->user()->id)
            ->where('date', $date)
            ->first();
            
        if (!$day) {
            return response()->json([
                'success' => false,
                'message' => 'Catatan tidak ditemukan untuk tanggal ini'
            ], 404);
        }
        
        foreach ($day->quranLogs as $log) {
            $this->enrichQuranLog($log);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diambil',
            'data' => $day
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/ramadhan/quran/surah-list",
     *     summary="Get surah list for selector",
     *     description="Mengambil daftar surah untuk dropdown",
     *     operationId="getSurahList",
     *     tags={"Ramadhan - Quran Logs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Daftar surah berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Al-Fatihah"),
     *                     @OA\Property(property="name_arabic", type="string", example="الفاتحة"),
     *                     @OA\Property(property="verses_count", type="integer", example=7),
     *                     @OA\Property(property="revelation_place", type="string", example="makkah")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getSurahList()
    {
        try {
            $surah = $this->quranService->getAllSurah();
            
            $formatted = array_map(function($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name_simple'] ?? 'Surah ' . $item['id'],
                    'name_arabic' => $item['name_arabic'] ?? '',
                    'verses_count' => $item['verses_count'] ?? 0,
                    'revelation_place' => $item['revelation_place'] ?? 'makkah'
                ];
            }, $surah);
            
            return response()->json([
                'success' => true,
                'data' => $formatted
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar surah'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/ramadhan/day",
     *     summary="Create or update daily record",
     *     description="Menyimpan atau memperbarui catatan ibadah harian",
     *     operationId="storeOrUpdateDay",
     *     tags={"Ramadhan - Daily"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"date"},
     *             @OA\Property(property="date", type="string", format="date", example="2026-02-19"),
     *             @OA\Property(property="fasting", type="boolean", example=true),
     *             @OA\Property(property="subuh", type="boolean", example=true),
     *             @OA\Property(property="dzuhur", type="boolean", example=true),
     *             @OA\Property(property="ashar", type="boolean", example=true),
     *             @OA\Property(property="maghrib", type="boolean", example=true),
     *             @OA\Property(property="isya", type="boolean", example=true),
     *             @OA\Property(property="tarawih", type="boolean", example=true),
     *             @OA\Property(property="quran_pages", type="integer", example=5),
     *             @OA\Property(property="dzikir_total", type="integer", example=99)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Catatan berhasil disimpan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Catatan harian berhasil disimpan"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="date", type="array", @OA\Items(type="string", example="The date field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function storeOrUpdateDay(StoreRamadhanDayRequest $request)
    {
        $validated = $request->validated();
        
        $ramadhanYear = Carbon::parse($validated['date'])->year;
        
        $day = RamadhanDay::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'date' => $validated['date']
            ],
            [
                'ramadhan_year' => $ramadhanYear,
                'fasting' => $validated['fasting'] ?? false,
                'subuh' => $validated['subuh'] ?? false,
                'dzuhur' => $validated['dzuhur'] ?? false,
                'ashar' => $validated['ashar'] ?? false,
                'maghrib' => $validated['maghrib'] ?? false,
                'isya' => $validated['isya'] ?? false,
                'tarawih' => $validated['tarawih'] ?? false,
                'quran_pages' => $validated['quran_pages'] ?? 0,
                'dzikir_total' => $validated['dzikir_total'] ?? 0
            ]
        );

        if ($validated['fasting'] ?? false) {
            $this->updateStreak($request->user()->id, $validated['date']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Catatan harian berhasil disimpan',
            'data' => $day->load(['quranLogs', 'dzikirLogs'])
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/ramadhan/day/{ramadhanDayId}/quran",
     *     summary="Get Quran logs",
     *     description="Mengambil daftar log Quran untuk hari tertentu",
     *     operationId="getQuranLogs",
     *     tags={"Ramadhan - Quran Logs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ramadhanDayId",
     *         in="path",
     *         required=true,
     *         description="ID hari Ramadhan",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar log Quran berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar log Quran berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="surah", type="integer", example=1),
     *                     @OA\Property(property="ayah", type="integer", example=1, nullable=true),
     *                     @OA\Property(property="pages", type="integer", example=2),
     *                     @OA\Property(property="minutes", type="integer", example=10),
     *                     @OA\Property(property="surah_name", type="string", example="Al-Fatihah"),
     *                     @OA\Property(property="surah_name_arabic", type="string", example="الفاتحة"),
     *                     @OA\Property(property="surah_verses_count", type="integer", example=7)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data tidak ditemukan"
     *     )
     * )
     */
    public function getQuranLogs(Request $request, $ramadhanDayId)
    {
        $day = RamadhanDay::where('user_id', $request->user()->id)
            ->findOrFail($ramadhanDayId);
            
        $logs = $day->quranLogs;
        
        foreach ($logs as $log) {
            $this->enrichQuranLog($log);
        }
            
        return response()->json([
            'success' => true,
            'message' => 'Daftar log Quran berhasil diambil',
            'data' => $logs
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/ramadhan/day/{ramadhanDayId}/quran",
     *     summary="Add Quran log",
     *     description="Menambahkan log bacaan Quran",
     *     operationId="addQuranLog",
     *     tags={"Ramadhan - Quran Logs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ramadhanDayId",
     *         in="path",
     *         required=true,
     *         description="ID hari Ramadhan",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"surah","pages","minutes"},
     *             @OA\Property(property="surah", type="integer", example=1, description="Nomor surah (1-114)"),
     *             @OA\Property(property="ayah", type="integer", example=1, description="Nomor ayat (opsional)"),
     *             @OA\Property(property="pages", type="integer", example=2, description="Jumlah halaman"),
     *             @OA\Property(property="minutes", type="integer", example=10, description="Durasi membaca (menit)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Log Quran berhasil ditambahkan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Log Quran berhasil ditambahkan"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="surah", type="array", @OA\Items(type="string", example="The surah field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function addQuranLog(StoreQuranLogRequest $request, $ramadhanDayId)
    {
        $validated = $request->validated();

        $day = RamadhanDay::where('user_id', $request->user()->id)
            ->findOrFail($ramadhanDayId);

        DB::beginTransaction();
        try {
            $log = QuranLog::create([
                'ramadhan_day_id' => $day->id,
                'surah' => $validated['surah'],
                'ayah' => $validated['ayah'] ?? null,
                'pages' => $validated['pages'],
                'minutes' => $validated['minutes']
            ]);

            $day->increment('quran_pages', $validated['pages']);

            DB::commit();
            
            $this->enrichQuranLog($log);

            return response()->json([
                'success' => true,
                'message' => 'Log Quran berhasil ditambahkan',
                'data' => $log
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan log Quran'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/ramadhan/day/{ramadhanDayId}/quran/{logId}",
     *     summary="Delete Quran log",
     *     description="Menghapus log bacaan Quran",
     *     operationId="deleteQuranLog",
     *     tags={"Ramadhan - Quran Logs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ramadhanDayId",
     *         in="path",
     *         required=true,
     *         description="ID hari Ramadhan",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="logId",
     *         in="path",
     *         required=true,
     *         description="ID log Quran",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Log Quran berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Log Quran berhasil dihapus")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data tidak ditemukan"
     *     )
     * )
     */
    public function deleteQuranLog(Request $request, $ramadhanDayId, $logId)
    {
        $day = RamadhanDay::where('user_id', $request->user()->id)
            ->findOrFail($ramadhanDayId);
            
        $log = QuranLog::where('ramadhan_day_id', $day->id)
            ->findOrFail($logId);
            
        DB::beginTransaction();
        try {
            $day->decrement('quran_pages', $log->pages);
            $log->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Log Quran berhasil dihapus'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus log Quran'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/ramadhan/day/{ramadhanDayId}/dzikir",
     *     summary="Get dzikir logs",
     *     description="Mengambil daftar log dzikir untuk hari tertentu",
     *     operationId="getDzikirLogs",
     *     tags={"Ramadhan - Dzikir Logs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ramadhanDayId",
     *         in="path",
     *         required=true,
     *         description="ID hari Ramadhan",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daftar log dzikir berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar log dzikir berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example="tasbih"),
     *                     @OA\Property(property="count", type="integer", example=33)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data tidak ditemukan"
     *     )
     * )
     */
    public function getDzikirLogs(Request $request, $ramadhanDayId)
    {
        $day = RamadhanDay::where('user_id', $request->user()->id)
            ->findOrFail($ramadhanDayId);
            
        return response()->json([
            'success' => true,
            'message' => 'Daftar log dzikir berhasil diambil',
            'data' => $day->dzikirLogs
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/ramadhan/day/{ramadhanDayId}/dzikir",
     *     summary="Add dzikir log",
     *     description="Menambahkan log dzikir",
     *     operationId="addDzikirLog",
     *     tags={"Ramadhan - Dzikir Logs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ramadhanDayId",
     *         in="path",
     *         required=true,
     *         description="ID hari Ramadhan",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type","count"},
     *             @OA\Property(property="type", type="string", enum={"tasbih", "tahmid", "takbir", "tahlil", "istighfar"}, example="tasbih"),
     *             @OA\Property(property="count", type="integer", example=33)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Log dzikir berhasil ditambahkan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Log dzikir berhasil ditambahkan"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="type", type="array", @OA\Items(type="string", example="The type field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function addDzikirLog(StoreDzikirLogRequest $request, $ramadhanDayId)
    {
        $validated = $request->validated();

        $day = RamadhanDay::where('user_id', $request->user()->id)
            ->findOrFail($ramadhanDayId);

        DB::beginTransaction();
        try {
            $log = DzikirLog::create([
                'ramadhan_day_id' => $day->id,
                'type' => $validated['type'],
                'count' => $validated['count']
            ]);

            $day->increment('dzikir_total', $validated['count']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Log dzikir berhasil ditambahkan',
                'data' => $log
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan log dzikir'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/ramadhan/day/{ramadhanDayId}/dzikir/{logId}",
     *     summary="Update dzikir log",
     *     description="Memperbarui jumlah dzikir pada log",
     *     operationId="updateDzikirLog",
     *     tags={"Ramadhan - Dzikir Logs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ramadhanDayId",
     *         in="path",
     *         required=true,
     *         description="ID hari Ramadhan",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="logId",
     *         in="path",
     *         required=true,
     *         description="ID log dzikir",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"count"},
     *             @OA\Property(property="count", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Log dzikir berhasil diperbarui",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Log dzikir berhasil diperbarui"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data tidak ditemukan"
     *     )
     * )
     */
    public function updateDzikirLog(UpdateDzikirLogRequest $request, $ramadhanDayId, $logId)
    {
        $validated = $request->validated();

        $day = RamadhanDay::where('user_id', $request->user()->id)
            ->findOrFail($ramadhanDayId);
            
        $log = DzikirLog::where('ramadhan_day_id', $day->id)
            ->findOrFail($logId);
            
        DB::beginTransaction();
        try {
            $oldCount = $log->count;
            $log->update(['count' => $validated['count']]);
            
            $day->increment('dzikir_total', $validated['count'] - $oldCount);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Log dzikir berhasil diperbarui',
                'data' => $log
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui log dzikir'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/ramadhan/day/{ramadhanDayId}/dzikir/{logId}",
     *     summary="Delete dzikir log",
     *     description="Menghapus log dzikir",
     *     operationId="deleteDzikirLog",
     *     tags={"Ramadhan - Dzikir Logs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ramadhanDayId",
     *         in="path",
     *         required=true,
     *         description="ID hari Ramadhan",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="logId",
     *         in="path",
     *         required=true,
     *         description="ID log dzikir",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Log dzikir berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Log dzikir berhasil dihapus")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data tidak ditemukan"
     *     )
     * )
     */
    public function deleteDzikirLog(Request $request, $ramadhanDayId, $logId)
    {
        $day = RamadhanDay::where('user_id', $request->user()->id)
            ->findOrFail($ramadhanDayId);
            
        $log = DzikirLog::where('ramadhan_day_id', $day->id)
            ->findOrFail($logId);
            
        DB::beginTransaction();
        try {
            $day->decrement('dzikir_total', $log->count);
            $log->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Log dzikir berhasil dihapus'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus log dzikir'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/ramadhan/bookmarks",
     *     summary="Get bookmarks",
     *     description="Mengambil daftar bookmark user",
     *     operationId="getBookmarks",
     *     tags={"Ramadhan - Bookmarks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Daftar bookmark berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Daftar bookmark berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="surah", type="integer", example=1),
     *                     @OA\Property(property="ayah", type="integer", example=1, nullable=true),
     *                     @OA\Property(property="page", type="integer", example=1, nullable=true),
     *                     @OA\Property(property="surah_name", type="string", example="Al-Fatihah"),
     *                     @OA\Property(property="surah_name_arabic", type="string", example="الفاتحة")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getBookmarks(Request $request)
    {
        $bookmarks = Bookmark::where('user_id', $request->user()->id)
            ->orderBy('surah')
            ->orderBy('ayah')
            ->get();

        foreach ($bookmarks as $bookmark) {
            $this->enrichBookmark($bookmark);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar bookmark berhasil diambil',
            'data' => $bookmarks
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/ramadhan/bookmarks",
     *     summary="Add bookmark",
     *     description="Menambahkan bookmark ayat baru",
     *     operationId="addBookmark",
     *     tags={"Ramadhan - Bookmarks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"surah"},
     *             @OA\Property(property="surah", type="integer", example=1, description="Nomor surah (1-114)"),
     *             @OA\Property(property="ayah", type="integer", example=1, description="Nomor ayat (opsional)"),
     *             @OA\Property(property="page", type="integer", example=1, description="Nomor halaman (opsional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bookmark berhasil ditambahkan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bookmark berhasil ditambahkan"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Bookmark sudah ada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Bookmark sudah ada")
     *         )
     *     )
     * )
     */
    public function addBookmark(StoreBookmarkRequest $request)
    {
        $validated = $request->validated();

        $exists = Bookmark::where('user_id', $request->user()->id)
            ->where('surah', $validated['surah'])
            ->where('ayah', $validated['ayah'] ?? null)
            ->where('page', $validated['page'] ?? null)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Bookmark sudah ada'
            ], 409);
        }

        $bookmark = Bookmark::create([
            'user_id' => $request->user()->id,
            'surah' => $validated['surah'],
            'ayah' => $validated['ayah'] ?? null,
            'page' => $validated['page'] ?? null
        ]);

        $this->enrichBookmark($bookmark);

        return response()->json([
            'success' => true,
            'message' => 'Bookmark berhasil ditambahkan',
            'data' => $bookmark
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/ramadhan/bookmarks/{id}",
     *     summary="Delete bookmark",
     *     description="Menghapus bookmark",
     *     operationId="deleteBookmark",
     *     tags={"Ramadhan - Bookmarks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID bookmark",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bookmark berhasil dihapus",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bookmark berhasil dihapus")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Data tidak ditemukan"
     *     )
     * )
     */
    public function deleteBookmark(Request $request, $id)
    {
        $bookmark = Bookmark::where('user_id', $request->user()->id)
            ->findOrFail($id);
            
        $bookmark->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bookmark berhasil dihapus'
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/ramadhan/summary/streak",
     *     summary="Get streak information",
     *     description="Mengambil informasi streak puasa user",
     *     operationId="streakInfo",
     *     tags={"Ramadhan - Summary"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Data streak berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data streak berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="current_streak", type="integer", example=1),
     *                 @OA\Property(property="longest_streak", type="integer", example=1),
     *                 @OA\Property(property="last_active_date", type="string", format="date", example="2026-02-19")
     *             )
     *         )
     *     )
     * )
     */
    public function streakInfo(Request $request)
    {
        $streak = Streak::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_active_date' => null
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Data streak berhasil diambil',
            'data' => $streak
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/ramadhan/summary/month",
     *     summary="Get monthly summary",
     *     description="Mengambil ringkasan ibadah bulan ini",
     *     operationId="monthlySummary",
     *     tags={"Ramadhan - Summary"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Ringkasan bulanan berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Ringkasan bulanan berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="bulan", type="string", example="February 2026"),
     *                 @OA\Property(property="total_hari", type="integer", example=1),
     *                 @OA\Property(property="total_puasa", type="integer", example=1),
     *                 @OA\Property(
     *                     property="shalat",
     *                     type="object",
     *                     @OA\Property(property="subuh", type="integer", example=1),
     *                     @OA\Property(property="dzuhur", type="integer", example=1),
     *                     @OA\Property(property="ashar", type="integer", example=1),
     *                     @OA\Property(property="maghrib", type="integer", example=1),
     *                     @OA\Property(property="isya", type="integer", example=1),
     *                     @OA\Property(property="tarawih", type="integer", example=1)
     *                 ),
     *                 @OA\Property(property="total_halaman_quran", type="integer", example=5),
     *                 @OA\Property(property="total_dzikir", type="integer", example=165)
     *             )
     *         )
     *     )
     * )
     */
    public function monthlySummary(Request $request)
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $days = RamadhanDay::where('user_id', $request->user()->id)
            ->whereBetween('date', [$start, $end])
            ->get();

        $summary = [
            'bulan' => Carbon::now()->format('F Y'),
            'total_hari' => $days->count(),
            'total_puasa' => $days->where('fasting', true)->count(),
            'shalat' => [
                'subuh' => $days->where('subuh', true)->count(),
                'dzuhur' => $days->where('dzuhur', true)->count(),
                'ashar' => $days->where('ashar', true)->count(),
                'maghrib' => $days->where('maghrib', true)->count(),
                'isya' => $days->where('isya', true)->count(),
                'tarawih' => $days->where('tarawih', true)->count(),
            ],
            'total_halaman_quran' => $days->sum('quran_pages'),
            'total_dzikir' => $days->sum('dzikir_total')
        ];

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan bulanan berhasil diambil',
            'data' => $summary
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/ramadhan/summary/year/{year}",
     *     summary="Get yearly summary",
     *     description="Mengambil ringkasan ibadah untuk tahun Ramadhan tertentu",
     *     operationId="yearlySummary",
     *     tags={"Ramadhan - Summary"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="year",
     *         in="path",
     *         required=true,
     *         description="Tahun Ramadhan",
     *         @OA\Schema(type="integer", example=2026)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ringkasan tahunan berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Ringkasan tahunan berhasil diambil"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="tahun", type="integer", example=2026),
     *                 @OA\Property(property="total_hari", type="integer", example=30),
     *                 @OA\Property(property="total_puasa", type="integer", example=28),
     *                 @OA\Property(property="total_halaman_quran", type="integer", example=150),
     *                 @OA\Property(property="total_dzikir", type="integer", example=5000)
     *             )
     *         )
     *     )
     * )
     */
    public function yearlySummary(Request $request, $year)
    {
        $days = RamadhanDay::where('user_id', $request->user()->id)
            ->where('ramadhan_year', $year)
            ->get();

        $summary = [
            'tahun' => $year,
            'total_hari' => $days->count(),
            'total_puasa' => $days->where('fasting', true)->count(),
            'total_halaman_quran' => $days->sum('quran_pages'),
            'total_dzikir' => $days->sum('dzikir_total')
        ];

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan tahunan berhasil diambil',
            'data' => $summary
        ], 200);
    }

    /**
     * Enrich quran log with surah info from QuranApiService
     */
    private function enrichQuranLog($log)
    {
        if (!$log || !$log->surah) {
            return $log;
        }
        
        try {
            $surahInfo = $this->quranService->getSurah($log->surah);
            
            if ($surahInfo) {
                $log->surah_name = $surahInfo['name_simple'] ?? 'Surah ' . $log->surah;
                $log->surah_name_arabic = $surahInfo['name_arabic'] ?? '';
                $log->surah_verses_count = $surahInfo['verses_count'] ?? 0;
            }
            
        } catch (\Exception $e) {
            $log->surah_name = 'Surah ' . $log->surah;
        }
        
        return $log;
    }

    /**
     * Enrich bookmark with surah info from QuranApiService
     */
    private function enrichBookmark($bookmark)
    {
        if (!$bookmark || !$bookmark->surah) {
            return $bookmark;
        }
        
        try {
            $surahInfo = $this->quranService->getSurah($bookmark->surah);
            
            if ($surahInfo) {
                $bookmark->surah_name = $surahInfo['name_simple'] ?? 'Surah ' . $bookmark->surah;
                $bookmark->surah_name_arabic = $surahInfo['name_arabic'] ?? '';
            }
            
        } catch (\Exception $e) {
            $bookmark->surah_name = 'Surah ' . $bookmark->surah;
        }
        
        return $bookmark;
    }

    /**
     * Update streak based on fasting activity
     */
    private function updateStreak($userId, $date)
    {
        $streak = Streak::firstOrCreate(['user_id' => $userId]);
        
        $currentDate = Carbon::parse($date);
        $lastActive = $streak->last_active_date ? Carbon::parse($streak->last_active_date) : null;

        if (!$lastActive) {
            $streak->current_streak = 1;
            $streak->longest_streak = 1;
        } elseif ($lastActive->copy()->addDay()->isSameDay($currentDate)) {
            $streak->current_streak++;
            $streak->longest_streak = max($streak->longest_streak, $streak->current_streak);
        } elseif (!$lastActive->isSameDay($currentDate)) {
            $streak->current_streak = 1;
        }

        $streak->last_active_date = $date;
        $streak->save();
    }
}
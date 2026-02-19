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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RamadhanController extends Controller
{
    /**
     * Get today's record
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
        
        return response()->json([
            'success' => true,
            'message' => 'Data hari ini berhasil diambil',
            'data' => $day
        ], 200);
    }

    /**
     * Get specific day record
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
        
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diambil',
            'data' => $day
        ], 200);
    }

    /**
     * Store or update daily record (puasa, shalat, dll)
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

        // Update streak if fasting
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
     * Get Quran logs for a specific day
     */
    public function getQuranLogs(Request $request, $ramadhanDayId)
    {
        $day = RamadhanDay::where('user_id', $request->user()->id)
            ->findOrFail($ramadhanDayId);
            
        return response()->json([
            'success' => true,
            'message' => 'Daftar log Quran berhasil diambil',
            'data' => $day->quranLogs
        ], 200);
    }

    /**
     * Add Quran log to a day
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

            // Update total pages in ramadhan_day
            $day->increment('quran_pages', $validated['pages']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Log Quran berhasil ditambahkan',
                'data' => $log
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan log Quran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Quran log
     */
    public function deleteQuranLog(Request $request, $ramadhanDayId, $logId)
    {
        $day = RamadhanDay::where('user_id', $request->user()->id)
            ->findOrFail($ramadhanDayId);
            
        $log = QuranLog::where('ramadhan_day_id', $day->id)
            ->findOrFail($logId);
            
        DB::beginTransaction();
        try {
            // Decrement total pages
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
     * Get dzikir logs for a specific day
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
     * Add dzikir log to a day
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

            // Update total dzikir in ramadhan_day
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
     * Update dzikir log
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
            
            // Adjust total dzikir
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
     * Delete dzikir log
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
     * Get user bookmarks
     */
    public function getBookmarks(Request $request)
    {
        $bookmarks = Bookmark::where('user_id', $request->user()->id)
            ->orderBy('surah')
            ->orderBy('ayah')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar bookmark berhasil diambil',
            'data' => $bookmarks
        ], 200);
    }

    /**
     * Add bookmark
     */
    public function addBookmark(StoreBookmarkRequest $request)
    {
        $validated = $request->validated();

        // Check if bookmark already exists
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

        return response()->json([
            'success' => true,
            'message' => 'Bookmark berhasil ditambahkan',
            'data' => $bookmark
        ], 201);
    }

    /**
     * Delete bookmark
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
     * Get streak information
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
     * Monthly summary
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
     * Yearly summary
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
     * Private method to update streak
     */
    private function updateStreak($userId, $date)
    {
        $streak = Streak::firstOrCreate(['user_id' => $userId]);
        
        $currentDate = Carbon::parse($date);
        $lastActive = $streak->last_active_date ? Carbon::parse($streak->last_active_date) : null;

        if (!$lastActive) {
            // First streak
            $streak->current_streak = 1;
            $streak->longest_streak = 1;
        } elseif ($lastActive->copy()->addDay()->isSameDay($currentDate)) {
            // Consecutive day
            $streak->current_streak++;
            $streak->longest_streak = max($streak->longest_streak, $streak->current_streak);
        } elseif (!$lastActive->isSameDay($currentDate)) {
            // Streak broken (missed a day)
            $streak->current_streak = 1;
        }

        $streak->last_active_date = $date;
        $streak->save();
    }
}
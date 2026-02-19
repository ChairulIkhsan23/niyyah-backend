<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RamadhanController;
use App\Http\Controllers\Api\IslamicController;

/*
|--------------------------------------------------------------------------
| AUTH PUBLIC
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});


/*
|--------------------------------------------------------------------------
| AUTH PROTECTED
|--------------------------------------------------------------------------
*/

Route::prefix('auth')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', fn (Request $request) => $request->user());
    });


/*
|--------------------------------------------------------------------------
| USER PROFILE PROTECTED
|--------------------------------------------------------------------------
*/

Route::prefix('user')
    ->middleware('auth:sanctum')
    ->group(function () {
        // Profile routes
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::post('/avatar', [UserController::class, 'updateAvatar']);
        Route::put('/password', [UserController::class, 'updatePassword']);
                
        // Device management
        Route::get('/devices', [UserController::class, 'devices']);
        Route::delete('/devices/{tokenId}', [UserController::class, 'revokeDevice']);
        Route::post('/logout-all', [UserController::class, 'logoutAllDevices']);
        
        // Account management
        Route::delete('/account', [UserController::class, 'deleteAccount']);
    });


/*
|--------------------------------------------------------------------------
| RAMADHAN TRACKING PROTECTED
|--------------------------------------------------------------------------
*/

Route::prefix('ramadhan')
    ->middleware('auth:sanctum')
    ->name('ramadhan.')
    ->group(function () {
        
        // Daily tracking
        Route::get('/today', [RamadhanController::class, 'today'])->name('today');
        Route::get('/day/{date}', [RamadhanController::class, 'getDay'])->name('day.show');
        Route::post('/day', [RamadhanController::class, 'storeOrUpdateDay'])->name('day.store');
        
        // Quran logs
        Route::prefix('/day/{ramadhanDayId}/quran')->name('quran.')->group(function () {
            Route::get('/', [RamadhanController::class, 'getQuranLogs'])->name('index');
            Route::post('/', [RamadhanController::class, 'addQuranLog'])->name('store');
            Route::delete('/{logId}', [RamadhanController::class, 'deleteQuranLog'])->name('destroy');
        });
        
        // Dzikir logs
        Route::prefix('/day/{ramadhanDayId}/dzikir')->name('dzikir.')->group(function () {
            Route::get('/', [RamadhanController::class, 'getDzikirLogs'])->name('index');
            Route::post('/', [RamadhanController::class, 'addDzikirLog'])->name('store');
            Route::put('/{logId}', [RamadhanController::class, 'updateDzikirLog'])->name('update');
            Route::delete('/{logId}', [RamadhanController::class, 'deleteDzikirLog'])->name('destroy');
        });
        
        // Summary & stats
        Route::get('/summary/month', [RamadhanController::class, 'monthlySummary'])->name('summary.month');
        Route::get('/summary/streak', [RamadhanController::class, 'streakInfo'])->name('summary.streak');
        Route::get('/summary/year/{year}', [RamadhanController::class, 'yearlySummary'])->name('summary.year');
        
        // Bookmarks
        Route::get('/bookmarks', [RamadhanController::class, 'getBookmarks'])->name('bookmarks.index');
        Route::post('/bookmarks', [RamadhanController::class, 'addBookmark'])->name('bookmarks.store');
        Route::delete('/bookmarks/{id}', [RamadhanController::class, 'deleteBookmark'])->name('bookmarks.destroy');
        
    });


/*
|--------------------------------------------------------------------------
| ISLAMIC PUBLIC API (Protected - butuh login)
|--------------------------------------------------------------------------
*/

Route::prefix('islamic')
    ->middleware('auth:sanctum')
    ->group(function () {
        
        // Quran
        Route::get('/quran/surah', [IslamicController::class, 'getSurah']);
        Route::get('/quran/surah/{id}', [IslamicController::class, 'getSurahDetail']);
        
        // Prayer Schedule
        Route::get('/sholat/jadwal', [IslamicController::class, 'getPrayerSchedule']);
        Route::get('/sholat/hari-ini', [IslamicController::class, 'getTodaySchedule']);
        
        // Doa
        Route::get('/doa/harian', [IslamicController::class, 'getDailyPrayers']);
        Route::get('/doa/pagi-petang', [IslamicController::class, 'getMorningEveningPrayers']);
        
        // Qibla
        Route::get('/kiblat', [IslamicController::class, 'getQiblaDirection']);
        Route::get('/kiblat/{city}', [IslamicController::class, 'getQiblaByCity']);        
    });


/*
|--------------------------------------------------------------------------
| PROTECTED TEST
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'API OK',
            'user' => Auth::user()
        ]);
    });
});
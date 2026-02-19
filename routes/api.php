<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

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
| USER PROFILE PROTECTED (Detail endpoints untuk manajemen profile)
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
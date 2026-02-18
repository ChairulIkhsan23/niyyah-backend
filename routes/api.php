<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\AuthController;

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
| PROTECTED TEST
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/ping', function () {
        return response()->json([
            'message' => 'API OK',
            'user' => Auth::user()
        ]);
    });
});

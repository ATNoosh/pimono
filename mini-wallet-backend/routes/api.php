<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Transaction routes
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);

    // Dev-only: trigger outbox dispatcher via HTTP (requires auth)
    if (app()->environment('local')) {
        Route::post('/dev/outbox/dispatch', function () {
            $exitCode = Artisan::call('outbox:dispatch', [
                '--limit' => 200,
            ]);
            return response()->json([
                'status' => 'ok',
                'exit_code' => $exitCode,
                'output' => Artisan::output(),
            ]);
        });
    }
});

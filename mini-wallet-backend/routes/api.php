<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Broadcast;
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
    
    // Broadcasting auth endpoint
    Route::post('/broadcasting/auth', function (Request $request) {
        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');
        
        if (!$channelName || !$socketId) {
            return response()->json(['error' => 'Missing channel_name or socket_id'], 400);
        }
        
        // Verify the channel name format (should be private-user.{id})
        if (!preg_match('/^private-user\.(\d+)$/', $channelName, $matches)) {
            return response()->json(['error' => 'Invalid channel name'], 403);
        }
        
        $channelUserId = (int) $matches[1];
        $currentUserId = $request->user()->id;
        
        // Check if user is trying to access their own channel
        if ($channelUserId !== $currentUserId) {
            return response()->json(['error' => 'Unauthorized channel access'], 403);
        }
        
        // Generate Pusher auth signature
        $pusherAppKey = env('PUSHER_APP_KEY', '457df54d0b56682441fc');
        $pusherAppSecret = env('PUSHER_APP_SECRET', 'your-pusher-app-secret');
        
        $stringToSign = $socketId . ':' . $channelName;
        $signature = hash_hmac('sha256', $stringToSign, $pusherAppSecret);
        
        return response()->json([
            'auth' => $pusherAppKey . ':' . $signature
        ]);
    });

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

// Channel authorization: allow users to join their own private channel
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

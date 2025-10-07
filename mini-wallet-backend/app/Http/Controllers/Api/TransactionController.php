<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    // moved to service
    /**
     * Get transaction history and current balance for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get paginated transactions for the user
        $transactions = Transaction::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->with(['sender:id,name', 'receiver:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'balance' => $user->balance,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Create a new money transfer transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01|max:999999.99',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sender = $request->user();
        $receiverId = (int) $request->input('receiver_id');
        $amount = (float) $request->input('amount');

        if ($receiverId == $sender->id) {
            return response()->json([
                'message' => 'Cannot transfer money to yourself',
            ], 422);
        }

        $idempotencyKey = $request->header('Idempotency-Key') ?? $request->input('idempotency_key');

        $service = app(\App\Services\TransferService::class);

        try {
            $transaction = $service->executeTransfer($sender, $receiverId, $amount, $idempotencyKey);

            return response()->json([
                'message' => 'Transfer completed successfully',
                'transaction' => $transaction->load(['sender:id,name', 'receiver:id,name']),
                'new_balance' => $sender->fresh()->balance,
            ], 201);
        } catch (\Throwable $e) {
            $status = $e->getCode() == 422 ? 422 : 500;
            return response()->json([
                'message' => $status === 422 ? 'Insufficient balance' : 'Transfer failed',
                'error' => $e->getMessage(),
            ], $status);
        }
    }
}

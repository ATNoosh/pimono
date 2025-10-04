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
        $receiverId = $request->input('receiver_id');
        $amount = (float) $request->input('amount');

        // Validate receiver exists and is not the same as sender
        if ($receiverId == $sender->id) {
            return response()->json([
                'message' => 'Cannot transfer money to yourself',
            ], 422);
        }

        $receiver = User::findOrFail($receiverId);

        // Calculate commission and total amount
        $commissionFee = Transaction::calculateCommission($amount);
        $totalAmount = Transaction::calculateTotalAmount($amount);

        // Check if sender has sufficient balance
        if (!$sender->hasSufficientBalance($totalAmount)) {
            return response()->json([
                'message' => 'Insufficient balance',
                'required' => $totalAmount,
                'available' => $sender->balance,
            ], 422);
        }

        try {
            // Use database transaction for atomicity
            $transaction = DB::transaction(function () use ($sender, $receiver, $amount, $commissionFee, $totalAmount) {
                // Create transaction record
                $transaction = Transaction::create([
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'amount' => $amount,
                    'commission_fee' => $commissionFee,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                ]);

                // Update balances atomically
                $sender->deductBalance($totalAmount);
                $receiver->addBalance($amount);

                // Mark transaction as completed
                $transaction->markAsCompleted();

                return $transaction;
            });

            // Broadcast real-time event
            event(new \App\Events\TransactionCompleted($transaction));

            return response()->json([
                'message' => 'Transfer completed successfully',
                'transaction' => $transaction->load(['sender:id,name', 'receiver:id,name']),
                'new_balance' => $sender->fresh()->balance,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Transfer failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

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
    private function isTransientTransactionError(\Throwable $e): bool
    {
        $message = $e->getMessage();
        // MySQL deadlock or lock wait timeout, PostgreSQL serialization failure
        return str_contains($message, 'Deadlock found')
            || str_contains($message, 'Lock wait timeout exceeded')
            || str_contains($message, 'could not serialize access due to')
            || str_contains($message, 'deadlock detected');
    }
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

        // Idempotency support
        $idempotencyKey = $request->header('Idempotency-Key') ?? $request->input('idempotency_key');

        // Retry on deadlocks/transient errors
        $maxAttempts = 3;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $transaction = DB::transaction(function () use ($sender, $receiver, $amount, $commissionFee, $totalAmount, $idempotencyKey) {
                    // Lock user rows to avoid race conditions
                    $lockedSender = User::where('id', $sender->id)->lockForUpdate()->firstOrFail();
                    $lockedReceiver = User::where('id', $receiver->id)->lockForUpdate()->firstOrFail();

                    // Re-check sufficient balance under lock
                    if (!$lockedSender->hasSufficientBalance($totalAmount)) {
                        abort(422, 'Insufficient balance');
                    }

                    // Basic idempotency: if the same key exists, return previous transaction
                    if ($idempotencyKey) {
                        $existing = DB::table('idempotency_keys')->where('key', $idempotencyKey)->first();
                        if ($existing && $existing->transaction_id) {
                            return Transaction::with(['sender:id,name', 'receiver:id,name'])->findOrFail($existing->transaction_id);
                        }
                    }

                    // Create transaction record
                    $transaction = Transaction::create([
                        'sender_id' => $lockedSender->id,
                        'receiver_id' => $lockedReceiver->id,
                        'amount' => $amount,
                        'commission_fee' => $commissionFee,
                        'total_amount' => $totalAmount,
                        'status' => 'pending',
                    ]);

                    // Update balances atomically
                    $lockedSender->deductBalance($totalAmount);
                    $lockedReceiver->addBalance($amount);

                    // Mark transaction as completed
                    $transaction->markAsCompleted();

                    // Store idempotency record inside the same transaction
                    if ($idempotencyKey) {
                        DB::table('idempotency_keys')->updateOrInsert(
                            ['key' => $idempotencyKey],
                            ['user_id' => $lockedSender->id, 'transaction_id' => $transaction->id, 'updated_at' => now(), 'created_at' => now()]
                        );
                    }

                    // Write to outbox for reliable dispatch
                    DB::table('outbox_messages')->insert([
                        'type' => 'transaction.completed',
                        'payload' => json_encode(['transaction_id' => $transaction->id]),
                        'available_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Also dispatch immediately after commit for real-time UX in dev/local
                    DB::afterCommit(function () use ($transaction) {
                        event(new \App\Events\TransactionCompleted($transaction));
                    });

                    return $transaction;
                });

                return response()->json([
                    'message' => 'Transfer completed successfully',
                    'transaction' => $transaction->load(['sender:id,name', 'receiver:id,name']),
                    'new_balance' => $sender->fresh()->balance,
                ], 201);
            } catch (\Throwable $e) {
                if ($this->isTransientTransactionError($e) && $attempt < $maxAttempts) {
                    usleep(100000 * $attempt); // backoff
                    continue;
                }
                $status = $e->getCode() == 422 ? 422 : 500;
                return response()->json([
                    'message' => $status === 422 ? 'Insufficient balance' : 'Transfer failed',
                    'error' => $e->getMessage(),
                ], $status);
            }
        }
        // Should not reach here
        return response()->json(['message' => 'Transfer failed'], 500);
    }
}

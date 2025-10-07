<?php

namespace App\Services;

use App\Events\TransactionCompleted;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\IdempotencyRepository;
use App\Repositories\OutboxRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TransactionRepository $transactionRepository,
        private readonly IdempotencyRepository $idempotencyRepository,
        private readonly OutboxRepository $outboxRepository,
    ) {}

    public function executeTransfer(User $sender, int $receiverId, float $amount, ?string $idempotencyKey = null): Transaction
    {
        $receiver = User::findOrFail($receiverId);
        [$commissionFee, $totalAmount] = $this->calculateAmounts($amount);
        $startedAt = microtime(true);
        try {
            return $this->runWithRetry(function () use ($sender, $receiver, $amount, $commissionFee, $totalAmount, $idempotencyKey) {
                return $this->performTransfer($sender, $receiver, $amount, $commissionFee, $totalAmount, $idempotencyKey);
            });
        } finally {
            $durationMs = (int) ((microtime(true) - $startedAt) * 1000);
            Log::info('transfer.executed', [
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'amount' => $amount,
                'duration_ms' => $durationMs,
            ]);
        }
    }

    private function isTransientTransactionError(\Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'Deadlock found')
            || str_contains($message, 'Lock wait timeout exceeded')
            || str_contains($message, 'could not serialize access due to')
            || str_contains($message, 'deadlock detected');
    }

    private function calculateAmounts(float $amount): array
    {
        $commissionFee = Transaction::calculateCommission($amount);
        $totalAmount = Transaction::calculateTotalAmount($amount);

        return [$commissionFee, $totalAmount];
    }

    private function runWithRetry(\Closure $callback): Transaction
    {
        $maxAttempts = 3;
        $lastException = null;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $callback();
            } catch (\Throwable $e) {
                $lastException = $e;
                if ($this->isTransientTransactionError($e) && $attempt < $maxAttempts) {
                    Log::warning('transfer.retry', [
                        'attempt' => $attempt,
                        'error' => $e->getMessage(),
                    ]);
                    usleep(100000 * $attempt);

                    continue;
                }
                throw $e;
            }
        }
        if ($lastException) {
            throw $lastException;
        }
        abort(500, 'Transfer failed');
    }

    private function performTransfer(User $sender, User $receiver, float $amount, float $commissionFee, float $totalAmount, ?string $idempotencyKey): Transaction
    {
        return DB::transaction(function () use ($sender, $receiver, $amount, $commissionFee, $totalAmount, $idempotencyKey) {
            $lockedSender = $this->userRepository->lockById($sender->id);
            $lockedReceiver = $this->userRepository->lockById($receiver->id);

            if (! $lockedSender->hasSufficientBalance($totalAmount)) {
                abort(422, 'Insufficient balance');
            }

            if ($idempotencyKey) {
                $existing = $this->idempotencyRepository->findByKey($idempotencyKey);
                if ($existing && $existing->transaction_id) {
                    $existingTx = $this->transactionRepository->findWithRelationsById($existing->transaction_id);
                    if ($existingTx) {
                        return $existingTx;
                    }
                }
            }

            $transaction = $this->transactionRepository->createPending(
                $lockedSender->id,
                $lockedReceiver->id,
                $amount,
                $commissionFee,
                $totalAmount
            );

            $this->userRepository->deductBalance($lockedSender, $totalAmount);
            $this->userRepository->addBalance($lockedReceiver, $amount);

            $this->transactionRepository->markCompleted($transaction);

            if ($idempotencyKey) {
                $this->idempotencyRepository->saveKey($idempotencyKey, $lockedSender->id, $transaction->id);
            }

            $this->outboxRepository->enqueue('transaction.completed', ['transaction_id' => $transaction->id]);

            DB::afterCommit(function () use ($transaction) {
                event(new TransactionCompleted($transaction));
            });

            return $transaction;
        });
    }
}

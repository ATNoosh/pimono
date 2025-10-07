<?php

namespace App\Repositories;

use App\Models\Transaction;

class TransactionRepository
{
    public function createPending(int $senderId, int $receiverId, float $amount, float $commissionFee, float $totalAmount): Transaction
    {
        return Transaction::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'amount' => $amount,
            'commission_fee' => $commissionFee,
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ]);
    }

    public function markCompleted(Transaction $transaction): void
    {
        $transaction->markAsCompleted();
    }

    public function findWithRelationsById(int $id): ?Transaction
    {
        return Transaction::with(['sender:id,name', 'receiver:id,name'])->find($id);
    }
}

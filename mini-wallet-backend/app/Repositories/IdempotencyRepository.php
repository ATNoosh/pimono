<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class IdempotencyRepository
{
    public function findByKey(string $key): ?object
    {
        return DB::table('idempotency_keys')->where('key', $key)->first();
    }

    public function saveKey(string $key, int $userId, int $transactionId): void
    {
        DB::table('idempotency_keys')->updateOrInsert(
            ['key' => $key],
            ['user_id' => $userId, 'transaction_id' => $transactionId, 'updated_at' => now(), 'created_at' => now()]
        );
    }
}



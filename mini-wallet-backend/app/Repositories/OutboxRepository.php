<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class OutboxRepository
{
    public function enqueue(string $type, array $payload): void
    {
        DB::table('outbox_messages')->insert([
            'type' => $type,
            'payload' => json_encode($payload),
            'available_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

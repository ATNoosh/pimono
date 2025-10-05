<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Events\TransactionCompleted;
use App\Models\Transaction;

class DispatchOutbox extends Command
{
    protected $signature = 'outbox:dispatch {--limit=100}';
    protected $description = 'Dispatch pending outbox messages';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $messages = DB::table('outbox_messages')
            ->whereNull('processed_at')
            ->where(function ($q) {
                $q->whereNull('available_at')->orWhere('available_at', '<=', now());
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($messages as $msg) {
            try {
                if ($msg->type === 'transaction.completed') {
                    $payload = json_decode($msg->payload, true);
                    $tx = Transaction::with(['sender:id,name', 'receiver:id,name'])->find($payload['transaction_id'] ?? null);
                    if ($tx) {
                        event(new TransactionCompleted($tx));
                    }
                }
                DB::table('outbox_messages')->where('id', $msg->id)->update([
                    'processed_at' => now(),
                    'attempts' => DB::raw('attempts + 1'),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                DB::table('outbox_messages')->where('id', $msg->id)->update([
                    'attempts' => DB::raw('attempts + 1'),
                    'updated_at' => now(),
                ]);
                $this->error($e->getMessage());
            }
        }

        $this->info('Processed '.count($messages).' outbox messages.');
        return self::SUCCESS;
    }
}



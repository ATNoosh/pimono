<?php

namespace Tests\Unit;

use App\Events\TransactionCompleted;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\IdempotencyRepository;
use App\Repositories\OutboxRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeService(): TransferService
    {
        return new TransferService(
            new UserRepository,
            new TransactionRepository,
            new IdempotencyRepository,
            new OutboxRepository,
        );
    }

    public function test_successful_transfer(): void
    {
        Event::fake([TransactionCompleted::class]);

        $sender = User::factory()->create(['balance' => 500.00]);
        $receiver = User::factory()->create(['balance' => 100.00]);

        $service = $this->makeService();
        $tx = $service->executeTransfer($sender, $receiver->id, 100.00, null);

        $this->assertInstanceOf(Transaction::class, $tx);
        $this->assertEquals('completed', $tx->status);
        $this->assertEquals(398.50, $sender->fresh()->balance); // 100 + 1.5% = 101.50 deducted
        $this->assertEquals(200.00, $receiver->fresh()->balance);

        Event::assertDispatched(TransactionCompleted::class);
    }

    public function test_idempotent_transfer_returns_same_transaction(): void
    {
        Event::fake([TransactionCompleted::class]);

        $sender = User::factory()->create(['balance' => 500.00]);
        $receiver = User::factory()->create(['balance' => 100.00]);

        $service = $this->makeService();
        $key = 'test-key-123';

        $tx1 = $service->executeTransfer($sender, $receiver->id, 50.00, $key);
        $senderBalanceAfterFirst = $sender->fresh()->balance;

        $tx2 = $service->executeTransfer($sender, $receiver->id, 50.00, $key);

        $this->assertEquals($tx1->id, $tx2->id);
        $this->assertEquals($senderBalanceAfterFirst, $sender->fresh()->balance);
        $this->assertEquals(150.00, $receiver->fresh()->balance);

        Event::assertDispatched(TransactionCompleted::class);
    }

    public function test_insufficient_balance_throws_422(): void
    {
        $sender = User::factory()->create(['balance' => 10.00]);
        $receiver = User::factory()->create(['balance' => 0.00]);

        $service = $this->makeService();

        $this->expectExceptionCode(422);
        $service->executeTransfer($sender, $receiver->id, 50.00, null);
    }

    public function test_retry_on_transient_error_succeeds(): void
    {
        $sender = User::factory()->create(['balance' => 500.00]);
        $receiver = User::factory()->create(['balance' => 100.00]);

        $service = $this->makeService();

        $thrown = false;
        DB::beforeExecuting(function () use (&$thrown) {
            if (! $thrown) {
                $thrown = true;
                throw new \RuntimeException('Deadlock found when trying to get lock');
            }
        });

        $tx = $service->executeTransfer($sender, $receiver->id, 10.00, null);
        $this->assertEquals('completed', $tx->status);
        $this->assertEquals(489.85, $sender->fresh()->balance);
        $this->assertEquals(110.00, $receiver->fresh()->balance);
    }
}

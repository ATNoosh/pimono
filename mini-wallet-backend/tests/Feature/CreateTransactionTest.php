<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_transaction_endpoint(): void
    {
        $sender = User::factory()->create(['balance' => 250.00]);
        $receiver = User::factory()->create(['balance' => 10.00]);

        $response = $this->actingAs($sender)->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 100.00,
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['message' => 'Transfer completed successfully']);

        $this->assertEquals(148.50, $sender->fresh()->balance);
        $this->assertEquals(110.00, $receiver->fresh()->balance);
    }
}



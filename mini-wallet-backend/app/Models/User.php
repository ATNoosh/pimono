<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
        ];
    }

    /**
     * Get the transactions where this user is the sender.
     */
    public function sentTransactions()
    {
        return $this->hasMany(Transaction::class, 'sender_id');
    }

    /**
     * Get the transactions where this user is the receiver.
     */
    public function receivedTransactions()
    {
        return $this->hasMany(Transaction::class, 'receiver_id');
    }

    /**
     * Get all transactions for this user (both sent and received).
     */
    public function transactions()
    {
        return $this->sentTransactions()->union($this->receivedTransactions());
    }

    /**
     * Check if user has sufficient balance for a transaction.
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Add money to user's balance (for deposits or receiving transfers).
     */
    public function addBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    /**
     * Deduct money from user's balance (for withdrawals or sending transfers).
     */
    public function deductBalance(float $amount): void
    {
        $this->decrement('balance', $amount);
    }
}

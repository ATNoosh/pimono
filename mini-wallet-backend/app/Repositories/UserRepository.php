<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function lockById(int $id): User
    {
        return User::where('id', $id)->lockForUpdate()->firstOrFail();
    }

    public function addBalance(User $user, float $amount): void
    {
        $user->addBalance($amount);
    }

    public function deductBalance(User $user, float $amount): void
    {
        $user->deductBalance($amount);
    }
}



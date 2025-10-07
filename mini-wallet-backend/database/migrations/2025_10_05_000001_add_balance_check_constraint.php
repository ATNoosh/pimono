<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add check constraint for non-negative balance where supported (e.g., MySQL 8.0.16+, PostgreSQL)
        try {
            DB::statement('ALTER TABLE users ADD CONSTRAINT chk_users_balance_non_negative CHECK (balance >= 0)');
        } catch (\Throwable $e) {
            // Some engines/versions may not support CHECK; ignore gracefully
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE users DROP CONSTRAINT chk_users_balance_non_negative');
        } catch (\Throwable $e) {
            try {
                DB::statement('ALTER TABLE users DROP CHECK chk_users_balance_non_negative');
            } catch (\Throwable $e2) {
                // ignore
            }
        }
    }
};

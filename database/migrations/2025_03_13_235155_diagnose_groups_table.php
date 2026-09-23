<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Simple migration for SQLite compatibility
        // Just skip the MySQL-specific operations
        \Log::info("Skipping groups table diagnosis for SQLite compatibility");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rien à faire en cas de rollback
    }
};
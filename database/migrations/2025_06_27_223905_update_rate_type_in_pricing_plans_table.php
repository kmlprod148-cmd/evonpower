<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration's logic is now handled by earlier migrations (2024_01_01_000005_create_pricing_plans_table.php)
        // which has been updated to the new schema.
        // Therefore, this migration should do nothing to avoid conflicts.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration's logic is now handled by earlier migrations (2024_01_01_000005_create_pricing_plans_table.php)
        // which has been updated to the new schema.
        // Therefore, this migration should do nothing to avoid conflicts.
    }
};

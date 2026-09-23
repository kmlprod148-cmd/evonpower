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
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasIndex('transactions', ['commission_plan_id'])) {
                $table->index('commission_plan_id');
            }
            if (!Schema::hasIndex('transactions', ['created_at'])) {
                $table->index('created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop the foreign key constraint before dropping the index
            $table->dropForeign(['commission_plan_id']);

            $table->dropIndex(['commission_plan_id']);
            $table->dropIndex(['created_at']);
        });
    }
};

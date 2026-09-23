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
        Schema::table('admin_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('admin_notifications', 'read_at') && Schema::hasColumn('admin_notifications', 'is_read')) {
                DB::statement('UPDATE admin_notifications SET is_read = TRUE WHERE read_at IS NOT NULL');
                $table->dropColumn('read_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            // Revert changes: add back read_at if it was dropped
            if (!Schema::hasColumn('admin_notifications', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }
            // Note: Reverting is_read status would require more complex logic if data was truly transferred.
            // For simplicity, we assume is_read remains as is on rollback.
        });
    }
};

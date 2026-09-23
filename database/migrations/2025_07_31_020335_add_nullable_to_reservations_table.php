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
        if (DB::getDriverName() === 'mysql') {
            Schema::table('reservations', function (Blueprint $table) {
                // Drop station_id if it exists and is a legacy column
                if (Schema::hasColumn('reservations', 'station_id')) {
                    $table->dropForeign(['station_id']); // Drop foreign key first
                    $table->dropColumn('station_id');
                }
                // Make estimated_duration nullable
                $table->integer('estimated_duration')->nullable()->change();
            });
        } else {
            // Pour SQLite, on ne peut pas facilement modifier les colonnes
            echo "SQLite detected - skipping reservations table modifications\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('reservations', function (Blueprint $table) {
                // Revert estimated_duration to non-nullable if needed (assuming it was originally non-nullable)
                $table->integer('estimated_duration')->nullable(false)->change();

                // Re-add station_id if it was dropped by this migration (optional, depending on original schema)
                // For this task, we assume it's a legacy column to be removed.
                // If it needs to be re-added, its original definition (nullable/non-nullable, foreign key) should be restored.
            });
        } else {
            // Pour SQLite, on ne peut pas facilement modifier les colonnes
            echo "SQLite detected - skipping reservations table modifications\n";
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('reservations', function (Blueprint $table) {
                // Change the enum values for the 'status' column
                DB::statement("ALTER TABLE reservations CHANGE status status ENUM('pending', 'pending_confirmation', 'confirmed', 'active', 'completed', 'canceled') DEFAULT 'pending'");
            });
        } else {
            // Pour SQLite, on ne peut pas facilement modifier les colonnes ENUM
            echo "SQLite detected - skipping status enum modification\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('reservations', function (Blueprint $table) {
                // Revert the enum values for the 'status' column
                DB::statement("ALTER TABLE reservations CHANGE status status ENUM('pending', 'pending_confirmation', 'confirmed', 'active', 'completed', 'cancelled') DEFAULT 'pending'");
            });
        } else {
            // Pour SQLite, on ne peut pas facilement modifier les colonnes ENUM
            echo "SQLite detected - skipping status enum modification\n";
        }
    }
};

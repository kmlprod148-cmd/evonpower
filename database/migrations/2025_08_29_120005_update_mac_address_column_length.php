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
            Schema::table('charging_points', function (Blueprint $table) {
                // Modify mac_address column to have proper length for MAC address format
                // MAC addresses are 17 characters long (6 pairs of hex digits + 5 separators)
                $table->string('mac_address', 17)->nullable()->change();
            });
        } else {
            // Pour SQLite, on ne peut pas facilement modifier les colonnes
            echo "SQLite detected - skipping mac_address column modification\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('charging_points', function (Blueprint $table) {
                // Revert to original length (255 characters)
                $table->string('mac_address', 255)->nullable()->change();
            });
        } else {
            // Pour SQLite, on ne peut pas facilement modifier les colonnes
            echo "SQLite detected - skipping mac_address column modification\n";
        }
    }
};

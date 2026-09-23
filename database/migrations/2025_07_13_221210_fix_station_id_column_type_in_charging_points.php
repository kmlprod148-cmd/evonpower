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
                // Change station_id from string to unsignedBigInteger
                $table->unsignedBigInteger('station_id')->nullable()->change();
            });
        } else {
            // Pour SQLite, on ne peut pas facilement modifier le type de colonne
            echo "SQLite detected - skipping station_id column type modification\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('charging_points', function (Blueprint $table) {
                // Revert station_id back to string
                $table->string('station_id')->nullable()->change();
            });
        } else {
            // Pour SQLite, on ne peut pas facilement modifier le type de colonne
            echo "SQLite detected - skipping station_id column type modification\n";
        }
    }
};

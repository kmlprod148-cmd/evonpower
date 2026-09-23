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
            // Corriger l'énumération status dans la table charging_points (valeurs finales)
            DB::statement("
                ALTER TABLE charging_points 
                MODIFY COLUMN status ENUM('available', 'charging', 'offline', 'maintenance', 'error', 'reserved', 'online') 
                NOT NULL DEFAULT 'available'
            ");
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
            // Revenir à l'énumération originale avec 'online' si besoin
            DB::statement("
                ALTER TABLE charging_points 
                MODIFY COLUMN status ENUM('available', 'charging', 'offline', 'maintenance', 'error', 'reserved', 'online') 
                NOT NULL DEFAULT 'available'
            ");
        } else {
            // Pour SQLite, on ne peut pas facilement modifier les colonnes ENUM
            echo "SQLite detected - skipping status enum modification\n";
        }
    }
};

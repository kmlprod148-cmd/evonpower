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
        // Cette migration corrige le problème de la migration précédente qui échoue
        // Elle ajoute les colonnes nécessaires sans toucher aux foreign keys
        
        Schema::table('reservations', function (Blueprint $table) {
            // Ajouter les colonnes manquantes si elles n'existent pas
            if (!Schema::hasColumn('reservations', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('reservations', 'amount')) {
                $table->decimal('amount', 10, 2)->nullable()->after('estimated_cost');
            }
            
            if (!Schema::hasColumn('reservations', 'energy_kwh')) {
                $table->decimal('energy_kwh', 8, 2)->nullable()->after('amount');
            }
            
            if (!Schema::hasColumn('reservations', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('energy_kwh');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'confirmed_at')) {
                $table->dropColumn('confirmed_at');
            }
            if (Schema::hasColumn('reservations', 'amount')) {
                $table->dropColumn('amount');
            }
            if (Schema::hasColumn('reservations', 'energy_kwh')) {
                $table->dropColumn('energy_kwh');
            }
            if (Schema::hasColumn('reservations', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
        });
    }
};

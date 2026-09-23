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
        Schema::table('reservations', function (Blueprint $table) {
            // Ajouter les colonnes manquantes si elles n'existent pas
            if (!Schema::hasColumn('reservations', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('status')->comment('Date et heure de confirmation de la réservation');
            }
            
            if (!Schema::hasColumn('reservations', 'amount')) {
                $table->decimal('amount', 10, 2)->nullable()->after('estimated_cost')->comment('Montant total de la réservation');
            }
            
            if (!Schema::hasColumn('reservations', 'energy_kwh')) {
                $table->decimal('energy_kwh', 8, 2)->nullable()->after('amount')->comment('Énergie en kWh');
            }
            
            if (!Schema::hasColumn('reservations', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('energy_kwh')->comment('Durée en minutes');
            }
            
            // Ne pas toucher à order_id pour éviter les problèmes de foreign key
            // La colonne order_id existe déjà et fonctionne
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['confirmed_at', 'amount', 'energy_kwh', 'duration_minutes']);
        });
    }
};

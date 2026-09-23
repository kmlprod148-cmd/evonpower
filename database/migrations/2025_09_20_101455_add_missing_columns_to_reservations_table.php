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
            if (!Schema::hasColumn('reservations', 'energy_kwh')) {
                $table->decimal('energy_kwh', 8, 2)->nullable()->comment('Énergie en kWh');
            }
            if (!Schema::hasColumn('reservations', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->comment('Durée en minutes');
            }
            if (!Schema::hasColumn('reservations', 'amount')) {
                $table->decimal('amount', 10, 2)->nullable()->comment('Montant total');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['energy_kwh', 'duration_minutes', 'amount']);
        });
    }
};

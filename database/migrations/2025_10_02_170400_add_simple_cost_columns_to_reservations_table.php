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
            // Ajouter seulement les colonnes de coût essentielles
            if (!Schema::hasColumn('reservations', 'estimated_cost')) {
                $table->decimal('estimated_cost', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('reservations', 'actual_cost')) {
                $table->decimal('actual_cost', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('reservations', 'total_cost')) {
                $table->decimal('total_cost', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('reservations', 'reservation_type')) {
                $table->string('reservation_type')->nullable();
            }
            if (!Schema::hasColumn('reservations', 'reservation_value')) {
                $table->decimal('reservation_value', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('reservations', 'pricing_plan_id')) {
                $table->unsignedBigInteger('pricing_plan_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'estimated_cost',
                'actual_cost', 
                'total_cost',
                'reservation_type',
                'reservation_value',
                'pricing_plan_id'
            ]);
        });
    }
};

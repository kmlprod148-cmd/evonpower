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
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'meter_start')) {
                $table->decimal('meter_start', 12, 4)->change();
            }
            if (Schema::hasColumn('transactions', 'meter_stop')) {
                $table->decimal('meter_stop', 12, 4)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'energy_delivered')) {
                $table->decimal('energy_delivered', 12, 4)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'price_energy')) {
                $table->decimal('price_energy', 12, 4)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'price_time')) {
                $table->decimal('price_time', 12, 4)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'price_service')) {
                $table->decimal('price_service', 12, 4)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'price_tax')) {
                $table->decimal('price_tax', 12, 4)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'price_total')) {
                $table->decimal('price_total', 12, 4)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'admin_commission')) {
                $table->decimal('admin_commission', 12, 4)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'integrator_commission')) {
                $table->decimal('integrator_commission', 12, 4)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'partner_commission')) {
                $table->decimal('partner_commission', 12, 4)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Revert to original precision if needed, or handle as per rollback strategy
            if (Schema::hasColumn('transactions', 'meter_start')) {
                $table->decimal('meter_start', 10, 2)->change();
            }
            if (Schema::hasColumn('transactions', 'meter_stop')) {
                $table->decimal('meter_stop', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'energy_delivered')) {
                $table->decimal('energy_delivered', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'price_energy')) {
                $table->decimal('price_energy', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'price_time')) {
                $table->decimal('price_time', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'price_service')) {
                $table->decimal('price_service', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'price_tax')) {
                $table->decimal('price_tax', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'price_total')) {
                $table->decimal('price_total', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'admin_commission')) {
                $table->decimal('admin_commission', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'integrator_commission')) {
                $table->decimal('integrator_commission', 10, 2)->nullable()->change();
            }
            if (Schema::hasColumn('transactions', 'partner_commission')) {
                $table->decimal('partner_commission', 10, 2)->nullable()->change();
            }
        });
    }
};

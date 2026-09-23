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
        Schema::table('pricing_plans', function (Blueprint $table) {
            // Vérifiez si la colonne existe avant de l'ajouter
            if (!Schema::hasColumn('pricing_plans', 'currency_code')) {
                $table->string('currency_code', 3)->default('EUR')->notNull();
            }
            if (!Schema::hasColumn('pricing_plans', 'billing_interval')) {
                // Pour SQLite, ajouter une valeur par défaut
                if (DB::getDriverName() === 'sqlite') {
                    $table->string('billing_interval', 20)->default('monthly')->notNull();
                } else {
                    $table->string('billing_interval', 20)->notNull();
                }
            }
            if (!Schema::hasColumn('pricing_plans', 'trial_period_days')) {
                $table->integer('trial_period_days')->nullable();
            }
            if (!Schema::hasColumn('pricing_plans', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->default(0)->notNull();
            }
            if (!Schema::hasColumn('pricing_plans', 'min_charging_time')) {
                $table->integer('min_charging_time')->default(0)->comment('Minutes');
            }
            if (!Schema::hasColumn('pricing_plans', 'max_charging_time')) {
                $table->integer('max_charging_time')->default(0)->comment('Minutes');
            }
            if (!Schema::hasColumn('pricing_plans', 'off_peak_start_time')) {
                $table->time('off_peak_start_time')->nullable();
            }
            if (!Schema::hasColumn('pricing_plans', 'off_peak_end_time')) {
                $table->time('off_peak_end_time')->nullable();
            }
            if (!Schema::hasColumn('pricing_plans', 'weekend_rate')) {
                $table->decimal('weekend_rate', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('pricing_plans', 'expiration_policy')) {
                $table->string('expiration_policy', 50)->nullable();
            }
            if (!Schema::hasColumn('pricing_plans', 'cancellation_policy')) {
                $table->string('cancellation_policy', 50)->nullable();
            }
            if (!Schema::hasColumn('pricing_plans', 'applicable_vehicle_types')) {
                if (DB::getDriverName() === 'sqlite') {
                    $table->text('applicable_vehicle_types')->nullable(); // SQLite ne supporte pas JSON
                } else {
                    $table->json('applicable_vehicle_types')->nullable();
                }
            }
            if (!Schema::hasColumn('pricing_plans', 'integrator_commission')) {
                $table->decimal('integrator_commission', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('pricing_plans', 'partner_commission')) {
                $table->decimal('partner_commission', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('pricing_plans', 'overstay_fee')) {
                $table->decimal('overstay_fee', 10, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropColumn([
                'currency_code',
                'billing_interval',
                'trial_period_days',
                'discount_percentage',
                'min_charging_time',
                'max_charging_time',
                'off_peak_start_time',
                'off_peak_end_time',
                'weekend_rate',
                'expiration_policy',
                'cancellation_policy',
                'applicable_vehicle_types',
                'integrator_commission',
                'partner_commission',
                'overstay_fee'
            ]);
        });
    }
};

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
            // Check if columns exist before adding them to avoid conflicts
            
            if (!Schema::hasColumn('pricing_plans', 'min_charge_duration')) {
                $table->integer('min_charge_duration')->nullable()->comment('Durée minimale de charge en minutes');
            }
            
            if (!Schema::hasColumn('pricing_plans', 'max_charge_duration')) {
                $table->integer('max_charge_duration')->nullable()->comment('Durée maximale de charge en minutes');
            }
            
            if (!Schema::hasColumn('pricing_plans', 'grace_period')) {
                $table->integer('grace_period')->default(0)->comment('Période de grâce en minutes');
            }
            
            if (!Schema::hasColumn('pricing_plans', 'idle_fee')) {
                $table->decimal('idle_fee', 8, 4)->default(0)->comment('Frais d\'inactivité');
            }
            
            if (!Schema::hasColumn('pricing_plans', 'reservation_fee')) {
                $table->decimal('reservation_fee', 8, 4)->default(0)->comment('Frais de réservation');
            }
            
            if (!Schema::hasColumn('pricing_plans', 'reservation_duration')) {
                $table->integer('reservation_duration')->default(30)->comment('Durée de réservation en minutes');
            }
            
            if (!Schema::hasColumn('pricing_plans', 'restriction_user_groups')) {
                $table->json('restriction_user_groups')->nullable()->comment('Groupes d\'utilisateurs autorisés');
            }
            
            if (!Schema::hasColumn('pricing_plans', 'custom_params')) {
                $table->json('custom_params')->nullable()->comment('Paramètres personnalisés');
            }
            
            if (!Schema::hasColumn('pricing_plans', 'notes')) {
                $table->text('notes')->nullable()->comment('Notes sur le plan tarifaire');
            }
            
            // Handle VAT rate foreign key carefully
            if (!Schema::hasColumn('pricing_plans', 'vat_rate_id')) {
                // Check if vat_rates table exists before creating foreign key
                if (Schema::hasTable('vat_rates')) {
                    $table->foreignId('vat_rate_id')->nullable()->constrained('vat_rates')->onDelete('set null');
                } else {
                    $table->unsignedBigInteger('vat_rate_id')->nullable()->comment('ID du taux de TVA');
                }
            }
            
            if (!Schema::hasColumn('pricing_plans', 'base_rate_per_minute')) {
                $table->decimal('base_rate_per_minute', 8, 4)->nullable()->comment('Tarif de base par minute');
            }
            
            if (!Schema::hasColumn('pricing_plans', 'base_rate_per_kwh')) {
                $table->decimal('base_rate_per_kwh', 8, 4)->nullable()->comment('Tarif de base par kWh');
            }
            
            // Add is_draft column if it doesn't exist
            if (!Schema::hasColumn('pricing_plans', 'is_draft')) {
                $table->boolean('is_draft')->default(false)->comment('Plan en mode brouillon');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            $columnsToRemove = [
                'min_charge_duration',
                'max_charge_duration', 
                'grace_period',
                'idle_fee',
                'reservation_fee',
                'reservation_duration',
                'restriction_user_groups',
                'custom_params',
                'notes',
                'vat_rate_id',
                'base_rate_per_minute',
                'base_rate_per_kwh',
                'is_draft'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('pricing_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

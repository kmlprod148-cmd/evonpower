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
            // Colonnes pour les types de transactions
            if (!Schema::hasColumn('transactions', 'transaction_type')) {
                $table->string('transaction_type')->default('client')->after('id');
            }
            
            if (!Schema::hasColumn('transactions', 'transaction_category')) {
                $table->string('transaction_category')->default('charging')->after('transaction_type');
            }

            // Colonnes pour les propriétaires de business profile
            if (!Schema::hasColumn('transactions', 'business_profile_owner_type')) {
                $table->string('business_profile_owner_type')->nullable()->after('business_profile_id');
            }
            
            if (!Schema::hasColumn('transactions', 'business_profile_owner_id')) {
                $table->unsignedBigInteger('business_profile_owner_id')->nullable()->after('business_profile_owner_type');
            }

            // Colonnes pour les frais d'activation
            if (!Schema::hasColumn('transactions', 'activation_fee')) {
                $table->decimal('activation_fee', 12, 4)->default(0)->after('price_tax');
            }
            
            if (!Schema::hasColumn('transactions', 'activation_fee_type')) {
                $table->string('activation_fee_type')->nullable()->after('activation_fee');
            }
            
            if (!Schema::hasColumn('transactions', 'activation_fee_amount')) {
                $table->decimal('activation_fee_amount', 12, 4)->default(0)->after('activation_fee_type');
            }
            
            if (!Schema::hasColumn('transactions', 'activation_fee_percentage')) {
                $table->decimal('activation_fee_percentage', 5, 4)->default(0)->after('activation_fee_amount');
            }
            
            if (!Schema::hasColumn('transactions', 'activation_fee_business_profile_id')) {
                $table->unsignedBigInteger('activation_fee_business_profile_id')->nullable()->after('activation_fee_percentage');
            }

            // Colonnes pour la répartition des revenus
            if (!Schema::hasColumn('transactions', 'repartition_breakdown')) {
                $table->json('repartition_breakdown')->nullable()->after('commission_notes');
            }
            
            if (!Schema::hasColumn('transactions', 'business_profile_fee_breakdown')) {
                $table->json('business_profile_fee_breakdown')->nullable()->after('repartition_breakdown');
            }

            // Colonnes pour les commissions
            if (!Schema::hasColumn('transactions', 'admin_commission')) {
                $table->decimal('admin_commission', 12, 4)->default(0)->after('business_profile_fee_breakdown');
            }
            
            if (!Schema::hasColumn('transactions', 'integrator_commission')) {
                $table->decimal('integrator_commission', 12, 4)->default(0)->after('admin_commission');
            }
            
            if (!Schema::hasColumn('transactions', 'partner_commission')) {
                $table->decimal('partner_commission', 12, 4)->default(0)->after('integrator_commission');
            }
            
            if (!Schema::hasColumn('transactions', 'admin_commission_paid')) {
                $table->boolean('admin_commission_paid')->default(false)->after('partner_commission');
            }
            
            if (!Schema::hasColumn('transactions', 'integrator_commission_paid')) {
                $table->boolean('integrator_commission_paid')->default(false)->after('admin_commission_paid');
            }
            
            if (!Schema::hasColumn('transactions', 'partner_commission_paid')) {
                $table->boolean('partner_commission_paid')->default(false)->after('integrator_commission_paid');
            }
            
            if (!Schema::hasColumn('transactions', 'admin_commission_paid_at')) {
                $table->timestamp('admin_commission_paid_at')->nullable()->after('admin_commission_paid');
            }
            
            if (!Schema::hasColumn('transactions', 'integrator_commission_paid_at')) {
                $table->timestamp('integrator_commission_paid_at')->nullable()->after('integrator_commission_paid');
            }
            
            if (!Schema::hasColumn('transactions', 'partner_commission_paid_at')) {
                $table->timestamp('partner_commission_paid_at')->nullable()->after('partner_commission_paid');
            }
            
            if (!Schema::hasColumn('transactions', 'commission_notes')) {
                $table->text('commission_notes')->nullable()->after('partner_commission_paid_at');
            }

            // Colonnes pour les relations
            if (!Schema::hasColumn('transactions', 'commission_plan_id')) {
                $table->unsignedBigInteger('commission_plan_id')->nullable()->after('pricing_plan_id');
            }

            // Index pour améliorer les performances
            if (!Schema::hasIndex('transactions', 'idx_transactions_type_category')) {
                $table->index(['transaction_type', 'transaction_category'], 'idx_transactions_type_category');
            }
            
            if (!Schema::hasIndex('transactions', 'idx_transactions_owner')) {
                $table->index(['business_profile_owner_type', 'business_profile_owner_id'], 'idx_transactions_owner');
            }
            
            if (!Schema::hasIndex('transactions', 'idx_transactions_activation_fee_bp')) {
                $table->index(['activation_fee_business_profile_id'], 'idx_transactions_activation_fee_bp');
            }
            
            if (!Schema::hasIndex('transactions', 'idx_transactions_commission_plan')) {
                $table->index(['commission_plan_id'], 'idx_transactions_commission_plan');
            }
        });

        // Les contraintes de clés étrangères existent déjà, pas besoin de les recréer
        // activation_fee_business_profile_id -> business_profiles.id
        // commission_plan_id -> commission_plans.id
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Les contraintes de clés étrangères existent déjà, pas besoin de les supprimer
            // activation_fee_business_profile_id -> business_profiles.id
            // commission_plan_id -> commission_plans.id

            // Supprimer les index
            if (Schema::hasIndex('transactions', 'idx_transactions_type_category')) {
                $table->dropIndex('idx_transactions_type_category');
            }
            
            if (Schema::hasIndex('transactions', 'idx_transactions_owner')) {
                $table->dropIndex('idx_transactions_owner');
            }
            
            if (Schema::hasIndex('transactions', 'idx_transactions_activation_fee_bp')) {
                $table->dropIndex('idx_transactions_activation_fee_bp');
            }
            
            if (Schema::hasIndex('transactions', 'idx_transactions_commission_plan')) {
                $table->dropIndex('idx_transactions_commission_plan');
            }

            // Supprimer les colonnes
            $columnsToDrop = [
                'transaction_type',
                'transaction_category',
                'business_profile_owner_type',
                'business_profile_owner_id',
                'activation_fee',
                'activation_fee_type',
                'activation_fee_amount',
                'activation_fee_percentage',
                'activation_fee_business_profile_id',
                'repartition_breakdown',
                'business_profile_fee_breakdown',
                'admin_commission',
                'integrator_commission',
                'partner_commission',
                'admin_commission_paid',
                'integrator_commission_paid',
                'partner_commission_paid',
                'admin_commission_paid_at',
                'integrator_commission_paid_at',
                'partner_commission_paid_at',
                'commission_notes',
                'commission_plan_id'
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

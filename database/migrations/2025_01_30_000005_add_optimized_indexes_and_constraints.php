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
        // Ajouter des index composites pour optimiser les requêtes fréquentes
        Schema::table('enhanced_transactions', function (Blueprint $table) {
            // Index composites pour les requêtes de reporting
            $table->index(['status', 'transaction_type', 'created_at'], 'idx_status_type_date');
            $table->index(['source_user_id', 'status', 'created_at'], 'idx_source_status_date');
            $table->index(['target_user_id', 'status', 'created_at'], 'idx_target_status_date');
            $table->index(['business_profile_id', 'status', 'created_at'], 'idx_profile_status_date');
            
            // Index pour les requêtes de montants
            $table->index(['amount', 'status'], 'idx_amount_status');
            $table->index(['total_amount', 'status'], 'idx_total_amount_status');
            
            // Index pour les recherches par période
            $table->index(['created_at', 'status'], 'idx_created_status');
            $table->index(['processed_at', 'status'], 'idx_processed_status');
        });

        // Ajouter des index pour les business profiles
        Schema::table('enhanced_business_profiles', function (Blueprint $table) {
            // Index pour les requêtes de frais
            $table->index(['owner_type', 'is_active'], 'idx_owner_type_active');
            $table->index(['is_default', 'is_active'], 'idx_default_active');
            
            // Index pour les limites de transaction
            $table->index(['min_transaction_amount', 'max_transaction_amount'], 'idx_amount_limits');
        });

        // Ajouter des index pour les utilisateurs
        Schema::table('enhanced_users', function (Blueprint $table) {
            // Index pour les requêtes par rôle et statut
            $table->index(['role', 'is_active', 'business_profile_id'], 'idx_role_active_profile');
            
            // Index pour les requêtes de solde
            $table->index(['balance', 'is_active'], 'idx_balance_active');
        });

        // Ajouter des index pour les logs de frais
        Schema::table('transaction_fee_logs', function (Blueprint $table) {
            // Index composites pour les analyses de frais
            $table->index(['fee_type', 'fee_category', 'created_at'], 'idx_fee_type_category_date');
            $table->index(['business_profile_id', 'fee_type', 'created_at'], 'idx_profile_fee_type_date');
            $table->index(['transaction_id', 'fee_category'], 'idx_transaction_fee_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enhanced_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_status_type_date');
            $table->dropIndex('idx_source_status_date');
            $table->dropIndex('idx_target_status_date');
            $table->dropIndex('idx_profile_status_date');
            $table->dropIndex('idx_amount_status');
            $table->dropIndex('idx_total_amount_status');
            $table->dropIndex('idx_created_status');
            $table->dropIndex('idx_processed_status');
        });

        Schema::table('enhanced_business_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_owner_type_active');
            $table->dropIndex('idx_default_active');
            $table->dropIndex('idx_amount_limits');
        });

        Schema::table('enhanced_users', function (Blueprint $table) {
            $table->dropIndex('idx_role_active_active_profile');
            $table->dropIndex('idx_balance_active');
        });

        Schema::table('transaction_fee_logs', function (Blueprint $table) {
            $table->dropIndex('idx_fee_type_category_date');
            $table->dropIndex('idx_profile_fee_type_date');
            $table->dropIndex('idx_transaction_fee_category');
        });
    }
};

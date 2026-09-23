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
        Schema::table('business_profiles', function (Blueprint $table) {
            // Ajouter les champs sans spécifier "after"
            if (!Schema::hasColumn('business_profiles', 'created_by_type')) {
                $table->string('created_by_type')->nullable()->after('id');
            }
            if (!Schema::hasColumn('business_profiles', 'created_by_id')) {
                $table->unsignedBigInteger('created_by_id')->nullable();
            }
            
            if (!Schema::hasColumn('business_profiles', 'maintenance_fee')) {
                $table->decimal('maintenance_fee', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('business_profiles', 'maintenance_period')) {
                $table->enum('maintenance_period', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            }
            if (!Schema::hasColumn('business_profiles', 'transaction_fee')) {
                $table->decimal('transaction_fee', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('business_profiles', 'transaction_fee_type')) {
                $table->enum('transaction_fee_type', ['fixed', 'percentage'])->default('fixed');
            }
            if (!Schema::hasColumn('business_profiles', 'recharge_fee')) {
                $table->decimal('recharge_fee', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('business_profiles', 'active_terminal_fee')) {
                $table->decimal('active_terminal_fee', 10, 2)->default(0);
            }
            
            if (!Schema::hasColumn('business_profiles', 'is_public')) {
                $table->boolean('is_public')->default(false);
            }
            
            // Ajouter l'index polymorphique
            if (!Schema::hasIndex('business_profiles', ['created_by_type', 'created_by_id'])) {
                $table->index(['created_by_type', 'created_by_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // Supprimer uniquement les champs ajoutés
            $columnsToDrop = [
                'created_by_type',
                'created_by_id',
                'maintenance_fee',
                'maintenance_period',
                'transaction_fee',
                'transaction_fee_type',
                'recharge_fee',
                'active_terminal_fee',
                'is_public'
            ];
            
            $existingColumns = [];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('business_profiles', $column)) {
                    $existingColumns[] = $column;
                }
            }
            
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
            
            // Supprimer l'index si existant
            // Supprimer l'index si existant
            $indexName = 'business_profiles_created_by_type_created_by_id_index';
            if (Schema::hasIndex('business_profiles', $indexName)) {
                $table->dropIndex($indexName);
            }
        });
    }
};
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
        Schema::create('enhanced_business_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->constrained('enhanced_users')->cascadeOnDelete();
            $table->enum('owner_type', ['admin', 'integrator', 'operator']);
            
            // Configuration des frais fixes (en euros)
            $table->decimal('admin_fixed_fee', 10, 2)->default(0.00);
            $table->decimal('integrator_fixed_fee', 10, 2)->default(0.00);
            $table->decimal('operator_fixed_fee', 10, 2)->default(0.00);
            
            // Configuration des frais en pourcentage
            $table->decimal('admin_percentage_fee', 5, 2)->default(0.00); // Max 999.99%
            $table->decimal('integrator_percentage_fee', 5, 2)->default(0.00);
            $table->decimal('operator_percentage_fee', 5, 2)->default(0.00);
            
            // Configuration des frais combinés
            $table->boolean('use_fixed_fees')->default(true);
            $table->boolean('use_percentage_fees')->default(false);
            $table->boolean('combine_fees')->default(false); // Si true, applique les deux types
            
            // Limites de transaction
            $table->decimal('min_transaction_amount', 10, 2)->default(0.01);
            $table->decimal('max_transaction_amount', 15, 2)->default(999999.99);
            $table->decimal('daily_limit', 15, 2)->nullable();
            $table->decimal('monthly_limit', 15, 2)->nullable();
            
            // Configuration des types de transaction supportés
            $table->boolean('supports_admin_to_integrator')->default(true);
            $table->boolean('supports_integrator_to_operator')->default(true);
            $table->boolean('supports_recharge')->default(true);
            
            // Métadonnées
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable(); // Pour stocker des configurations supplémentaires
            $table->timestamps();
            
            // Indexes
            $table->index(['owner_id', 'owner_type']);
            $table->index(['is_active', 'is_default']);
            $table->index('owner_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enhanced_business_profiles');
    }
};

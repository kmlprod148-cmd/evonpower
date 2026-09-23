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
        Schema::create('transaction_fee_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('enhanced_transactions')->cascadeOnDelete();
            $table->foreignId('business_profile_id')->constrained('enhanced_business_profiles')->cascadeOnDelete();
            
            // Type de frais appliqué
            $table->enum('fee_type', ['fixed', 'percentage', 'combined']);
            $table->string('fee_category'); // 'admin', 'integrator', 'operator'
            
            // Détails du calcul
            $table->decimal('base_amount', 15, 2); // Montant sur lequel le frais est calculé
            $table->decimal('fee_rate', 5, 2)->nullable(); // Taux en pourcentage (si applicable)
            $table->decimal('fee_amount', 10, 2); // Montant du frais calculé
            $table->string('currency', 3)->default('EUR');
            
            // Configuration utilisée
            $table->json('fee_config')->nullable(); // Configuration du business profile utilisée
            
            // Métadonnées
            $table->text('calculation_notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['transaction_id', 'fee_type']);
            $table->index(['business_profile_id', 'fee_category']);
            $table->index('fee_type');
            $table->index('fee_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_fee_logs');
    }
};

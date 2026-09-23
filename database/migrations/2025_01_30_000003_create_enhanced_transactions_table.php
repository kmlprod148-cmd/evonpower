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
        Schema::create('enhanced_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_reference')->unique(); // Référence unique de transaction
            
            // Utilisateurs source et cible
            $table->foreignId('source_user_id')->constrained('enhanced_users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained('enhanced_users')->cascadeOnDelete();
            
            // Profil d'affaires utilisé pour cette transaction
            $table->foreignId('business_profile_id')->constrained('enhanced_business_profiles')->cascadeOnDelete();
            
            // Montants et frais
            $table->decimal('amount', 15, 2); // Montant principal
            $table->decimal('fixed_fee_amount', 10, 2)->default(0.00); // Frais fixes appliqués
            $table->decimal('percentage_fee_amount', 10, 2)->default(0.00); // Frais en pourcentage appliqués
            $table->decimal('total_amount', 15, 2); // Montant total (amount + frais)
            $table->string('currency', 3)->default('EUR');
            
            // Détails des frais
            $table->json('fee_breakdown')->nullable(); // Détail des frais appliqués
            $table->decimal('admin_fee', 10, 2)->default(0.00);
            $table->decimal('integrator_fee', 10, 2)->default(0.00);
            $table->decimal('operator_fee', 10, 2)->default(0.00);
            
            // Type et statut de transaction
            $table->enum('transaction_type', ['admin_to_integrator', 'integrator_to_operator', 'recharge', 'refund', 'commission']);
            $table->enum('status', ['pending', 'completed', 'canceled', 'failed'])->default('pending');
            
            // Métadonnées de transaction
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Données supplémentaires
            $table->string('external_reference')->nullable(); // Référence externe (paiement, etc.)
            
            // Timestamps
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['source_user_id', 'status']);
            $table->index(['target_user_id', 'status']);
            $table->index(['business_profile_id', 'status']);
            $table->index(['transaction_type', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('transaction_reference');
            $table->index('external_reference');
            $table->index('processed_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enhanced_transactions');
    }
};

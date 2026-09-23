<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table des abonnements actifs des utilisateurs avec suivi des quotas
     */
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            
            // Période d'abonnement
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', [
                'active',       // Abonnement actif
                'expired',      // Expiré
                'cancelled',    // Annulé
                'suspended',    // Suspendu
                'pending',      // En attente de paiement
            ])->default('pending');
            
            // Suivi des quotas consommés
            $table->integer('sessions_used')->default(0);
            $table->decimal('kwh_used', 10, 2)->default(0);
            $table->integer('duration_minutes_used')->default(0);
            
            // Montants
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            
            // Paiement
            $table->string('payment_method')->nullable(); // cmi, card, bank_transfer, cash
            $table->string('payment_reference')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            
            // Renouvellement
            $table->boolean('auto_renew')->default(false);
            $table->date('renewal_date')->nullable();
            $table->foreignId('renewed_from_id')->nullable()->constrained('user_subscriptions')->nullOnDelete();
            
            // Métadonnées
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status'], 'user_sub_status_idx');
            $table->index(['status', 'end_date'], 'sub_expiry_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};

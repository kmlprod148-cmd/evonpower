<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table des remboursements vers wallet ou carte bancaire (CMI/Stripe)
     */
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            
            // Transaction ou wallet source du remboursement
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            
            // Wallet destinataire (si remboursement vers wallet)
            $table->foreignId('refund_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            
            // Montant
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            
            // Type de remboursement
            $table->enum('refund_type', [
                'wallet',           // Vers le wallet (crédit)
                'bank_card',        // Vers carte bancaire (CMI/Stripe)
            ]);
            
            // Statut
            $table->enum('status', [
                'pending',      // En attente
                'processing',  // En cours de traitement
                'completed',   // Terminé
                'failed',      // Échoué
                'reversed',    // Inversé (remboursement annulé)
            ])->default('pending');
            
            // Mode de paiement cible (pour bank_card)
            $table->enum('payment_gateway', [
                'cmi',          // CMI
                'stripe',       // Stripe
            ])->nullable();
            
            // Références de paiement
            $table->string('original_payment_reference')->nullable(); // Référence du paiement original
            $table->string('refund_reference')->nullable(); // Référence du remboursement
            $table->string('external_refund_id')->nullable(); // ID externe (Stripe refund ID, etc.)
            $table->text('gateway_response')->nullable(); // Réponse complète du gateway
            
            // Raison du remboursement
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            
            // Traçabilité
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            
            // Métadonnées
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['status', 'created_at'], 'refund_status_idx');
            $table->index(['refund_type', 'status'], 'refund_type_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};

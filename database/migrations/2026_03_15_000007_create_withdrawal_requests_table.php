<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table des demandes de retrait pour le wallet multi-rôles
     */
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            
            // Propriétaire de la demande (polymorphic)
            $table->morphs('owner'); // owner_type, owner_id (Integrator, Partner, etc.)
            
            // Wallet source
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            
            // Montant
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->decimal('fee', 10, 2)->default(0); // Frais de retrait
            $table->decimal('net_amount', 10, 2); // Montant net après frais
            
            // Statut
            $table->enum('status', [
                'pending',      // En attente de validation
                'approved',    // Approuvé
                'rejected',     // Rejeté
                'processing',   // En cours de traitement
                'completed',    // Terminé (argent envoyé)
                'failed',       // Échoué
                'cancelled',    // Annulé par l'utilisateur
            ])->default('pending');
            
            // Mode de retrait
            $table->enum('withdrawal_method', [
                'bank_transfer',   // Virement bancaire
                'card',            // Carte bancaire
                'wallet',          // Vers un autre wallet
            ]);
            
            // Coordonnées bancaires / paiement
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable(); // IBAN crypté
            $table->string('bank_code')->nullable(); // Code banque
            $table->string('card_last4')->nullable(); // 4 derniers chiffres carte
            $table->string('card_brand')->nullable(); // Visa, Mastercard
            $table->string('destination_wallet_id')->nullable();
            
            // Références externes (Stripe, CMI, etc.)
            $table->string('external_reference')->nullable();
            $table->string('external_id')->nullable();
            $table->text('external_response')->nullable();
            
            // Validation
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Traitement
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_notes')->nullable();
            
            // Historique des statuts
            $table->json('status_history')->nullable();
            
            // Métadonnées
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index(['owner_type', 'owner_id', 'status'], 'withdrawal_owner_status_idx');
            $table->index(['status', 'created_at'], 'withdrawal_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};

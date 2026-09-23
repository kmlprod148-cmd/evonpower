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
        if (Schema::hasTable('credit_requests')) {
            return; // La table existe déjà, on skip
        }
        
        Schema::create('credit_requests', function (Blueprint $table) {
            $table->id();
            
            // Client qui demande le crédit
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            
            // Propriétaire du point de charge (admin, intégrateur, opérateur, partenaire)
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            
            // Point de charge concerné (optionnel) - Contrainte FK sera ajoutée plus tard
            $table->unsignedBigInteger('charging_point_id')->nullable();
            
            // Réservation qui a déclenché la demande (optionnel) - Contrainte FK sera ajoutée plus tard
            $table->unsignedBigInteger('reservation_id')->nullable();
            
            // Montant demandé
            $table->decimal('amount', 10, 2);
            
            // Statut : pending, approved, rejected, cancelled
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            
            // Type de demande : reservation_based, manual
            $table->enum('request_type', ['reservation_based', 'manual'])->default('manual');
            
            // Raison de la demande
            $table->text('reason')->nullable();
            
            // Réponse du propriétaire
            $table->text('owner_response')->nullable();
            
            // Date de traitement
            $table->timestamp('processed_at')->nullable();
            
            // ID de la transaction de paiement (si crédit card)
            $table->string('payment_transaction_id')->nullable();
            
            // Méthode de paiement : credit_request, credit_card, bank_transfer
            $table->enum('payment_method', ['credit_request', 'credit_card', 'bank_transfer'])->default('credit_request');
            
            // Métadonnées JSON
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour performance
            $table->index(['client_id', 'status']);
            $table->index(['owner_id', 'status']);
            $table->index(['charging_point_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_requests');
    }
};


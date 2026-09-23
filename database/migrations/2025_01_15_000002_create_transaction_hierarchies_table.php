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
        if (!Schema::hasTable('transaction_hierarchies')) {
            Schema::create('transaction_hierarchies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_transaction_id')->comment('Transaction originale');
            
            // Type de transaction hiérarchique
            $table->enum('transaction_type', ['admin_integrator', 'integrator_operator'])->comment('Type de transaction dans la hiérarchie');
            
            // Participants
            $table->unsignedBigInteger('payer_id')->comment('Utilisateur qui paie');
            $table->unsignedBigInteger('payee_id')->comment('Utilisateur qui reçoit');
            
            // Montants
            $table->decimal('amount', 10, 2)->comment('Montant de la transaction');
            $table->decimal('fees_amount', 10, 2)->default(0)->comment('Montant des frais');
            $table->decimal('net_amount', 10, 2)->comment('Montant net après frais');
            
            // Statut
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            
            // Métadonnées
            $table->text('description')->nullable();
            $table->json('metadata')->nullable()->comment('Données supplémentaires');
            
            $table->timestamps();
            
            // Index avec noms courts pour éviter l'erreur MySQL
            $table->index(['original_transaction_id', 'transaction_type'], 'tx_hier_orig_type_idx');
            $table->index(['payer_id', 'payee_id'], 'tx_hier_payer_payee_idx');
            $table->index('status', 'tx_hier_status_idx');
            $table->index('created_at', 'tx_hier_created_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_hierarchies');
    }
};

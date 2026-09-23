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
        if (!Schema::hasTable('balance_movements')) {
            Schema::create('balance_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payer_id')->constrained('users')->onDelete('cascade')->comment('Utilisateur qui paie');
            $table->foreignId('payee_id')->constrained('users')->onDelete('cascade')->comment('Utilisateur qui reçoit');
            $table->foreignId('transaction_hierarchy_id')->nullable()->constrained('transaction_hierarchies')->onDelete('set null')->comment('Transaction hiérarchique associée');
            
            // Montants et soldes
            $table->decimal('amount', 10, 2)->comment('Montant transféré');
            $table->decimal('payer_balance_before', 15, 2)->comment('Solde du payeur avant la transaction');
            $table->decimal('payer_balance_after', 15, 2)->comment('Solde du payeur après la transaction');
            $table->decimal('payee_balance_before', 15, 2)->comment('Solde du bénéficiaire avant la transaction');
            $table->decimal('payee_balance_after', 15, 2)->comment('Solde du bénéficiaire après la transaction');
            
            // Type de mouvement
            $table->enum('movement_type', ['admin_integrator', 'integrator_operator', 'manual', 'adjustment'])->comment('Type de mouvement');
            
            // Métadonnées
            $table->text('description')->nullable()->comment('Description du mouvement');
            $table->json('metadata')->nullable()->comment('Données supplémentaires');
            
            $table->timestamps();
            
            // Index avec noms courts pour éviter l'erreur MySQL
            $table->index(['payer_id', 'created_at'], 'balance_payer_date_idx');
            $table->index(['payee_id', 'created_at'], 'balance_payee_date_idx');
            $table->index('movement_type', 'balance_type_idx');
            $table->index('transaction_hierarchy_id', 'balance_tx_hier_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_movements');
    }
};

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
        if (!Schema::hasTable('transaction_details')) {
            Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            
            // Détails des frais séparés
            $table->decimal('transaction_fee_percentage', 5, 2)->default(1.50)->comment('Frais de transaction en pourcentage (1.5%)');
            $table->decimal('transaction_fee_fixed', 8, 2)->default(1.00)->comment('Frais de transaction fixes (1 DH)');
            $table->decimal('transaction_fee_total', 10, 2)->comment('Total des frais de transaction calculés');
            
            // Parts détaillées
            $table->decimal('admin_share_amount', 10, 2)->default(0)->comment('Montant de la part Admin');
            $table->decimal('integrator_share_amount', 10, 2)->default(0)->comment('Montant de la part Intégrateur');
            $table->decimal('operator_share_amount', 10, 2)->default(0)->comment('Montant de la part Opérateur');
            
            // Pourcentages des parts
            $table->decimal('admin_share_percentage', 5, 2)->default(10.00)->comment('Pourcentage de la part Admin');
            $table->decimal('integrator_share_percentage', 5, 2)->default(5.00)->comment('Pourcentage de la part Intégrateur');
            
            // Hiérarchie des créateurs
            $table->unsignedBigInteger('admin_creator_id')->nullable()->comment('Admin qui a créé l\'intégrateur');
            $table->unsignedBigInteger('integrator_creator_id')->nullable()->comment('Intégrateur qui a créé l\'opérateur');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('Opérateur concerné par la transaction');
            
            // Statut des paiements
            $table->boolean('admin_paid')->default(false);
            $table->boolean('integrator_paid')->default(false);
            $table->boolean('operator_paid')->default(false);
            $table->timestamp('admin_paid_at')->nullable();
            $table->timestamp('integrator_paid_at')->nullable();
            $table->timestamp('operator_paid_at')->nullable();
            
            // Métadonnées
            $table->text('notes')->nullable();
            $table->json('calculation_details')->nullable()->comment('Détails du calcul pour audit');
            
            $table->timestamps();
            
            // Index avec noms courts pour éviter l'erreur MySQL
            $table->index(['transaction_id', 'admin_creator_id'], 'tx_detail_admin_idx');
            $table->index(['transaction_id', 'integrator_creator_id'], 'tx_detail_integrator_idx');
            $table->index(['transaction_id', 'operator_id'], 'tx_detail_operator_idx');
            $table->index('created_at', 'tx_detail_created_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};

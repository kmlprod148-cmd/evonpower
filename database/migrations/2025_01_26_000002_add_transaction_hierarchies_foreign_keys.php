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
        // Ajouter les contraintes de clé étrangère pour transaction_hierarchies
        // seulement si les tables existent
        if (Schema::hasTable('transactions') && Schema::hasTable('transaction_hierarchies')) {
            Schema::table('transaction_hierarchies', function (Blueprint $table) {
                // Ajouter la contrainte pour original_transaction_id si elle n'existe pas
                if (!Schema::hasColumn('transaction_hierarchies', 'original_transaction_id')) {
                    $table->foreign('original_transaction_id')->references('id')->on('transactions')->onDelete('cascade');
                }
            });
        }
        
        if (Schema::hasTable('users') && Schema::hasTable('transaction_hierarchies')) {
            Schema::table('transaction_hierarchies', function (Blueprint $table) {
                // Ajouter les contraintes pour payer_id et payee_id si elles n'existent pas
                if (!Schema::hasColumn('transaction_hierarchies', 'payer_id')) {
                    $table->foreign('payer_id')->references('id')->on('users')->onDelete('cascade');
                }
                if (!Schema::hasColumn('transaction_hierarchies', 'payee_id')) {
                    $table->foreign('payee_id')->references('id')->on('users')->onDelete('cascade');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les contraintes de clé étrangère
        if (Schema::hasTable('transaction_hierarchies')) {
            Schema::table('transaction_hierarchies', function (Blueprint $table) {
                $table->dropForeign(['original_transaction_id']);
                $table->dropForeign(['payer_id']);
                $table->dropForeign(['payee_id']);
            });
        }
    }
};

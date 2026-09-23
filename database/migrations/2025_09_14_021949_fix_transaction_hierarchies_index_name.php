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
        // Supprimer l'index problématique s'il existe
        try {
            Schema::table('transaction_hierarchies', function (Blueprint $table) {
                $table->dropIndex('transaction_hierarchies_original_transaction_id_transaction_type_index');
            });
        } catch (Exception $e) {
            // L'index n'existe peut-être pas, continuer
        }

        // Créer l'index avec un nom court seulement s'il n'existe pas déjà
        if (!Schema::hasIndex('transaction_hierarchies', 'tx_hier_orig_type_idx')) {
            Schema::table('transaction_hierarchies', function (Blueprint $table) {
                $table->index(['original_transaction_id', 'transaction_type'], 'tx_hier_orig_type_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer l'index court
        Schema::table('transaction_hierarchies', function (Blueprint $table) {
            $table->dropIndex('tx_hier_orig_type_idx');
        });

        // Recréer l'index avec le nom original (si nécessaire)
        try {
            Schema::table('transaction_hierarchies', function (Blueprint $table) {
                $table->index(['original_transaction_id', 'transaction_type'], 'transaction_hierarchies_original_transaction_id_transaction_type_index');
            });
        } catch (Exception $e) {
            // Ignorer l'erreur si le nom est trop long
        }
    }
};

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
        // Ajouter les contraintes de clé étrangère pour transaction_details
        // seulement si les tables existent
        if (Schema::hasTable('transactions') && Schema::hasTable('transaction_details')) {
            Schema::table('transaction_details', function (Blueprint $table) {
                // Ajouter la contrainte pour transaction_id si elle n'existe pas
                if (!Schema::hasColumn('transaction_details', 'transaction_id')) {
                    $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
                }
            });
        }
        
        if (Schema::hasTable('users') && Schema::hasTable('transaction_details')) {
            Schema::table('transaction_details', function (Blueprint $table) {
                // Ajouter les contraintes pour les clés étrangères vers users si elles n'existent pas
                if (!Schema::hasColumn('transaction_details', 'admin_creator_id')) {
                    $table->foreign('admin_creator_id')->references('id')->on('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('transaction_details', 'integrator_creator_id')) {
                    $table->foreign('integrator_creator_id')->references('id')->on('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('transaction_details', 'operator_id')) {
                    $table->foreign('operator_id')->references('id')->on('users')->onDelete('set null');
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
        if (Schema::hasTable('transaction_details')) {
            Schema::table('transaction_details', function (Blueprint $table) {
                $table->dropForeign(['transaction_id']);
                $table->dropForeign(['admin_creator_id']);
                $table->dropForeign(['integrator_creator_id']);
                $table->dropForeign(['operator_id']);
            });
        }
    }
};

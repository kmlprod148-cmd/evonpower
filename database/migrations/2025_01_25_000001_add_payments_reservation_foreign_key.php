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
        // Ajouter la contrainte de clé étrangère vers reservations
        // Cette migration s'exécute après la création de la table reservations
        if (Schema::hasTable('payments') && Schema::hasTable('reservations')) {
            try {
                Schema::table('payments', function (Blueprint $table) {
                    $table->foreign('reservation_id')
                        ->references('id')
                        ->on('reservations')
                        ->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // La contrainte existe déjà ou autre erreur, on ignore
                \Log::info('FK payments->reservations déjà existante ou erreur: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            try {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropForeign(['reservation_id']);
                });
            } catch (\Exception $e) {
                // La contrainte n'existe pas, on ignore
                \Log::info('FK payments->reservations inexistante: ' . $e->getMessage());
            }
        }
    }
};


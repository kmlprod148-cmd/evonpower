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
        // Ajouter la contrainte FK credit_recharges->credit_packs
        // Cette migration s'exécute après la création de credit_packs
        if (Schema::hasTable('credit_recharges') && Schema::hasTable('credit_packs')) {
            try {
                Schema::table('credit_recharges', function (Blueprint $table) {
                    $table->foreign('credit_pack_id')
                        ->references('id')
                        ->on('credit_packs')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                \Log::info('FK credit_recharges->credit_packs déjà existante: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('credit_recharges')) {
            try {
                Schema::table('credit_recharges', function (Blueprint $table) {
                    $table->dropForeign(['credit_pack_id']);
                });
            } catch (\Exception $e) {
                \Log::info('FK credit_recharges->credit_packs inexistante: ' . $e->getMessage());
            }
        }
    }
};


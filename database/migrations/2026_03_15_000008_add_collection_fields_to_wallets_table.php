<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajout des champs de collecte pour le wallet multi-rôles (admin, intégrateur, partenaire)
     */
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Mode de collecte
            $table->enum('collection_mode', [
                'manual',      // Collecte manuelle
                'auto',        // Collecte automatique
                'none',        // Pas de collecte
            ])->default('manual')->after('auto_recharge_amount');
            
            // Solde en attente de collecte (to be collected)
            $table->decimal('balance_to_collect', 12, 2)->default(0)->after('collection_mode');
            $table->decimal('last_collected_amount', 12, 2)->default(0)->after('balance_to_collect');
            $table->timestamp('last_collected_at')->nullable()->after('last_collected_amount');
            
            // Paramètres de seuil de collecte automatique
            $table->decimal('collection_threshold', 12, 2)->nullable()->after('last_collected_at');
            $table->decimal('collection_target', 12, 2)->nullable()->after('collection_threshold');
            
            // Métadonnées de collecte
            $table->json('collection_metadata')->nullable()->after('collection_target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn([
                'collection_mode',
                'balance_to_collect',
                'last_collected_amount',
                'last_collected_at',
                'collection_threshold',
                'collection_target',
                'collection_metadata',
            ]);
        });
    }
};

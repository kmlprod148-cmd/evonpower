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
        Schema::table('charging_points', function (Blueprint $table) {
            // Ajouter steve_charge_box_pk si n'existe pas
            if (!Schema::hasColumn('charging_points', 'steve_charge_box_pk')) {
                $table->unsignedBigInteger('steve_charge_box_pk')
                    ->nullable()
                    ->after('steve_charging_point_id')
                    ->comment('Clé primaire de la borne sur Steve API');
            }

            // Statut de synchronisation
            if (!Schema::hasColumn('charging_points', 'steve_sync_status')) {
                $table->enum('steve_sync_status', ['not_synced', 'synced', 'failed', 'out_of_sync'])
                    ->default('not_synced')
                    ->after('steve_charge_box_pk')
                    ->comment('Statut de synchronisation avec Steve API');
            }

            // Date de dernière synchronisation
            if (!Schema::hasColumn('charging_points', 'steve_synced_at')) {
                $table->timestamp('steve_synced_at')
                    ->nullable()
                    ->after('steve_sync_status')
                    ->comment('Date de dernière synchronisation avec Steve');
            }

            // Erreur de synchronisation
            if (!Schema::hasColumn('charging_points', 'steve_sync_error')) {
                $table->text('steve_sync_error')
                    ->nullable()
                    ->after('steve_synced_at')
                    ->comment('Message d\'erreur si synchronisation échouée');
            }

            // Auto-sync activé/désactivé par borne
            if (!Schema::hasColumn('charging_points', 'steve_auto_sync')) {
                $table->boolean('steve_auto_sync')
                    ->default(true)
                    ->after('steve_sync_error')
                    ->comment('Activer la synchronisation automatique avec Steve');
            }

            // Stocker la configuration OCPP
            if (!Schema::hasColumn('charging_points', 'ocpp_config')) {
                $table->json('ocpp_config')
                    ->nullable()
                    ->after('steve_auto_sync')
                    ->comment('Configuration OCPP de la borne (endpoint, version, etc.)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            $columns = [
                'steve_charge_box_pk',
                'steve_sync_status',
                'steve_synced_at',
                'steve_sync_error',
                'steve_auto_sync',
                'ocpp_config'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('charging_points', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

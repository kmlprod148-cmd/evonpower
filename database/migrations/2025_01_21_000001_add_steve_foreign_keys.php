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
        // Ajouter les contraintes de clé étrangère pour les tables SteVe
        // seulement si la table charging_points existe
        if (Schema::hasTable('charging_points')) {
            // Ajouter la contrainte pour steve_monitoring_logs si elle n'existe pas
            if (Schema::hasTable('steve_monitoring_logs') && !Schema::hasColumn('steve_monitoring_logs', 'charging_point_id')) {
                Schema::table('steve_monitoring_logs', function (Blueprint $table) {
                    $table->foreign('charging_point_id')->references('id')->on('charging_points')->onDelete('cascade');
                });
            }
            
            // Ajouter la contrainte pour steve_ocpp_commands si elle n'existe pas
            if (Schema::hasTable('steve_ocpp_commands') && !Schema::hasColumn('steve_ocpp_commands', 'charging_point_id')) {
                Schema::table('steve_ocpp_commands', function (Blueprint $table) {
                    $table->foreign('charging_point_id')->references('id')->on('charging_points')->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les contraintes de clé étrangère
        if (Schema::hasTable('steve_monitoring_logs')) {
            Schema::table('steve_monitoring_logs', function (Blueprint $table) {
                $table->dropForeign(['charging_point_id']);
            });
        }
        
        if (Schema::hasTable('steve_ocpp_commands')) {
            Schema::table('steve_ocpp_commands', function (Blueprint $table) {
                $table->dropForeign(['charging_point_id']);
            });
        }
    }
};

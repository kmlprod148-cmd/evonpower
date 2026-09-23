<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConnectivityFieldsToChargingPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('charging_points', function (Blueprint $table) {
            // Vérifier si les colonnes n'existent pas déjà avant de les ajouter
            if (!Schema::hasColumn('charging_points', 'connection_type')) {
                $table->string('connection_type')->nullable();
            }
            
            if (!Schema::hasColumn('charging_points', 'ip_address')) {
                $table->string('ip_address')->nullable();
            }
            
            if (!Schema::hasColumn('charging_points', 'communication_protocol')) {
                $table->string('communication_protocol')->nullable();
            }
            
            if (!Schema::hasColumn('charging_points', 'firmware_version')) {
                $table->string('firmware_version')->nullable();
            }
            
            if (!Schema::hasColumn('charging_points', 'authentication_required')) {
                $table->boolean('authentication_required')->default(false);
            }
            
            // Ajouter d'autres colonnes manquantes si nécessaire
            if (!Schema::hasColumn('charging_points', 'group_id')) {
                $table->unsignedBigInteger('group_id')->nullable();
                $table->foreign('group_id')->references('id')->on('groups')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('charging_points', 'location')) {
                $table->string('location')->nullable();
            }
            
            if (!Schema::hasColumn('charging_points', 'description')) {
                $table->text('description')->nullable();
            }
            
            if (!Schema::hasColumn('charging_points', 'power_output')) {
                $table->decimal('power_output', 8, 2)->nullable();
            }
            
            if (!Schema::hasColumn('charging_points', 'connector_type')) {
                $table->string('connector_type')->nullable();
            }
            
            if (!Schema::hasColumn('charging_points', 'installation_date')) {
                $table->date('installation_date')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('charging_points', function (Blueprint $table) {
            // Supprimer les colonnes dans l'ordre inverse pour éviter les problèmes de clés étrangères
            $table->dropColumn([
                'connection_type',
                'ip_address',
                'communication_protocol',
                'firmware_version',
                'authentication_required',
                'installation_date',
                'connector_type',
                'power_output',
                'description',
                'location'
            ]);
            
            // Si nécessaire, supprimer aussi la clé étrangère et la colonne group_id
            if (Schema::hasColumn('charging_points', 'group_id')) {
                $table->dropForeign(['group_id']);
                $table->dropColumn('group_id');
            }
        });
    }
}
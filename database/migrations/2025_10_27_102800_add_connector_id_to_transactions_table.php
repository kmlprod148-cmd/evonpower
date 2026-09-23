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
        Schema::table('transactions', function (Blueprint $table) {
            // Ajouter la colonne connector_id pour permettre l'association avec les connecteurs
            $table->unsignedBigInteger('connector_id')->nullable()->after('charging_point_id');
            
            // Ajouter la clé étrangère
            $table->foreign('connector_id')->references('id')->on('connectors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['connector_id']);
            $table->dropColumn('connector_id');
        });
    }
};
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
            // Supprimer la contrainte de clé étrangère existante
            $table->dropForeign(['user_id']);
            
            // Modifier la colonne pour permettre les valeurs null
            $table->unsignedBigInteger('user_id')->nullable()->change();
            
            // Recréer la contrainte de clé étrangère avec onDelete('set null')
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['user_id']);
            
            // Remettre la colonne comme non nullable
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            
            // Recréer la contrainte de clé étrangère originale
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};

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
        // Supprimer les tables des anciens profils
        Schema::dropIfExists('integrator_profiles');
        Schema::dropIfExists('partner_profiles');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recréer les tables si nécessaire (cette opération est destructive, donc la restauration n'est pas complète)
        Schema::create('integrator_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('partner_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }
};
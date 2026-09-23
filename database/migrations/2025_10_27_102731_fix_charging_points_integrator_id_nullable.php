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
            // Rendre integrator_id nullable pour permettre les tests et les points de recharge par défaut
            $table->unsignedBigInteger('integrator_id')->nullable()->change();
            
            // S'assurer que partner_id est aussi nullable
            $table->unsignedBigInteger('partner_id')->nullable()->change();
            
            // S'assurer que group_id est aussi nullable
            $table->unsignedBigInteger('group_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            // Remettre les contraintes NOT NULL si nécessaire
            $table->unsignedBigInteger('integrator_id')->nullable(false)->change();
            $table->unsignedBigInteger('partner_id')->nullable(false)->change();
            $table->unsignedBigInteger('group_id')->nullable(false)->change();
        });
    }
};
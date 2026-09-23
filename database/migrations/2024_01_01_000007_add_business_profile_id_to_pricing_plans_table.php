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
        // Ajoute uniquement la contrainte de clé étrangère si la colonne existe déjà
        Schema::table('pricing_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('pricing_plans', 'business_profile_id')) {
                $table->unsignedBigInteger('business_profile_id')->nullable();
            }
            $table->foreign('business_profile_id')
                ->references('id')
                ->on('business_profiles')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropForeign(['business_profile_id']);
            // Ne supprime pas la colonne si elle existait déjà avant
        });
    }
};
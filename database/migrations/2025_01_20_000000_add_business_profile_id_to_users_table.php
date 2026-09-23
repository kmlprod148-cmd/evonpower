<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ajoute le champ business_profile_id à la table users pour permettre
     * à chaque utilisateur (Admin, Operator, etc.) d'avoir son propre Business Profile
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'business_profile_id')) {
                $table->foreignId('business_profile_id')
                      ->nullable()
                      ->after('email')
                      ->constrained('business_profiles')
                      ->onDelete('set null');
                
                // Index pour améliorer les performances
                $table->index('business_profile_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'business_profile_id')) {
                $table->dropForeign(['business_profile_id']);
                $table->dropIndex(['business_profile_id']);
                $table->dropColumn('business_profile_id');
            }
        });
    }
};


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
            // Ajouter les champs de traçabilité
            if (!Schema::hasColumn('charging_points', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('integrator_id');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('charging_points', 'created_by_type')) {
                $table->string('created_by_type')->nullable()->after('created_by');
            }
            
            if (!Schema::hasColumn('charging_points', 'created_by_id')) {
                $table->unsignedBigInteger('created_by_id')->nullable()->after('created_by_type');
            }
            
            // Index pour les performances
            $table->index(['created_by', 'created_by_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            // Supprimer les index et contraintes
            $table->dropIndex(['created_by', 'created_by_type']);
            $table->dropForeign(['created_by']);
            
            // Supprimer les colonnes
            $table->dropColumn(['created_by', 'created_by_type', 'created_by_id']);
        });
    }
};

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
        Schema::table('partners', function (Blueprint $table) {
            // Ajouter le champ created_by s'il n'existe pas déjà
            if (!Schema::hasColumn('partners', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('integrator_id');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            // Supprimer la clé étrangère et la colonne
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};

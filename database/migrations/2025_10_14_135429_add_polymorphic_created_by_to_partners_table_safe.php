<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vérifier si les colonnes existent déjà (compatible SQLite et MySQL)
        $hasCreatedByType = Schema::hasColumn('partners', 'created_by_type');
        $hasCreatedById = Schema::hasColumn('partners', 'created_by_id');
        
        if (!$hasCreatedByType) {
            Schema::table('partners', function (Blueprint $table) {
                $table->string('created_by_type')->nullable()->after('created_by');
            });
        }
        
        if (!$hasCreatedById) {
            Schema::table('partners', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by_id')->nullable()->after('created_by_type');
            });
        }
        
        // Ajouter l'index s'il n'existe pas (compatible SQLite)
        try {
            Schema::table('partners', function (Blueprint $table) {
                $table->index(['created_by_type', 'created_by_id']);
            });
        } catch (\Exception $e) {
            // Si l'index existe déjà, ignorer l'erreur
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'duplicate') === false) {
                throw $e;
            }
        }
        
        // Migrer les données existantes vers le nouveau système
        try {
            DB::statement('UPDATE partners SET created_by_type = "App\\Models\\User", created_by_id = created_by WHERE created_by IS NOT NULL AND (created_by_type IS NULL OR created_by_id IS NULL)');
        } catch (\Exception $e) {
            // Si la colonne created_by n'existe pas, ignorer l'erreur
            if (strpos($e->getMessage(), 'no such column') === false) {
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            // Supprimer l'index et les colonnes polymorphiques
            $table->dropIndex(['created_by_type', 'created_by_id']);
            $table->dropColumn(['created_by_type', 'created_by_id']);
        });
    }
};

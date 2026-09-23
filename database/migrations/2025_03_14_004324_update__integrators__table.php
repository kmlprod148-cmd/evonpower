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
    public function up()
{
    // Vérifier si la colonne status existe et l'ajouter si nécessaire
    if (!Schema::hasColumn('integrators', 'status')) {
        Schema::table('integrators', function (Blueprint $table) {
            $table->string('status')->default('active')->after('id'); // Remplacez 'id' par une colonne existante
        });
    }

    // Ajouter profile_plan_id après status
    Schema::table('integrators', function (Blueprint $table) {
        if (!Schema::hasColumn('integrators', 'profile_plan_id')) {
            $table->foreignId('profile_plan_id')->nullable()->after('status')
                ->constrained('profile_plans')->nullOnDelete();
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('integrators') && Schema::hasColumn('integrators', 'profile_plan_id')) {
            Schema::table('integrators', function (Blueprint $table) {
                // Supprimer la contrainte de clé étrangère si elle existe
                $foreignKeys = $this->listTableForeignKeys('integrators');
                $foreignKeyName = 'integrators_profile_plan_id_foreign';
                
                if (in_array($foreignKeyName, $foreignKeys)) {
                    $table->dropForeign([$foreignKeyName]);
                }
                
                // Supprimer la colonne
                $table->dropColumn('profile_plan_id');
            });
            
            echo "Colonne 'profile_plan_id' supprimée de la table 'integrators'.\n";
        }

        Schema::table('integrators', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
    
    /**
     * Récupère la liste des clés étrangères d'une table.
     *
     * @param string $table
     * @return array
     */
    private function listTableForeignKeys($table)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();
        
        $foreignKeys = [];
        
        try {
            $tableDetails = $conn->listTableDetails($table);
            foreach ($tableDetails->getForeignKeys() as $foreignKey) {
                $foreignKeys[] = $foreignKey->getName();
            }
        } catch (\Exception $e) {
            // Ne rien faire en cas d'erreur
        }
        
        return $foreignKeys;
    }
};
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
        // Vérifier si la table charging_points existe
        if (!Schema::hasTable('charging_points')) {
            return;
        }

        // Vérifier si la colonne integrator_id existe
        if (!Schema::hasColumn('charging_points', 'integrator_id')) {
            return;
        }

        // Supprimer l'ancienne contrainte de clé étrangère si elle existe
        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE charging_points DROP FOREIGN KEY charging_points_integrator_id_foreign');
            }
        } catch (\Exception $e) {
            // La contrainte n'existe peut-être pas, continuer
        }

        // Nettoyer les données invalides - mettre à NULL les integrator_id qui n'existent pas dans la table integrators
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE charging_points 
                SET integrator_id = NULL 
                WHERE integrator_id IS NOT NULL 
                AND integrator_id NOT IN (SELECT id FROM integrators)
            ');
        } else {
            // Pour SQLite, on ne peut pas facilement mettre à jour avec des contraintes de clé étrangère
            echo "SQLite detected - skipping integrator_id data cleanup\n";
        }

        // Ajouter la nouvelle contrainte de clé étrangère vers la table integrators
        if (DB::getDriverName() === 'mysql') {
            Schema::table('charging_points', function (Blueprint $table) {
                $table->foreign('integrator_id')->references('id')->on('integrators')->onDelete('cascade');
            });
        } else {
            // Pour SQLite, on ne peut pas facilement ajouter des clés étrangères après création
            echo "SQLite detected - skipping foreign key addition\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('charging_points') && Schema::hasColumn('charging_points', 'integrator_id')) {
            Schema::table('charging_points', function (Blueprint $table) {
                $table->dropForeign(['integrator_id']);
            });
        }
    }
};

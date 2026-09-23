<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixStationsRelationship extends Migration
{
    public function up()
    {
        // Créer la table charging_sessions si elle n'existe pas
        // Créer la table charging_sessions si elle n'existe pas
        if (!Schema::hasTable('charging_sessions')) {
            Schema::create('charging_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('station_id');
                $table->timestamps();
            });
        }

        // Vérifier si la table stations existe
        if (Schema::hasTable('stations')) {
            // Vérifier si la colonne existe déjà
            if (!Schema::hasColumn('stations', 'integrator_id')) {
                Schema::table('stations', function (Blueprint $table) {
                    $table->unsignedBigInteger('integrator_id')->nullable();
                });
            } else {
                // Modifier le type de la colonne si nécessaire
                Schema::table('stations', function (Blueprint $table) {
                    $table->unsignedBigInteger('integrator_id')->nullable()->change();
                });
            }

            // Supprimer les anciennes contraintes étrangères
            $this->removeExistingForeignKeys('stations', 'integrator_id');

            // Attempt to drop foreign key if it exists
            try {
                Schema::table('stations', function (Blueprint $table) {
                    $table->dropForeign('stations_integrator_id_foreign');
                });
                echo "Dropped existing foreign key stations_integrator_id_foreign from stations table.\n";
            } catch (\Exception $e) {
                // Ignore the error if the foreign key doesn't exist
                echo "Foreign key stations_integrator_id_foreign did not exist or could not be dropped: " . $e->getMessage() . "\n";
            }

            // Ajouter la nouvelle contrainte
            Schema::table('stations', function (Blueprint $table) {
                $table->foreign('integrator_id')
                    ->references('id')
                    ->on('integrators')
                    ->onDelete('set null');
            });
        }
    }

    private function removeExistingForeignKeys($tableName, $columnName)
    {
        if (DB::getDriverName() === 'mysql') {
            // Get foreign keys for the column using information schema
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [env('DB_DATABASE'), $tableName, $columnName]);

            foreach ($foreignKeys as $key) {
                try {
                    Schema::table($tableName, function (Blueprint $table) use ($key) {
                        $table->dropForeign([$key->CONSTRAINT_NAME]);
                    });
                } catch (\Illuminate\Database\QueryException $e) {
                    // Ignore the error if the foreign key doesn't exist
                    if (strpos($e->getMessage(), '1091') === false) {
                        throw $e;
                    }
                }
            }
        }
        // Pour SQLite, on ne peut pas facilement récupérer les contraintes
        // On va simplement essayer de supprimer la contrainte par nom conventionnel
    }

    public function down()
    {
        // Ne rien faire dans la descente pour éviter de perdre des données
    }
}
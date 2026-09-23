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
        Schema::table('transactions', function (Blueprint $table) {
            // Identifiant de la transaction Steve (clé primaire dans Steve)
            if (!$this->columnExists('steve_transaction_id')) {
                $table->integer('steve_transaction_id')->nullable()->unique()->after('id');
            }
            
            // Tag OCPP utilisé pour la transaction
            if (!$this->columnExists('ocpp_id_tag')) {
                $table->string('ocpp_id_tag', 50)->nullable()->after('steve_transaction_id');
            }
            if (!$this->columnExists('ocpp_tag_pk')) {
                $table->integer('ocpp_tag_pk')->nullable()->after('ocpp_id_tag');
            }
            
            // ChargeBox (borne) associé
            if (!$this->columnExists('charge_box_pk')) {
                $table->integer('charge_box_pk')->nullable()->after('charging_point_id');
            }
            
            // Note: connector_id existe déjà dans la table via une migration précédente
            
            // Timestamps de début et fin (format OCPP)
            // Note: start_timestamp et stop_timestamp existent déjà, on ne les ajoute pas
            
            // Valeurs du compteur (en Wh)
            if (!$this->columnExists('start_value')) {
                $table->string('start_value', 50)->nullable()->after('stop_timestamp');
            }
            if (!$this->columnExists('stop_value')) {
                $table->string('stop_value', 50)->nullable()->after('start_value');
            }
            
            // Note: stop_reason existe déjà dans la table via une migration précédente
            // Acteur de l'arrêt
            if (!$this->columnExists('stop_event_actor')) {
                $table->enum('stop_event_actor', ['station', 'manual'])->nullable()->after('stop_value');
            }
            
            // Données calculées
            if (!$this->columnExists('energy_consumed_wh')) {
                $table->decimal('energy_consumed_wh', 12, 2)->nullable()->after('stop_event_actor');
            }
            if (!$this->columnExists('duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('energy_consumed_wh');
            }
        });

        // Ajouter les index après avoir ajouté les colonnes
        // Utiliser try-catch pour gérer les index qui pourraient déjà exister
        $this->addIndexIfNotExists('idx_steve_transaction_id', 'steve_transaction_id');
        $this->addIndexIfNotExists('idx_ocpp_id_tag', 'ocpp_id_tag');
        $this->addIndexIfNotExists('idx_cp_start_time', ['charging_point_id', 'start_timestamp']);
        $this->addIndexIfNotExists('idx_tag_start_time', ['ocpp_id_tag', 'start_timestamp']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les index d'abord (si ils existent)
        $this->dropIndexIfExists('idx_steve_transaction_id');
        $this->dropIndexIfExists('idx_ocpp_id_tag');
        $this->dropIndexIfExists('idx_cp_start_time');
        $this->dropIndexIfExists('idx_tag_start_time');
        
        // Supprimer les colonnes
        Schema::table('transactions', function (Blueprint $table) {
            $columnsToCheck = [
                'steve_transaction_id',
                'ocpp_id_tag',
                'ocpp_tag_pk',
                'charge_box_pk',
                'start_value',
                'stop_value',
                'stop_event_actor',
                'energy_consumed_wh',
                'duration_minutes'
            ];
            
            foreach ($columnsToCheck as $column) {
                if ($this->columnExists($column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Ajouter un index s'il n'existe pas déjà
     */
    private function addIndexIfNotExists(string $indexName, $columns): void
    {
        if (!$this->indexExists($indexName)) {
            Schema::table('transactions', function (Blueprint $table) use ($indexName, $columns) {
                $table->index($columns, $indexName);
            });
        }
    }

    /**
     * Supprimer un index s'il existe
     */
    private function dropIndexIfExists(string $indexName): void
    {
        if ($this->indexExists($indexName)) {
            Schema::table('transactions', function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }

    /**
     * Vérifier si un index existe (compatible SQLite et MySQL)
     */
    private function indexExists(string $indexName): bool
    {
        try {
            $connection = Schema::getConnection();
            $driver = $connection->getDriverName();
            
            if ($driver === 'sqlite') {
                $result = $connection->selectOne(
                    "SELECT COUNT(*) as count FROM sqlite_master WHERE type='index' AND name=?",
                    [$indexName]
                );
                return $result->count > 0;
            }
            
            // MySQL/MariaDB
            $databaseName = $connection->getDatabaseName();
            $query = "SELECT COUNT(*) as count 
                      FROM information_schema.statistics 
                      WHERE table_schema = ? 
                      AND table_name = 'transactions' 
                      AND index_name = ?";
            
            $result = $connection->selectOne($query, [$databaseName, $indexName]);
            return $result->count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Vérifier si une colonne existe (compatible SQLite et MySQL)
     */
    private function columnExists(string $columnName): bool
    {
        return Schema::hasColumn('transactions', $columnName);
    }
};

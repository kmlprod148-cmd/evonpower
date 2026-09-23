<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Relations
        $this->addColumnIfNotExists('groups', 'integrator_id', 'BIGINT UNSIGNED NULL AFTER external_id');
        $this->addColumnIfNotExists('groups', 'partner_id', 'BIGINT UNSIGNED NULL AFTER integrator_id');
        $this->addColumnIfNotExists('groups', 'parent_group_id', 'BIGINT UNSIGNED NULL AFTER partner_id');
        
        // 2. Informations de base
        $this->addColumnIfNotExists('groups', 'name', 'VARCHAR(255) NOT NULL AFTER parent_group_id');
        $this->addColumnIfNotExists('groups', 'slug', 'VARCHAR(255) NULL AFTER name');
        $this->addColumnIfNotExists('groups', 'description', 'TEXT NULL AFTER slug');
        $this->addColumnIfNotExists('groups', 'status', "ENUM('active', 'inactive', 'maintenance') DEFAULT 'active' AFTER description");
        $this->addColumnIfNotExists('groups', 'type', "ENUM('station', 'area', 'organization', 'other') DEFAULT 'station' AFTER status");
        $this->addColumnIfNotExists('groups', 'is_featured', 'BOOLEAN DEFAULT FALSE AFTER type');
        
        // 3. Localisation
        $this->addColumnIfNotExists('groups', 'location', 'VARCHAR(255) NULL AFTER is_featured');
        $this->addColumnIfNotExists('groups', 'latitude', 'DECIMAL(10,8) NULL AFTER location');
        $this->addColumnIfNotExists('groups', 'longitude', 'DECIMAL(11,8) NULL AFTER latitude');
        $this->addColumnIfNotExists('groups', 'address', 'VARCHAR(255) NULL AFTER longitude');
        $this->addColumnIfNotExists('groups', 'city', 'VARCHAR(255) NULL AFTER address');
        $this->addColumnIfNotExists('groups', 'postal_code', 'VARCHAR(20) NULL AFTER city');
        $this->addColumnIfNotExists('groups', 'country', 'VARCHAR(50) NULL AFTER postal_code');
        $this->addColumnIfNotExists('groups', 'timezone', "VARCHAR(50) DEFAULT 'UTC' AFTER country");
        
        // 4. Accès et capacité
        $this->addColumnIfNotExists('groups', 'public_access', 'BOOLEAN DEFAULT TRUE AFTER timezone');
        $this->addColumnIfNotExists('groups', 'max_charging_points', 'INT NULL AFTER public_access');
        
        // 5. Contact et gestion
        $this->addColumnIfNotExists('groups', 'contact_name', 'VARCHAR(255) NULL AFTER max_charging_points');
        $this->addColumnIfNotExists('groups', 'contact_email', 'VARCHAR(255) NULL AFTER contact_name');
        $this->addColumnIfNotExists('groups', 'contact_phone', 'VARCHAR(50) NULL AFTER contact_email');
        $this->addColumnIfNotExists('groups', 'manager_name', 'VARCHAR(255) NULL AFTER contact_phone');
        $this->addColumnIfNotExists('groups', 'manager_email', 'VARCHAR(255) NULL AFTER manager_name');
        $this->addColumnIfNotExists('groups', 'manager_phone', 'VARCHAR(50) NULL AFTER manager_email');
        
        // 6. Affaires
        $this->addColumnIfNotExists('groups', 'commission_rate', 'DECIMAL(5,2) NULL AFTER manager_phone');
        
        // 7. Médias et apparence
        $this->addColumnIfNotExists('groups', 'image', 'VARCHAR(255) NULL AFTER commission_rate');
        $this->addColumnIfNotExists('groups', 'color_code', 'VARCHAR(20) NULL AFTER image');
        
        // 8. Données structurées (JSON)
        $this->addColumnIfNotExists('groups', 'opening_hours', 'JSON NULL AFTER color_code');
        $this->addColumnIfNotExists('groups', 'services', 'JSON NULL AFTER opening_hours');
        $this->addColumnIfNotExists('groups', 'restrictions', 'JSON NULL AFTER services');
        $this->addColumnIfNotExists('groups', 'access_control', 'JSON NULL AFTER restrictions');
        $this->addColumnIfNotExists('groups', 'metadata', 'JSON NULL AFTER access_control');
        $this->addColumnIfNotExists('groups', 'analytics_config', 'JSON NULL AFTER metadata');
        
        // 9. Plan tarifaire
        $this->addColumnIfNotExists('groups', 'pricing_plan_id', 'BIGINT UNSIGNED NULL AFTER analytics_config');
        
        // 10. Soft delete
        $this->addColumnIfNotExists('groups', 'deleted_at', 'TIMESTAMP NULL AFTER updated_at');
        
        // Ajout des clés étrangères (seulement si les tables référencées existent)
        // Commentez ces lignes si vous n'avez pas encore créé les tables référencées
        /*
        $this->addForeignKeyIfNotExists('groups', 'integrator_id', 'integrators', 'id');
        $this->addForeignKeyIfNotExists('groups', 'partner_id', 'partners', 'id');
        $this->addForeignKeyIfNotExists('groups', 'parent_group_id', 'groups', 'id');
        $this->addForeignKeyIfNotExists('groups', 'pricing_plan_id', 'pricing_plans', 'id');
        */
        
        // Ajout d'index
        $this->addIndexIfNotExists('groups', 'status');
        $this->addIndexIfNotExists('groups', 'type');
        $this->addIndexIfNotExists('groups', 'public_access');
        $this->addIndexIfNotExists('groups', 'slug', 'UNIQUE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ne pas supprimer les colonnes pour éviter de perdre des données
    }
    
    /**
     * Ajoute une colonne si elle n'existe pas déjà.
     */
    private function addColumnIfNotExists($table, $column, $definition)
    {
        if (DB::getDriverName() === 'mysql') {
            $columns = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = '{$column}'");
            if (empty($columns)) {
                DB::statement("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            }
        } else {
            // Pour SQLite, vérifier avec pragma
            $columns = DB::select("PRAGMA table_info({$table})");
            $columnExists = false;
            foreach ($columns as $col) {
                if ($col->name === $column) {
                    $columnExists = true;
                    break;
                }
            }
            if (!$columnExists) {
                // Pour SQLite, simplifier la définition
                $sqliteDefinition = $this->convertToSqliteDefinition($definition);
                DB::statement("ALTER TABLE {$table} ADD COLUMN {$column} {$sqliteDefinition}");
            }
        }
    }
    
    /**
     * Convertit une définition MySQL en définition SQLite.
     */
    private function convertToSqliteDefinition($definition)
    {
        // Simplifier les définitions pour SQLite
        $definition = str_replace('BIGINT UNSIGNED', 'INTEGER', $definition);
        $definition = str_replace('VARCHAR(255)', 'TEXT', $definition);
        $definition = str_replace('VARCHAR(50)', 'TEXT', $definition);
        $definition = str_replace('VARCHAR(20)', 'TEXT', $definition);
        $definition = str_replace('DECIMAL(10,8)', 'REAL', $definition);
        $definition = str_replace('DECIMAL(11,8)', 'REAL', $definition);
        $definition = str_replace('DECIMAL(5,2)', 'REAL', $definition);
        $definition = str_replace('BOOLEAN', 'INTEGER', $definition);
        $definition = str_replace('TIMESTAMP', 'DATETIME', $definition);
        $definition = str_replace('JSON', 'TEXT', $definition);
        
        // Supprimer les contraintes ENUM pour SQLite
        $definition = preg_replace("/ENUM\([^)]+\)/", 'TEXT', $definition);
        
        // Supprimer la clause AFTER pour SQLite
        $definition = preg_replace("/\s+AFTER\s+\w+/", '', $definition);
        
        return $definition;
    }
    
    /**
     * Ajoute une clé étrangère si elle n'existe pas déjà.
     */
    private function addForeignKeyIfNotExists($table, $column, $referencedTable, $referencedColumn)
    {
        if (DB::getDriverName() === 'mysql') {
            $keyName = "{$table}_{$column}_foreign";
            $constraints = DB::select("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                                  WHERE TABLE_NAME = '{$table}' 
                                  AND COLUMN_NAME = '{$column}' 
                                  AND CONSTRAINT_NAME = '{$keyName}'");
            
            if (empty($constraints)) {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$keyName} 
                               FOREIGN KEY ({$column}) REFERENCES {$referencedTable}({$referencedColumn}) 
                               ON DELETE CASCADE");
            }
        }
        // Pour SQLite, on ne peut pas ajouter de clés étrangères après création de la table
    }
    
    /**
     * Ajoute un index si il n'existe pas déjà.
     */
    private function addIndexIfNotExists($table, $column, $type = null)
    {
        if (DB::getDriverName() === 'mysql') {
            $indexName = "{$table}_{$column}_index";
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Column_name = '{$column}'");
            
            if (empty($indexes)) {
                $indexType = $type ? $type : '';
                DB::statement("ALTER TABLE {$table} ADD {$indexType} INDEX {$indexName} ({$column})");
            }
        }
        // Pour SQLite, on ne peut pas ajouter d'index après création de la table
    }
};
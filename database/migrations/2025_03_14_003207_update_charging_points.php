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
        // Vérifier si la table existe avant de tenter de la modifier
        if (Schema::hasTable('charging_points')) {
            // Obtenir la liste des colonnes existantes
            $columns = DB::getSchemaBuilder()->getColumnListing('charging_points');
            
            Schema::table('charging_points', function (Blueprint $table) use ($columns) {
                // Ajout de colonnes d'identifiants et relations si elles n'existent pas encore
                if (!in_array('external_id', $columns)) {
                    $table->string('external_id')->nullable()->unique()->after('id');
                }
                
                if (!in_array('integrator_id', $columns)) {
                    $table->foreignId('integrator_id')->nullable()->after('external_id');
                    // Ne pas ajouter de contrainte immédiatement pour éviter les erreurs
                }
                
                if (!in_array('partner_id', $columns)) {
                    $table->foreignId('partner_id')->nullable()->after('integrator_id');
                }
                
                if (!in_array('group_id', $columns)) {
                    $table->foreignId('group_id')->nullable()->after('partner_id');
                }
                
                if (!in_array('pricing_plan_id', $columns)) {
                    $table->foreignId('pricing_plan_id')->nullable()->after('group_id');
                }
                
                // Informations de base
                if (!in_array('serial_number', $columns)) {
                    $table->string('serial_number')->nullable()->after('pricing_plan_id');
                    // Pas d'unique ici pour éviter les conflits potentiels
                }
                
                if (!in_array('name', $columns)) {
                    $table->string('name')->nullable()->after('serial_number');
                }
                
                if (!in_array('model', $columns)) {
                    $table->string('model')->nullable()->after('name');
                }
                
                if (!in_array('manufacturer', $columns)) {
                    $table->string('manufacturer')->nullable()->after('model');
                }
                
                if (!in_array('firmware_version', $columns)) {
                    $table->string('firmware_version')->nullable()->after('manufacturer');
                }
                
                if (!in_array('status', $columns)) {
                    $table->enum('status', ['online', 'offline', 'charging', 'error', 'maintenance', 'reserved'])->default('offline')->after('firmware_version');
                }
                
                // Localisation
                if (!in_array('location', $columns)) {
                    $table->string('location')->nullable()->after('status');
                }
                
                if (!in_array('latitude', $columns)) {
                    $table->decimal('latitude', 10, 8)->nullable()->after('location');
                }
                
                if (!in_array('longitude', $columns)) {
                    $table->decimal('longitude', 12, 8)->nullable()->after('latitude'); // Changed from 11,8 to 12,8 for full range
                }
                
                if (!in_array('address', $columns)) {
                    $table->string('address')->nullable()->after('longitude');
                }
                
                if (!in_array('city', $columns)) {
                    $table->string('city')->nullable()->after('address');
                }
                
                if (!in_array('postal_code', $columns)) {
                    $table->string('postal_code')->nullable()->after('city');
                }
                
                if (!in_array('country', $columns)) {
                    $table->string('country')->nullable()->after('postal_code');
                }
                
                if (!in_array('timezone', $columns)) {
                    $table->string('timezone')->default('UTC')->after('country');
                }
                
                // Informations d'installation et de maintenance
                if (!in_array('installation_date', $columns)) {
                    $table->date('installation_date')->nullable()->after('timezone');
                }
                
                if (!in_array('last_maintenance_date', $columns)) {
                    $table->date('last_maintenance_date')->nullable()->after('installation_date');
                }
                
                if (!in_array('next_maintenance_date', $columns)) {
                    $table->date('next_maintenance_date')->nullable()->after('last_maintenance_date');
                }
                
                if (!in_array('installation_notes', $columns)) {
                    $table->string('installation_notes')->nullable()->after('next_maintenance_date');
                }
                
                // Accès et configuration
                if (!in_array('access_type', $columns)) {
                    $table->enum('access_type', ['public', 'private', 'restricted'])->default('public')->after('installation_notes');
                }
                
                if (!in_array('public_access', $columns)) {
                    $table->boolean('public_access')->default(true)->after('access_type');
                }
                
                if (!in_array('access_code', $columns)) {
                    $table->string('access_code')->nullable()->after('public_access');
                }
                
                if (!in_array('access_control', $columns)) {
                    $table->json('access_control')->nullable()->after('access_code');
                }
                
                // Informations techniques
                if (!in_array('ip_address', $columns)) {
                    $table->string('ip_address')->nullable()->after('access_control');
                }
                
                if (!in_array('mac_address', $columns)) {
                    $table->string('mac_address')->nullable()->after('ip_address');
                }
                
                if (!in_array('sim_card_number', $columns)) {
                    $table->string('sim_card_number')->nullable()->after('mac_address');
                }
                
                if (!in_array('communication_protocol', $columns)) {
                    $table->string('communication_protocol')->nullable()->after('sim_card_number');
                }
                
                if (!in_array('communication_protocol_version', $columns)) {
                    $table->string('communication_protocol_version')->nullable()->after('communication_protocol');
                }
                
                // Informations contractuelles et commerciales
                if (!in_array('commission_rate', $columns)) {
                    $table->decimal('commission_rate', 5, 2)->nullable()->after('communication_protocol_version');
                }
                
                if (!in_array('contract_reference', $columns)) {
                    $table->string('contract_reference')->nullable()->after('commission_rate');
                }
                
                // QR code et identification
                if (!in_array('qr_code', $columns)) {
                    $table->string('qr_code')->nullable()->after('contract_reference');
                }
                
                if (!in_array('evse_id', $columns)) {
                    $table->string('evse_id')->nullable()->after('qr_code');
                }
                
                // Configuration et paramètres
                if (!in_array('configuration', $columns)) {
                    $table->json('configuration')->nullable()->after('evse_id');
                }
                
                if (!in_array('capabilities', $columns)) {
                    $table->json('capabilities')->nullable()->after('configuration');
                }
                
                if (!in_array('smart_charging_profile', $columns)) {
                    $table->json('smart_charging_profile')->nullable()->after('capabilities');
                }
                
                if (!in_array('load_balancing_settings', $columns)) {
                    $table->json('load_balancing_settings')->nullable()->after('smart_charging_profile');
                }
                
                // Suivi et mesures
                if (!in_array('total_energy_delivered', $columns)) {
                    $table->decimal('total_energy_delivered', 12, 3)->default(0)->after('load_balancing_settings');
                }
                
                if (!in_array('total_charging_sessions', $columns)) {
                    $table->integer('total_charging_sessions')->default(0)->after('total_energy_delivered');
                }
                
                if (!in_array('last_connection', $columns)) {
                    $table->timestamp('last_connection')->nullable()->after('total_charging_sessions');
                }
                
                if (!in_array('last_status_update', $columns)) {
                    $table->timestamp('last_status_update')->nullable()->after('last_connection');
                }
                
                if (!in_array('last_used_at', $columns)) {
                    $table->timestamp('last_used_at')->nullable()->after('last_status_update');
                }
                
                // Champs additionnels
                if (!in_array('notes', $columns)) {
                    $table->text('notes')->nullable()->after('last_used_at');
                }
                
                if (!in_array('metadata', $columns)) {
                    $table->json('metadata')->nullable()->after('notes');
                }
                
                // Assurer que les timestamps standard existent
                if (!in_array('created_at', $columns)) {
                    $table->timestamp('created_at')->nullable();
                }
                
                if (!in_array('updated_at', $columns)) {
                    $table->timestamp('updated_at')->nullable();
                }
                
                if (!in_array('deleted_at', $columns)) {
                    $table->softDeletes();
                }
                
                // Ajouter des index pour améliorer les performances
                try {
                    if (!$this->hasIndex('charging_points', 'charging_points_status_index')) {
                        $table->index('status');
                    }
                    
                    if (!$this->hasIndex('charging_points', 'charging_points_access_type_index')) {
                        $table->index('access_type');
                    }
                    
                    if (!$this->hasIndex('charging_points', 'charging_points_public_access_index')) {
                        $table->index('public_access');
                    }
                    
                    if (!$this->hasIndex('charging_points', 'charging_points_latitude_longitude_index')) {
                        $table->index(['latitude', 'longitude']);
                    }
                } catch (\Exception $e) {
                    // Ignorer les erreurs d'index, ils ne sont pas critiques
                }
            });
            
            // Ajouter les contraintes de clé étrangère séparément après avoir vérifié l'existence des tables
            $this->addForeignKeyIfTableExists('charging_points', 'integrator_id', 'integrators');
            $this->addForeignKeyIfTableExists('charging_points', 'partner_id', 'partners');
            $this->addForeignKeyIfTableExists('charging_points', 'group_id', 'groups');
            $this->addForeignKeyIfTableExists('charging_points', 'pricing_plan_id', 'pricing_plans');
            
            // Assurer l'unicité du serial_number
            $this->addUniqueConstraint('charging_points', 'serial_number');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ne pas supprimer la table pour éviter de perdre des données
        // Si vous voulez vraiment supprimer la table, décommentez la ligne ci-dessous
        // Schema::dropIfExists('charging_points');
    }
    
    /**
     * Vérifie si un index existe sur une table.
     */
    private function hasIndex($table, $index)
    {
        return collect(DB::select("PRAGMA index_list('{$table}')"))->pluck('name')->contains($index);
        return $indexExists;
    }
    
    /**
     * Ajoute une contrainte de clé étrangère si la table référencée existe.
     */
    private function addForeignKeyIfTableExists($table, $column, $referencedTable)
    {
        try {
            if (Schema::hasTable($referencedTable)) {
                Schema::table($table, function (Blueprint $table) use ($column, $referencedTable) {
                    // Vérifier si la clé étrangère existe déjà
                    $keyName = "{$table->getTable()}_{$column}_foreign";
                    $constraints = DB::select("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                        WHERE TABLE_NAME = '{$table->getTable()}'
                                        AND COLUMN_NAME = '{$column}'
                                        AND CONSTRAINT_NAME = '{$keyName}'");
                    
                    if (empty($constraints)) {
                        $table->foreign($column)->references('id')->on($referencedTable)->onDelete('set null');
                    }
                });
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs, la contrainte sera ajoutée plus tard
        }
    }
    
    /**
     * Ajoute une contrainte d'unicité si elle n'existe pas déjà.
     */
    private function addUniqueConstraint($table, $column)
    {
        try {
            $uniqueIndexName = "{$table}_{$column}_unique";
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$uniqueIndexName}'");
            
            if (empty($indexes)) {
                DB::statement("ALTER TABLE {$table} ADD UNIQUE KEY {$uniqueIndexName} ({$column})");
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs, la contrainte sera ajoutée plus tard
        }
    }
};
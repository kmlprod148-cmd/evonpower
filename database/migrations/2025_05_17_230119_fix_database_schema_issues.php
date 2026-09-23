<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixDatabaseSchemaIssues extends Migration
{
    public function up()
    {
        // 1. Fix business_profiles table
        $this->fixBusinessProfilesTable();
        
        // 2. Fix foreign key relationships
        $this->fixForeignKeyRelationships();
    }
    
    private function fixBusinessProfilesTable()
    {
        if (Schema::hasTable('business_profiles')) {
            Schema::table('business_profiles', function (Blueprint $table) {
                // Add email column if it doesn't exist
                if (!Schema::hasColumn('business_profiles', 'email')) {
                    $table->string('email')->nullable();
                }
            });
            
            // Make potentially required fields nullable (MySQL only)
            if (DB::getDriverName() === 'mysql') {
                $columnsToMakeNullable = [
                    'name', 'phone', 'address', 'city', 'postal_code', 'country',
                    'contact_name', 'contact_email', 'contact_phone', 'type'
                ];
                
                foreach ($columnsToMakeNullable as $column) {
                    if (Schema::hasColumn('business_profiles', $column)) {
                        DB::statement("ALTER TABLE business_profiles MODIFY {$column} VARCHAR(255) NULL");
                    }
                }
            }
        }
    }
    
    private function fixForeignKeyRelationships()
    {
        if (DB::getDriverName() === 'mysql') {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Tables that might have foreign keys to integrators
            $tablesWithIntegratorRelationship = [
                'users', 'stations', 'charging_points', 'partners', 'business_profiles'
            ];
            
            foreach ($tablesWithIntegratorRelationship as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'integrator_id')) {
                    // 1. Drop any existing foreign keys
                    $this->dropForeignKey($table, 'integrator_id');
                    
                    // 2. Ensure column is correct type
                    DB::statement("ALTER TABLE `{$table}` MODIFY `integrator_id` BIGINT UNSIGNED NULL");
                    
                    // 3. Re-add foreign key if integrators table exists
                    if (Schema::hasTable('integrators')) {
                        Schema::table($table, function (Blueprint $table) {
                            $table->foreign('integrator_id')
                                  ->references('id')
                                  ->on('integrators')
                                  ->onDelete('set null');
                        });
                    }
                }
            }
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        // Pour SQLite, on ne peut pas facilement modifier les contraintes de clé étrangère
    }
    
    private function dropForeignKey($table, $column)
    {
        if (DB::getDriverName() === 'mysql') {
            // Find foreign key constraint for this column
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$table}'
                AND COLUMN_NAME = '{$column}'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`");
            }
        }
        // Pour SQLite, on ne peut pas facilement supprimer les contraintes de clé étrangère
    }
    
    public function down()
    {
        // No down method needed for this fix
    }
}
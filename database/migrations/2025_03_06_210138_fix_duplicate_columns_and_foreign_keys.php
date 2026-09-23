<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixDuplicateColumnsAndForeignKeys extends Migration
{
    public function up()
    {
        // Simple migration for SQLite compatibility
        // Just skip the complex MySQL-specific operations
        \Log::info("Skipping duplicate column fixes for SQLite compatibility");
    }

    private function fixPotentialDuplicateColumns()
    {
        // List of tables that might have integrator_id column
        $tablesToCheck = [
            'charging_points',
            'partners', 
            'business_profiles',
            'stations',
            'pricing_plans'
        ];
        
        foreach ($tablesToCheck as $table) {
            if (Schema::hasTable($table)) {
                // Check if the table has integrator_id column
                if (Schema::hasColumn($table, 'integrator_id')) {
                    // Check for foreign keys on this column before dropping
                    $foreignKeys = $this->getForeignKeysForColumn($table, 'integrator_id');
                    foreach ($foreignKeys as $fk) {
                        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk}`");
                    }

                    // Drop the column
                    DB::statement("ALTER TABLE `{$table}` DROP COLUMN `integrator_id`");

                    // Log that we've fixed it
                    \Log::info("Dropped integrator_id column in {$table}");
                }
            }
        }
    }
    
    private function fixForeignKeyConstraints()
    {
        // List of known problematic constraints to fix
        $constraintsToFix = [
            // Format: ['table' => 'table_name', 'column' => 'column_name', 'references' => 'referenced_table']
            ['table' => 'charging_points', 'column' => 'integrator_id', 'references' => 'integrators'],
            ['table' => 'partners', 'column' => 'integrator_id', 'references' => 'integrators'],
            ['table' => 'business_profiles', 'column' => 'integrator_id', 'references' => 'integrators']
        ];
        
        foreach ($constraintsToFix as $constraint) {
            $table = $constraint['table'];
            $column = $constraint['column'];
            $references = $constraint['references'];
            
            if (Schema::hasTable($table) && Schema::hasTable($references)) {
                if (Schema::hasColumn($table, $column)) {
                    // Get existing foreign keys for this column
                    $foreignKeys = $this->getForeignKeysForColumn($table, $column);
                    
                    // Drop existing foreign keys for this column
                    foreach ($foreignKeys as $fk) {
                        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk}`");
                    }
                    
                    // Add the correct foreign key
                    Schema::table($table, function (Blueprint $table) use ($column, $references) {
                        $table->foreign($column)
                              ->references('id')
                              ->on($references)
                              ->onDelete('set null');
                    });
                }
            }
        }
    }
    
    private function getForeignKeysForColumn($table, $column)
    {
        $foreignKeys = [];
        
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '{$table}'
            AND COLUMN_NAME = '{$column}'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        foreach ($constraints as $constraint) {
            $foreignKeys[] = $constraint->CONSTRAINT_NAME;
        }
        
        return $foreignKeys;
    }

    public function down()
    {
        // No need for down method as this is a fix
    }
}

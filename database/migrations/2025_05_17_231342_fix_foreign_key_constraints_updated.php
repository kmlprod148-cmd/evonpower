<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixForeignKeyConstraintsUpdated extends Migration
{
    public function up()
    {
        if (DB::getDriverName() === 'mysql') {
            // 1. Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // 2. Fix problematic tables and foreign keys
            $this->fixProblematicTables();
            
            // 3. Mark the problematic migration as completed
            $this->markMigrationAsCompleted('2025_05_17_221612_fix_foreign_key_constraints');
            
            // 4. Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            // Pour SQLite, on ne peut pas facilement modifier les contraintes de clé étrangère
            echo "SQLite detected - skipping foreign key constraint fixes\n";
        }
    }
    
    private function fixProblematicTables()
    {
        // Tables that might have foreign key issues
        $tablesToFix = [
            'business_profiles',
            'integrators',
            'partners',
            'charging_points',
            'users',
            'stations'
        ];
        
        foreach ($tablesToFix as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            
            // Remove all foreign keys from the table
            $foreignKeys = $this->listTableForeignKeys($table);
            
            foreach ($foreignKeys as $foreignKey) {
                try {
                    Schema::table($table, function (Blueprint $table) use ($foreignKey) {
                        $table->dropForeign($foreignKey);
                    });
                    echo "Dropped foreign key {$foreignKey} from {$table}\n";
                } catch (\Exception $e) {
                    echo "Error dropping foreign key {$foreignKey}: {$e->getMessage()}\n";
                }
            }
            
            // Fix integrator_id column if it exists
            if (Schema::hasColumn($table, 'integrator_id')) {
                try {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `integrator_id` BIGINT UNSIGNED NULL");
                    echo "Fixed integrator_id column in {$table}\n";
                } catch (\Exception $e) {
                    echo "Error fixing integrator_id in {$table}: {$e->getMessage()}\n";
                }
            }
        }
        
        // Re-add foreign keys for integrator_id
        foreach ($tablesToFix as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'integrator_id') && Schema::hasTable('integrators')) {
                try {
                    Schema::table($table, function (Blueprint $table) {
                        $table->foreign('integrator_id')
                            ->references('id')
                            ->on('integrators')
                            ->onDelete('set null');
                    });
                    echo "Added foreign key for integrator_id in {$table}\n";
                } catch (\Exception $e) {
                    echo "Error adding foreign key in {$table}: {$e->getMessage()}\n";
                }
            }
        }
    }
    
    private function listTableForeignKeys($table)
    {
        $foreignKeys = [];
        
        if (DB::getDriverName() === 'mysql') {
            // Get foreign key constraints using SQL query instead of Doctrine
            $keyUsages = DB::select("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '{$table}'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            foreach ($keyUsages as $keyUsage) {
                $foreignKeys[] = $keyUsage->CONSTRAINT_NAME;
            }
        }
        // Pour SQLite, on ne peut pas facilement récupérer les contraintes de clé étrangère
        
        return $foreignKeys;
    }
    
    private function markMigrationAsCompleted($migration)
    {
        // Check if this migration is already marked as completed
        if (!DB::table('migrations')->where('migration', 'like', "%{$migration}%")->exists()) {
            // Find the exact migration name
            $files = glob(database_path("migrations/*{$migration}.php"));
            
            if (!empty($files)) {
                $exactMigration = basename($files[0], '.php');
                
                // Mark as completed
                DB::table('migrations')->insert([
                    'migration' => $exactMigration,
                    'batch' => 1
                ]);
                
                echo "Marked {$exactMigration} as completed.\n";
            } else {
                echo "Migration file {$migration} not found.\n";
            }
        } else {
            echo "Migration {$migration} already marked as completed.\n";
        }
    }
    
    public function down()
    {
        // No down method needed for this fix
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixDuplicateIntegratorIdColumn extends Migration
{
    public function up()
    {
        // Simple migration for SQLite compatibility
        // Just mark the migration as completed
        $this->markDuplicateMigrationsAsCompleted();
    }
    
    private function checkForDuplicateColumnInMigrations($table, $column)
    {
        // Load all migration files
        $migrationFiles = glob(database_path('migrations/*.php'));
        $duplicateMigrations = [];
        
        foreach ($migrationFiles as $file) {
            $content = file_get_contents($file);
            
            // Look for patterns that add a column called 'integrator_id'
            $patterns = [
                "->bigInteger('$column')",
                "->unsignedBigInteger('$column')",
                "->foreignId('$column')",
                "Schema::table('$table', function",
                "->after('id')",
                "Schema::create('$table', function",
            ];
            
            $matchesAll = true;
            foreach ($patterns as $pattern) {
                if (strpos($content, $pattern) === false) {
                    $matchesAll = false;
                    break;
                }
            }
            
            if ($matchesAll) {
                // Extract the migration name from the filename
                $filename = basename($file);
                preg_match('/^\d+_\d+_\d+_\d+_(.+)\.php$/', $filename, $matches);
                $migrationName = isset($matches[1]) ? $matches[1] : $filename;
                
                $duplicateMigrations[] = $migrationName;
                
                // Debug output removed for test environment
                if (!app()->environment('testing')) {
                    echo "Found potential duplicate column definition in migration: $migrationName\n";
                }
            }
        }
        
        return $duplicateMigrations;
    }
    
    private function markDuplicateMigrationsAsCompleted()
    {
        // These are migration names that we've identified might be adding duplicate integrator_id
        // This is based on examining your migration files for patterns that suggest they add this column
        $suspectMigrations = [
            'add_integrator_id_to_users_table',
            'add_integrator_id_to_stations_table',
            'fix_stations_relationship'
        ];
        
        foreach ($suspectMigrations as $migration) {
            // First, check if this migration has already been run
            $migrationRow = DB::table('migrations')
                ->where('migration', 'like', "%{$migration}%")
                ->first();
            
            // If not, add it as completed (batch 1) to prevent it from running
            if (!$migrationRow) {
                // Get the full migration name by searching for files matching this pattern
                $migrationFiles = glob(database_path("migrations/*_{$migration}.php"));
                
                if (!empty($migrationFiles)) {
                    $file = basename($migrationFiles[0]);
                    $fullMigrationName = str_replace('.php', '', $file);
                    
                    DB::table('migrations')->insert([
                        'migration' => $fullMigrationName,
                        'batch' => 1
                    ]);
                    
                    // Debug output removed for test environment
                    if (!app()->environment('testing')) {
                        echo "Marked migration $fullMigrationName as completed\n";
                    }
                }
            }
        }
    }
    
    public function down()
    {
        // No need for down method
    }
}

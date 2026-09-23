<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if column exists in table
     */
    private function columnExists(string $table, string $columnName): bool
    {
        try {
            // Try SQLite first
            $columns = DB::select("PRAGMA table_info({$table})");
            foreach ($columns as $column) {
                if ($column->name === $columnName) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            // Fallback for MySQL
            try {
                $columns = DB::select("SHOW COLUMNS FROM {$table} LIKE '{$columnName}'");
                return !empty($columns);
            } catch (\Exception $e2) {
                return false;
            }
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: utiliser DB::statement directement
            if (!$this->columnExists('ocpp_transactions', 'version')) {
                DB::statement("ALTER TABLE ocpp_transactions ADD COLUMN version INTEGER NOT NULL DEFAULT 1");
            }
            
            if (!$this->columnExists('ocpp_transactions', 'idempotency_key')) {
                DB::statement("ALTER TABLE ocpp_transactions ADD COLUMN idempotency_key TEXT NULL");
                // Créer l'index unique séparément (SQLite permet plusieurs NULL dans un index unique)
                DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS ocpp_transactions_idempotency_key_unique ON ocpp_transactions(idempotency_key)");
            }
        } else {
            // MySQL/PostgreSQL: utiliser Schema
            Schema::table('ocpp_transactions', function (Blueprint $table) {
                if (!$this->columnExists('ocpp_transactions', 'version')) {
                    $table->unsignedInteger('version')->default(1)->after('meta')
                        ->comment('Version pour optimistic locking');
                }
                
                if (!$this->columnExists('ocpp_transactions', 'idempotency_key')) {
                    $table->string('idempotency_key')->nullable()->unique()->after('version')
                        ->comment('Clé d\'idempotence pour les appels API');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: supprimer les colonnes directement
            if ($this->columnExists('ocpp_transactions', 'idempotency_key')) {
                DB::statement("DROP INDEX IF EXISTS ocpp_transactions_idempotency_key_unique");
                DB::statement("ALTER TABLE ocpp_transactions DROP COLUMN idempotency_key");
            }
            if ($this->columnExists('ocpp_transactions', 'version')) {
                DB::statement("ALTER TABLE ocpp_transactions DROP COLUMN version");
            }
        } else {
            // MySQL/PostgreSQL: utiliser Schema
            Schema::table('ocpp_transactions', function (Blueprint $table) {
                if ($this->columnExists('ocpp_transactions', 'idempotency_key')) {
                    $table->dropUnique(['idempotency_key']);
                    $table->dropColumn('idempotency_key');
                }
                if ($this->columnExists('ocpp_transactions', 'version')) {
                    $table->dropColumn('version');
                }
            });
        }
    }
};


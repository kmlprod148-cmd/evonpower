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
        if (!$this->columnExists('reservations', 'approved_by')) {
            $driver = DB::getDriverName();
            
            if ($driver === 'sqlite') {
                // SQLite: ajouter la colonne avec ALTER TABLE
                DB::statement("ALTER TABLE reservations ADD COLUMN approved_by INTEGER NULL");
            } else {
                // MySQL/PostgreSQL: utiliser Schema pour les clés étrangères
                Schema::table('reservations', function (Blueprint $table) {
                    $table->foreignId('approved_by')->nullable()->after('confirmed_at')
                        ->constrained('users')->onDelete('set null')
                        ->comment('Utilisateur qui a approuvé manuellement la réservation');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->columnExists('reservations', 'approved_by')) {
            $driver = DB::getDriverName();
            
            if ($driver === 'sqlite') {
                // SQLite: supprimer la colonne directement
                DB::statement("ALTER TABLE reservations DROP COLUMN approved_by");
            } else {
                // MySQL/PostgreSQL: utiliser Schema pour supprimer la clé étrangère
                Schema::table('reservations', function (Blueprint $table) {
                    $table->dropForeign(['approved_by']);
                    $table->dropColumn('approved_by');
                });
            }
        }
    }
};


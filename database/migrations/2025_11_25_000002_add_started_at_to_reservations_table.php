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
        Schema::table('reservations', function (Blueprint $table) {
            if (!$this->columnExists('reservations', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('confirmed_at')
                    ->comment('Date et heure de démarrage effectif de la réservation');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if ($this->columnExists('reservations', 'started_at')) {
                $table->dropColumn('started_at');
            }
        });
    }
};


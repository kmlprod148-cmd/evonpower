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
        Schema::table('users', function (Blueprint $table) {
            if (!$this->columnExists('users', 'credit_limit')) {
                $table->decimal('credit_limit', 10, 2)->default(0)->after('credit_balance')
                    ->comment('Limite de crédit autorisée pour le paiement postpayé');
            }
            if (!$this->columnExists('users', 'current_debt')) {
                $table->decimal('current_debt', 10, 2)->default(0)->after('credit_limit')
                    ->comment('Dette actuelle de l\'utilisateur (somme des charges non payées)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if ($this->columnExists('users', 'credit_limit')) {
                $table->dropColumn('credit_limit');
            }
            if ($this->columnExists('users', 'current_debt')) {
                $table->dropColumn('current_debt');
            }
        });
    }
};


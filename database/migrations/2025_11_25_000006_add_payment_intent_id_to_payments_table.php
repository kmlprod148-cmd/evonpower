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
        Schema::table('payments', function (Blueprint $table) {
            if (!$this->columnExists('payments', 'payment_intent_id')) {
                $table->string('payment_intent_id')->nullable()->after('external_id')
                    ->comment('ID du PaymentIntent Stripe ou référence CMI');
            }
            
            // Index pour les recherches rapides
            if (!$this->indexExists('payments', 'payments_payment_intent_id_index')) {
                $table->index('payment_intent_id');
            }
        });
    }

    /**
     * Check if index exists on table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            // Try SQLite first
            $indexes = DB::select("PRAGMA index_list({$table})");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            // Fallback for MySQL
            try {
                $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = '{$indexName}'");
                return !empty($indexes);
            } catch (\Exception $e2) {
                return false;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if ($this->columnExists('payments', 'payment_intent_id')) {
                if ($this->indexExists('payments', 'payments_payment_intent_id_index')) {
                    $table->dropIndex('payments_payment_intent_id_index');
                }
                $table->dropColumn('payment_intent_id');
            }
        });
    }
};


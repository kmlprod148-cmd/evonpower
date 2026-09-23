<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        try {
            if ($driver === 'sqlite') {
                $indexes = $connection->select("PRAGMA index_list('{$table}')");

                foreach ($indexes as $index) {
                    if (($index->name ?? null) === $indexName) {
                        return true;
                    }
                }

                return false;
            }

            $database = $connection->getDatabaseName();
            $result = $connection->select(
                'SELECT COUNT(*) AS count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [$database, $table, $indexName]
            );

            return (int) ($result[0]->count ?? 0) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        try {
            if ($driver === 'sqlite') {
                $foreignKeys = $connection->select("PRAGMA foreign_key_list('{$table}')");

                foreach ($foreignKeys as $foreignKey) {
                    if (($foreignKey->from ?? null) === $column) {
                        return true;
                    }
                }

                return false;
            }

            $database = $connection->getDatabaseName();
            $result = $connection->select(
                'SELECT COUNT(*) AS count FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                [$database, $table, $column]
            );

            return (int) ($result[0]->count ?? 0) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function up(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'payer_id')) {
                $table->unsignedBigInteger('payer_id')->nullable();
            }

            if (!Schema::hasColumn('transactions', 'beneficiary_id')) {
                $table->unsignedBigInteger('beneficiary_id')->nullable();
            }

            if (!Schema::hasColumn('transactions', 'vat_amount')) {
                $table->decimal('vat_amount', 10, 2)->nullable()->default(0.00);
            }

            if (!Schema::hasColumn('transactions', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->nullable()->default(0.00);
            }

            // Keep existing columns intact when they already exist to avoid data loss
            // and let later dedicated migrations handle type changes safely.
            if (!Schema::hasColumn('transactions', 'payment_method')) {
                $table->enum('payment_method', ['wallet', 'card', 'subscription'])->nullable();
            }

            if (!Schema::hasColumn('transactions', 'transaction_type')) {
                $table->enum('transaction_type', [
                    'charge',
                    'subscription',
                    'wallet_topup',
                    'admin_commission',
                    'partner_commission',
                    'refund',
                ])->nullable();
            }

            if (!Schema::hasColumn('transactions', 'collect_status')) {
                $table->enum('collect_status', ['pending', 'to_collect', 'collected', 'withdrawn'])
                    ->nullable()
                    ->default('pending');
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'payer_id') && !$this->foreignKeyExists('transactions', 'payer_id')) {
                $table->foreign('payer_id')->references('id')->on('users')->onDelete('set null');
            }

            if (Schema::hasColumn('transactions', 'beneficiary_id') && !$this->foreignKeyExists('transactions', 'beneficiary_id')) {
                $table->foreign('beneficiary_id')->references('id')->on('users')->onDelete('set null');
            }

            if (Schema::hasColumn('transactions', 'transaction_type') && !$this->indexExists('transactions', 'idx_transactions_type')) {
                $table->index('transaction_type', 'idx_transactions_type');
            }

            if (Schema::hasColumn('transactions', 'payment_method') && !$this->indexExists('transactions', 'idx_transactions_payment_method')) {
                $table->index('payment_method', 'idx_transactions_payment_method');
            }

            if (Schema::hasColumn('transactions', 'collect_status') && !$this->indexExists('transactions', 'idx_transactions_collect_status')) {
                $table->index('collect_status', 'idx_transactions_collect_status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'payer_id') && $this->foreignKeyExists('transactions', 'payer_id')) {
                $table->dropForeign(['payer_id']);
            }

            if (Schema::hasColumn('transactions', 'beneficiary_id') && $this->foreignKeyExists('transactions', 'beneficiary_id')) {
                $table->dropForeign(['beneficiary_id']);
            }

            if ($this->indexExists('transactions', 'idx_transactions_type')) {
                $table->dropIndex('idx_transactions_type');
            }

            if ($this->indexExists('transactions', 'idx_transactions_payment_method')) {
                $table->dropIndex('idx_transactions_payment_method');
            }

            if ($this->indexExists('transactions', 'idx_transactions_collect_status')) {
                $table->dropIndex('idx_transactions_collect_status');
            }

            $columnsToDrop = [];

            foreach (['payer_id', 'beneficiary_id', 'vat_amount', 'total_amount', 'transaction_type', 'collect_status'] as $column) {
                if (Schema::hasColumn('transactions', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

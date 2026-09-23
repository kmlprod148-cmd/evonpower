<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('charging_sessions')) {
            return;
        }

        Schema::table('charging_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('charging_sessions', 'user_subscription_id')) {
                $table->unsignedBigInteger('user_subscription_id')->nullable();
            }

            if (!Schema::hasColumn('charging_sessions', 'subscription_session_counted')) {
                $table->boolean('subscription_session_counted')->default(false);
            }

            if (!Schema::hasColumn('charging_sessions', 'postpaid_authorized_at')) {
                $table->timestamp('postpaid_authorized_at')->nullable();
            }

            if (!Schema::hasColumn('charging_sessions', 'estimated_cost')) {
                $table->decimal('estimated_cost', 10, 2)->nullable();
            }

            if (!Schema::hasColumn('charging_sessions', 'actual_cost')) {
                $table->decimal('actual_cost', 10, 2)->nullable();
            }

            if (!Schema::hasColumn('charging_sessions', 'actual_energy')) {
                $table->decimal('actual_energy', 8, 2)->nullable();
            }

            if (!Schema::hasColumn('charging_sessions', 'actual_duration')) {
                $table->integer('actual_duration')->nullable();
            }

            if (!Schema::hasColumn('charging_sessions', 'payment_status')) {
                $table->string('payment_status', 50)->nullable()->default('pending');
            }
        });

        Schema::table('charging_sessions', function (Blueprint $table) {
            if (
                Schema::hasColumn('charging_sessions', 'user_subscription_id')
                && Schema::hasTable('user_subscriptions')
                && !$this->foreignKeyExists('charging_sessions', 'user_subscription_id')
            ) {
                $table->foreign('user_subscription_id')
                    ->references('id')
                    ->on('user_subscriptions')
                    ->onDelete('set null');
            }
        });

        if (Schema::hasColumn('charging_sessions', 'payment_status')) {
            $driver = Schema::getConnection()->getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                Schema::getConnection()->statement(
                    "ALTER TABLE charging_sessions MODIFY payment_status VARCHAR(50) NULL DEFAULT 'pending'"
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('charging_sessions')) {
            return;
        }

        Schema::table('charging_sessions', function (Blueprint $table) {
            if (
                Schema::hasColumn('charging_sessions', 'user_subscription_id')
                && $this->foreignKeyExists('charging_sessions', 'user_subscription_id')
            ) {
                $table->dropForeign(['user_subscription_id']);
            }

            $columnsToDrop = [];

            foreach ([
                'postpaid_authorized_at',
                'subscription_session_counted',
                'user_subscription_id',
            ] as $column) {
                if (Schema::hasColumn('charging_sessions', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

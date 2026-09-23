<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Guest Payment Methods Table
 *
 * Stores tokenized payment methods for guest users who want to use postpaid mode.
 */
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
        if (!Schema::hasTable('guest_payment_methods')) {
            Schema::create('guest_payment_methods', function (Blueprint $table) {
                $table->id();
                $table->string('guest_email')->index();
                $table->string('gateway_type');
                $table->string('payment_method_id');
                $table->string('last_four', 4);
                $table->string('brand')->nullable();
                $table->string('expiry_month', 2);
                $table->string('expiry_year', 4);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('reservation_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('reservation_id')
                    ->references('id')
                    ->on('reservations')
                    ->onDelete('set null');

                $table->index(['guest_email', 'is_active']);
                $table->index(['gateway_type', 'is_active']);
            });
        }

        if (Schema::hasTable('charging_sessions')) {
            Schema::table('charging_sessions', function (Blueprint $table) {
                if (!Schema::hasColumn('charging_sessions', 'guest_payment_method_id')) {
                    $table->unsignedBigInteger('guest_payment_method_id')->nullable();
                }
            });

            Schema::table('charging_sessions', function (Blueprint $table) {
                if (
                    Schema::hasColumn('charging_sessions', 'guest_payment_method_id')
                    && Schema::hasTable('guest_payment_methods')
                    && !$this->foreignKeyExists('charging_sessions', 'guest_payment_method_id')
                ) {
                    $table->foreign('guest_payment_method_id')
                        ->references('id')
                        ->on('guest_payment_methods')
                        ->onDelete('set null');
                }
            });
        }

        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                if (!Schema::hasColumn('reservations', 'guest_payment_method_id')) {
                    $table->unsignedBigInteger('guest_payment_method_id')->nullable();
                }

                if (!Schema::hasColumn('reservations', 'postpaid_auth_code')) {
                    $table->string('postpaid_auth_code')->nullable();
                }

                if (!Schema::hasColumn('reservations', 'postpaid_captured_at')) {
                    $table->timestamp('postpaid_captured_at')->nullable();
                }
            });

            Schema::table('reservations', function (Blueprint $table) {
                if (
                    Schema::hasColumn('reservations', 'guest_payment_method_id')
                    && Schema::hasTable('guest_payment_methods')
                    && !$this->foreignKeyExists('reservations', 'guest_payment_method_id')
                ) {
                    $table->foreign('guest_payment_method_id')
                        ->references('id')
                        ->on('guest_payment_methods')
                        ->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                if (
                    Schema::hasColumn('reservations', 'guest_payment_method_id')
                    && $this->foreignKeyExists('reservations', 'guest_payment_method_id')
                ) {
                    $table->dropForeign(['guest_payment_method_id']);
                }

                $columnsToDrop = [];

                foreach (['postpaid_captured_at', 'postpaid_auth_code', 'guest_payment_method_id'] as $column) {
                    if (Schema::hasColumn('reservations', $column)) {
                        $columnsToDrop[] = $column;
                    }
                }

                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }

        if (Schema::hasTable('charging_sessions')) {
            Schema::table('charging_sessions', function (Blueprint $table) {
                if (
                    Schema::hasColumn('charging_sessions', 'guest_payment_method_id')
                    && $this->foreignKeyExists('charging_sessions', 'guest_payment_method_id')
                ) {
                    $table->dropForeign(['guest_payment_method_id']);
                }

                if (Schema::hasColumn('charging_sessions', 'guest_payment_method_id')) {
                    $table->dropColumn('guest_payment_method_id');
                }
            });
        }

        Schema::dropIfExists('guest_payment_methods');
    }
};

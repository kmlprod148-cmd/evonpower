<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix two bugs in the transactions table:
 *
 * 1. payment_method was created as ENUM(['wallet','card','subscription']) by an earlier
 *    migration, which rejects 'stripe' and 'cmi' values written by PaymentService.
 *    → Widens it to VARCHAR(50).
 *
 * 2. failed_at column is missing but ClientChargingPaymentController tries to set it
 *    when a payment confirmation fails.
 *    → Adds it as a nullable timestamp.
 *
 * SQLite does not support ALTER COLUMN — MODIFY is skipped on SQLite (local dev).
 * The failed_at column addition works on both drivers via Schema::table.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Widen payment_method from ENUM to VARCHAR ────────────────────
        if (DB::getDriverName() !== 'sqlite' && Schema::hasColumn('transactions', 'payment_method')) {
            DB::statement('ALTER TABLE transactions MODIFY COLUMN `payment_method` VARCHAR(50) NULL');
        }

        // ── 2. Add failed_at if missing ─────────────────────────────────────
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        // Remove failed_at
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'failed_at')) {
                $table->dropColumn('failed_at');
            }
        });

        // Restore ENUM (MySQL only)
        if (DB::getDriverName() !== 'sqlite' && Schema::hasColumn('transactions', 'payment_method')) {
            DB::statement(
                "ALTER TABLE transactions MODIFY COLUMN `payment_method`
                 ENUM('wallet','card','subscription','stripe','cmi','cash','offline','bank_transfer') NULL"
            );
        }
    }
};

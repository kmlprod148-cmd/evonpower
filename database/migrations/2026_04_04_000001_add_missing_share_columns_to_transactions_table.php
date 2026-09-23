<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Share amount columns (used by ReservationTransactionService)
            if (!Schema::hasColumn('transactions', 'admin_share_amount')) {
                $table->decimal('admin_share_amount', 12, 4)->nullable()->after('admin_commission');
            }
            if (!Schema::hasColumn('transactions', 'integrator_share_amount')) {
                $table->decimal('integrator_share_amount', 12, 4)->nullable()->after('integrator_commission');
            }
            if (!Schema::hasColumn('transactions', 'operator_share_amount')) {
                $table->decimal('operator_share_amount', 12, 4)->nullable()->after('partner_commission');
            }

            // Actor ID columns
            if (!Schema::hasColumn('transactions', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('transactions', 'operator_id')) {
                $table->unsignedBigInteger('operator_id')->nullable();
            }

            // Wallet ID columns
            if (!Schema::hasColumn('transactions', 'admin_wallet_id')) {
                $table->unsignedBigInteger('admin_wallet_id')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'integrator_wallet_id')) {
                $table->unsignedBigInteger('integrator_wallet_id')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'operator_wallet_id')) {
                $table->unsignedBigInteger('operator_wallet_id')->nullable();
            }

            // Status tracking columns
            if (!Schema::hasColumn('transactions', 'processed_at')) {
                $table->timestamp('processed_at')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'failure_reason')) {
                $table->text('failure_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        $columns = [
            'admin_share_amount',
            'integrator_share_amount',
            'operator_share_amount',
            'admin_id',
            'operator_id',
            'admin_wallet_id',
            'integrator_wallet_id',
            'operator_wallet_id',
            'processed_at',
            'failure_reason',
        ];

        Schema::table('transactions', function (Blueprint $table) use ($columns) {
            $toDrop = array_filter($columns, fn($c) => Schema::hasColumn('transactions', $c));
            if (!empty($toDrop)) {
                $table->dropColumn(array_values($toDrop));
            }
        });
    }
};

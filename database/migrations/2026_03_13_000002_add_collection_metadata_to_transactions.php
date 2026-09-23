<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'collect_user_type')) {
                $table->string('collect_user_type', 100)
                    ->nullable()
                    ->after('transaction_category');
            }

            if (!Schema::hasColumn('transactions', 'collect_user_id')) {
                $table->unsignedBigInteger('collect_user_id')
                    ->nullable()
                    ->after('collect_user_type');
            }

            if (!Schema::hasColumn('transactions', 'collect_status')) {
                $table->string('collect_status', 50)
                    ->nullable()
                    ->after('collect_user_id');
            }

            if (!Schema::hasColumn('transactions', 'collect_date')) {
                $table->timestamp('collect_date')
                    ->nullable()
                    ->after('collect_status');
            }

            if (!Schema::hasColumn('transactions', 'collect_reference')) {
                $table->string('collect_reference', 191)
                    ->nullable()
                    ->after('collect_date');
            }

            if (!Schema::hasColumn('transactions', 'is_collectable')) {
                $table->boolean('is_collectable')
                    ->default(true)
                    ->after('collect_reference');
            }

            $table->index(['collect_user_type', 'collect_user_id', 'collect_status'], 'transactions_collect_user_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'is_collectable')) {
                $table->dropColumn('is_collectable');
            }
            if (Schema::hasColumn('transactions', 'collect_reference')) {
                $table->dropColumn('collect_reference');
            }
            if (Schema::hasColumn('transactions', 'collect_date')) {
                $table->dropColumn('collect_date');
            }
            if (Schema::hasColumn('transactions', 'collect_status')) {
                $table->dropColumn('collect_status');
            }
            if (Schema::hasColumn('transactions', 'collect_user_id')) {
                $table->dropColumn('collect_user_id');
            }
            if (Schema::hasColumn('transactions', 'collect_user_type')) {
                $table->dropColumn('collect_user_type');
            }

            $table->dropIndex('transactions_collect_user_status_index');
        });
    }
};


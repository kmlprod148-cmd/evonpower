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
        if (Schema::hasColumn('charging_points', 'status')) {
            Schema::table('charging_points', function (Blueprint $table) {
                if (!Schema::hasIndex('charging_points', 'idx_charging_points_status')) {
                    $table->index('status', 'idx_charging_points_status');
                }
            });
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'user_id')) {
                if (!Schema::hasIndex('transactions', 'idx_transactions_user_id')) {
                    $table->index('user_id', 'idx_transactions_user_id');
                }
            }
            if (Schema::hasColumn('transactions', 'charging_point_id')) {
                if (!Schema::hasIndex('transactions', 'idx_transactions_charging_point_id')) {
                    $table->index('charging_point_id', 'idx_transactions_charging_point_id');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('charging_points', 'status')) {
            Schema::table('charging_points', function (Blueprint $table) {
                $table->dropIndex('idx_charging_points_status');
            });
        }

        Schema::table('transactions', function (Blueprint $table) {
            // Then drop the indexes
            if (Schema::hasColumn('transactions', 'user_id')) {
                $table->dropIndex('idx_transactions_user_id');
            }
            if (Schema::hasColumn('transactions', 'charging_point_id')) {
                $table->dropIndex('idx_transactions_charging_point_id');
            }
        });
    }
};

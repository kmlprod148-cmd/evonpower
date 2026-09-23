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
        // Only run if the table exists (it will be created later in a migration with a later timestamp)
        if (!Schema::hasTable('charging_sessions')) {
            return;
        }

        Schema::table('charging_sessions', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('charging_sessions', 'mode')) {
                $table->enum('mode', ['prepaid', 'postpaid'])->default('postpaid')->after('user_id');
            }
            if (!Schema::hasColumn('charging_sessions', 'estimated_cost')) {
                $table->decimal('estimated_cost', 10, 2)->nullable()->after('cost')->comment('Estimated cost for prepaid sessions');
            }
            if (!Schema::hasColumn('charging_sessions', 'prepaid_amount')) {
                $table->decimal('prepaid_amount', 10, 2)->nullable()->after('estimated_cost')->comment('Amount charged for prepaid sessions');
            }
            if (!Schema::hasColumn('charging_sessions', 'refund_amount')) {
                $table->decimal('refund_amount', 10, 2)->nullable()->after('prepaid_amount')->comment('Amount refunded for prepaid sessions');
            }
            if (!Schema::hasColumn('charging_sessions', 'min_threshold')) {
                $table->decimal('min_threshold', 10, 2)->nullable()->after('refund_amount')->comment('Minimum threshold for postpaid sessions');
            }
            if (!Schema::hasColumn('charging_sessions', 'wallet_validation_passed')) {
                $table->boolean('wallet_validation_passed')->default(false)->after('min_threshold')->comment('Whether wallet validation passed');
            }
            if (!Schema::hasColumn('charging_sessions', 'wallet_validated_at')) {
                $table->timestamp('wallet_validated_at')->nullable()->after('wallet_validation_passed')->comment('When wallet validation was performed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only run if the table exists
        if (!Schema::hasTable('charging_sessions')) {
            return;
        }

        Schema::table('charging_sessions', function (Blueprint $table) {
            $columnsToDrop = [];
            
            if (Schema::hasColumn('charging_sessions', 'mode')) {
                $columnsToDrop[] = 'mode';
            }
            if (Schema::hasColumn('charging_sessions', 'estimated_cost')) {
                $columnsToDrop[] = 'estimated_cost';
            }
            if (Schema::hasColumn('charging_sessions', 'prepaid_amount')) {
                $columnsToDrop[] = 'prepaid_amount';
            }
            if (Schema::hasColumn('charging_sessions', 'refund_amount')) {
                $columnsToDrop[] = 'refund_amount';
            }
            if (Schema::hasColumn('charging_sessions', 'min_threshold')) {
                $columnsToDrop[] = 'min_threshold';
            }
            if (Schema::hasColumn('charging_sessions', 'wallet_validation_passed')) {
                $columnsToDrop[] = 'wallet_validation_passed';
            }
            if (Schema::hasColumn('charging_sessions', 'wallet_validated_at')) {
                $columnsToDrop[] = 'wallet_validated_at';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

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
            if (!Schema::hasColumn('charging_sessions', 'hierarchical_transaction_processed')) {
                $table->boolean('hierarchical_transaction_processed')->default(false)->after('status');
            }
            if (!Schema::hasColumn('charging_sessions', 'hierarchical_transaction_processed_at')) {
                $table->timestamp('hierarchical_transaction_processed_at')->nullable()->after('hierarchical_transaction_processed');
            }
            if (!Schema::hasColumn('charging_sessions', 'hierarchical_transaction_data')) {
                $table->json('hierarchical_transaction_data')->nullable()->after('hierarchical_transaction_processed_at');
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
            
            if (Schema::hasColumn('charging_sessions', 'hierarchical_transaction_processed')) {
                $columnsToDrop[] = 'hierarchical_transaction_processed';
            }
            if (Schema::hasColumn('charging_sessions', 'hierarchical_transaction_processed_at')) {
                $columnsToDrop[] = 'hierarchical_transaction_processed_at';
            }
            if (Schema::hasColumn('charging_sessions', 'hierarchical_transaction_data')) {
                $columnsToDrop[] = 'hierarchical_transaction_data';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

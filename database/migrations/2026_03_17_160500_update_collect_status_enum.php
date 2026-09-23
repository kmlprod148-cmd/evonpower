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

        // Update collect_status to be enum with specific values
        if (Schema::hasColumn('transactions', 'collect_status')) {
            // Drop all known indexes first – SQLite cannot drop a column while an index references it
            foreach ([
                'transactions_collect_user_status_index',
                'idx_transactions_collect_status',
                'transactions_collect_status_index',
            ] as $idx) {
                try {
                    \Illuminate\Support\Facades\DB::statement("DROP INDEX IF EXISTS {$idx}");
                } catch (\Exception $e) {
                    // Index may not exist or DB doesn't support the syntax – continue
                }
            }

            Schema::table('transactions', function (Blueprint $table) {
                if (Schema::hasColumn('transactions', 'collect_status')) {
                    $table->dropColumn('collect_status');
                }
            });

            Schema::table('transactions', function (Blueprint $table) {
                $table->enum('collect_status', ['pending', 'to_collect', 'collected', 'withdrawn'])
                    ->nullable()
                    ->default('pending')
                    ->after('collect_user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        // Revert collect_status back to string
        if (Schema::hasColumn('transactions', 'collect_status')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('collect_status');
                $table->string('collect_status', 50)
                    ->nullable()
                    ->after('collect_user_id');
            });
        }
    }
};
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

        // Update collect_status to be enum with specific values (idempotent – 160500 may have run it already)
        if (Schema::hasColumn('transactions', 'collect_status')) {
            try {
                \Illuminate\Support\Facades\DB::statement('DROP INDEX IF EXISTS transactions_collect_user_status_index');
            } catch (\Exception $e) {
                // Index may not exist – continue
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
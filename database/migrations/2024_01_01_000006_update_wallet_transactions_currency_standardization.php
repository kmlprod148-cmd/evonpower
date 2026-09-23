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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Update amount column to use consistent decimal(10,2)
            if (Schema::hasColumn('wallet_transactions', 'amount')) {
                $table->decimal('amount', 10, 2)->change();
            }
            
            // Update current_balance column to use consistent decimal(10,2) if it exists
            if (Schema::hasColumn('wallet_transactions', 'current_balance')) {
                $table->decimal('current_balance', 10, 2)->change();
            }
            
            // Add currency column if it doesn't exist
            if (!Schema::hasColumn('wallet_transactions', 'currency')) {
                $table->string('currency', 3)->default('EUR')->after('amount');
            }
            
            // Add additional fields for better transaction tracking
            if (!Schema::hasColumn('wallet_transactions', 'reference')) {
                $table->string('reference')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('wallet_transactions', 'external_id')) {
                $table->string('external_id')->nullable()->after('reference');
            }
            if (!Schema::hasColumn('wallet_transactions', 'status')) {
                $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed')->after('external_id');
            }
            if (!Schema::hasColumn('wallet_transactions', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Revert amount column
            $table->decimal('amount', 15, 2)->change();
            
            // Revert current_balance column
            $table->decimal('current_balance', 15, 2)->change();
            
            // Remove added columns
            $table->dropColumn([
                'currency',
                'reference',
                'external_id',
                'status',
                'processed_at'
            ]);
        });
    }
};

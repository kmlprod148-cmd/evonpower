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
        Schema::table('wallets', function (Blueprint $table) {
            // Update balance column to use consistent decimal(10,2)
            $table->decimal('balance', 10, 2)->default(0.00)->change();
            
            // Add currency column if it doesn't exist
            if (!Schema::hasColumn('wallets', 'currency')) {
                $table->string('currency', 3)->default('EUR')->after('balance');
            }
            
            // Add additional wallet fields for better money management
            if (!Schema::hasColumn('wallets', 'min_balance')) {
                $table->decimal('min_balance', 10, 2)->nullable()->after('currency');
            }
            if (!Schema::hasColumn('wallets', 'max_balance')) {
                $table->decimal('max_balance', 10, 2)->nullable()->after('min_balance');
            }
            if (!Schema::hasColumn('wallets', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('max_balance');
            }
            if (!Schema::hasColumn('wallets', 'name')) {
                $table->string('name')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('wallets', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Revert balance column
            $table->decimal('balance', 15, 2)->default(0.00)->change();
            
            // Remove added columns
            $table->dropColumn([
                'currency',
                'min_balance',
                'max_balance',
                'is_active',
                'name',
                'description'
            ]);
        });
    }
};

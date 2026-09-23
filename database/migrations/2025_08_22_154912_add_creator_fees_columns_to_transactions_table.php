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
        Schema::table('transactions', function (Blueprint $table) {
            // Colonnes pour les frais du créateur basés sur le business profile
            if (!Schema::hasColumn('transactions', 'creator_charging_fees')) {
                $table->decimal('creator_charging_fees', 12, 4)->default(0)->after('business_profile_fee_breakdown');
            }
            
            if (!Schema::hasColumn('transactions', 'creator_transaction_fees')) {
                $table->decimal('creator_transaction_fees', 12, 4)->default(0)->after('creator_charging_fees');
            }
            
            if (!Schema::hasColumn('transactions', 'creator_activation_fees')) {
                $table->decimal('creator_activation_fees', 12, 4)->default(0)->after('creator_transaction_fees');
            }
            
            if (!Schema::hasColumn('transactions', 'creator_admin_fees')) {
                $table->decimal('creator_admin_fees', 12, 4)->default(0)->after('creator_activation_fees');
            }
            
            if (!Schema::hasColumn('transactions', 'creator_fees_total')) {
                $table->decimal('creator_fees_total', 12, 4)->default(0)->after('creator_admin_fees');
            }
            
            if (!Schema::hasColumn('transactions', 'creator_fees_applied_at')) {
                $table->timestamp('creator_fees_applied_at')->nullable()->after('creator_fees_total');
            }
            
            if (!Schema::hasColumn('transactions', 'creator_fees_source')) {
                $table->string('creator_fees_source')->nullable()->after('creator_fees_applied_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'creator_charging_fees',
                'creator_transaction_fees',
                'creator_activation_fees',
                'creator_admin_fees',
                'creator_fees_total',
                'creator_fees_applied_at',
                'creator_fees_source'
            ]);
        });
    }
};

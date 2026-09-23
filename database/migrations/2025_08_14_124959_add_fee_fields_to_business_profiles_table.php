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
        Schema::table('business_profiles', function (Blueprint $table) {
            // Integrator fees
            if (!Schema::hasColumn('business_profiles', 'integrator_fee_fixed')) {
                $table->decimal('integrator_fee_fixed', 10, 2)->default(0)->after('integrator_commission');
            }
            if (!Schema::hasColumn('business_profiles', 'integrator_fee_percentage')) {
                $table->decimal('integrator_fee_percentage', 5, 2)->default(0)->after('integrator_fee_fixed');
            }
            
            // Partner fees
            if (!Schema::hasColumn('business_profiles', 'partner_fee_fixed')) {
                $table->decimal('partner_fee_fixed', 10, 2)->default(0)->after('partner_commission');
            }
            if (!Schema::hasColumn('business_profiles', 'partner_fee_percentage')) {
                $table->decimal('partner_fee_percentage', 5, 2)->default(0)->after('partner_fee_fixed');
            }
            
            // Admin fees (for consistency) - add after partner_commission since admin_commission doesn't exist
            if (!Schema::hasColumn('business_profiles', 'admin_fee_fixed')) {
                $table->decimal('admin_fee_fixed', 10, 2)->default(0)->after('partner_fee_percentage');
            }
            if (!Schema::hasColumn('business_profiles', 'admin_fee_percentage')) {
                $table->decimal('admin_fee_percentage', 5, 2)->default(0)->after('admin_fee_fixed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'integrator_fee_fixed',
                'integrator_fee_percentage',
                'partner_fee_fixed',
                'partner_fee_percentage',
                'admin_fee_fixed',
                'admin_fee_percentage'
            ]);
        });
    }
};

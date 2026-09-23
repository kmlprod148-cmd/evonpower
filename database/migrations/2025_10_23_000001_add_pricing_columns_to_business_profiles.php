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
            // Add pricing columns for hierarchical transaction calculation
            if (!Schema::hasColumn('business_profiles', 'price_per_kwh')) {
                $table->decimal('price_per_kwh', 10, 4)->nullable()->default(0.00)->after('base_fee_amount');
            }

            if (!Schema::hasColumn('business_profiles', 'price_per_hour')) {
                $table->decimal('price_per_hour', 10, 2)->nullable()->default(0.00)->after('price_per_kwh');
            }

            if (!Schema::hasColumn('business_profiles', 'fixed_price')) {
                $table->decimal('fixed_price', 10, 2)->nullable()->default(0.00)->after('price_per_hour');
            }

            // Add created_by_role if not exists (for identifying creator role)
            if (!Schema::hasColumn('business_profiles', 'created_by_role')) {
                $table->string('created_by_role')->nullable()->after('created_by_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('business_profiles', 'price_per_kwh')) {
                $table->dropColumn('price_per_kwh');
            }

            if (Schema::hasColumn('business_profiles', 'price_per_hour')) {
                $table->dropColumn('price_per_hour');
            }

            if (Schema::hasColumn('business_profiles', 'fixed_price')) {
                $table->dropColumn('fixed_price');
            }

            if (Schema::hasColumn('business_profiles', 'created_by_role')) {
                $table->dropColumn('created_by_role');
            }
        });
    }
};


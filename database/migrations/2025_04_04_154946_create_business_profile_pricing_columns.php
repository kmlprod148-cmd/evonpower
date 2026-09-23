<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessProfilePricingColumns extends Migration
{
    public function up()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            // Add columns safely
            if (!Schema::hasColumn('business_profiles', 'subscription_period')) {
                $table->string('subscription_period')->nullable();
            }

            if (!Schema::hasColumn('business_profiles', 'base_fee_amount')) {
                $table->decimal('base_fee_amount', 10, 2)->default(0)->nullable();
            }

            if (!Schema::hasColumn('business_profiles', 'terminal_fee_amount')) {
                $table->decimal('terminal_fee_amount', 10, 2)->default(0)->nullable();
            }

            if (!Schema::hasColumn('business_profiles', 'transaction_fee_type')) {
                $table->string('transaction_fee_type')->nullable();
            }

            if (!Schema::hasColumn('business_profiles', 'transaction_fee_amount')) {
                $table->decimal('transaction_fee_amount', 10, 2)->default(0)->nullable();
            }

            if (!Schema::hasColumn('business_profiles', 'charge_fee_amount')) {
                $table->decimal('charge_fee_amount', 10, 2)->default(0)->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('business_profiles', 'subscription_period')) {
                $table->dropColumn('subscription_period');
            }
            if (Schema::hasColumn('business_profiles', 'base_fee_amount')) {
                $table->dropColumn('base_fee_amount');
            }
            if (Schema::hasColumn('business_profiles', 'terminal_fee_amount')) {
                $table->dropColumn('terminal_fee_amount');
            }
            if (Schema::hasColumn('business_profiles', 'transaction_fee_type')) {
                $table->dropColumn('transaction_fee_type');
            }
            if (Schema::hasColumn('business_profiles', 'transaction_fee_amount')) {
                $table->dropColumn('transaction_fee_amount');
            }
            if (Schema::hasColumn('business_profiles', 'charge_fee_amount')) {
                $table->dropColumn('charge_fee_amount');
            }
        });
    }
}
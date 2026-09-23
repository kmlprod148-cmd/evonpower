<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('pricing_plans', 'fixed_price')) {
                $table->decimal('fixed_price', 12, 2)->nullable()->after('activation_fee');
            }
            if (!Schema::hasColumn('pricing_plans', 'valid_from')) {
                $table->dateTime('valid_from')->nullable()->after('fixed_price');
            }
            if (!Schema::hasColumn('pricing_plans', 'valid_until')) {
                $table->dateTime('valid_until')->nullable()->after('valid_from');
            }
            if (!Schema::hasColumn('pricing_plans', 'mobile_theme_color')) {
                $table->string('mobile_theme_color', 7)->nullable()->after('valid_until');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            if (Schema::hasColumn('pricing_plans', 'mobile_theme_color')) {
                $table->dropColumn('mobile_theme_color');
            }
            if (Schema::hasColumn('pricing_plans', 'valid_until')) {
                $table->dropColumn('valid_until');
            }
            if (Schema::hasColumn('pricing_plans', 'valid_from')) {
                $table->dropColumn('valid_from');
            }
            if (Schema::hasColumn('pricing_plans', 'fixed_price')) {
                $table->dropColumn('fixed_price');
            }
        });
    }
};



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
        Schema::table('tariff_plans', function (Blueprint $table) {
            if (Schema::hasColumn('tariff_plans', 'vat_rate_id')) {
                $table->index('vat_rate_id');
            }
            if (Schema::hasColumn('tariff_plans', 'priority')) {
                $table->index('priority');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tariff_plans', function (Blueprint $table) {
            if (Schema::hasColumn('tariff_plans', 'vat_rate_id')) {
                $table->dropIndex(['vat_rate_id']);
            }
            if (Schema::hasColumn('tariff_plans', 'priority')) {
                $table->dropIndex(['priority']);
            }
        });
    }
};

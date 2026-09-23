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
        Schema::table('pricing_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('pricing_plans', 'price_per_kwh')) {
                $table->decimal('price_per_kwh', 10, 2)->default(0)->after('rate_type');
            }
            if (!Schema::hasColumn('pricing_plans', 'price_per_minute')) {
                $table->decimal('price_per_minute', 10, 2)->default(0)->after('price_per_kwh');
            }
            if (!Schema::hasColumn('pricing_plans', 'vat_rate_id')) {
                $table->unsignedBigInteger('vat_rate_id')->nullable()->after('price_per_minute');
            }
            if (!Schema::hasColumn('pricing_plans', 'priority')) {
                $table->integer('priority')->default(0)->after('vat_rate_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            if (Schema::hasColumn('pricing_plans', 'price_per_minute')) {
                $table->dropColumn('price_per_minute');
            }
            if (Schema::hasColumn('pricing_plans', 'price_per_kwh')) {
                 $table->dropColumn('price_per_kwh');
            }
            if (Schema::hasColumn('pricing_plans', 'vat_rate_id')) {
                // Drop foreign key first if it exists
                $foreignKeys = DB::select(DB::raw("SHOW KEYS FROM pricing_plans WHERE Key_name = 'pricing_plans_vat_rate_id_foreign'"));
                if (!empty($foreignKeys)) {
                    $table->dropForeign(['vat_rate_id']);
                }
                $table->dropColumn('vat_rate_id');
            }
            if (Schema::hasColumn('pricing_plans', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};

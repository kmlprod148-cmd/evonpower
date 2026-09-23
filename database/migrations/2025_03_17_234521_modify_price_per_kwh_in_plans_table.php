<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            // Add price_per_kwh if not exists
            if (!Schema::hasColumn('pricing_plans', 'price_per_kwh')) {
                $table->decimal('price_per_kwh', 8, 4)->after('base_rate');
            }
            
            // Modify existing column if needed
            $table->decimal('price_per_kwh', 8, 4)->change();
        });
    }

    public function down()
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->decimal('price_per_kwh', 6, 4)->change();
        });
    }
};
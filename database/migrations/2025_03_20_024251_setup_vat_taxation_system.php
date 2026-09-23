<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SetupVatTaxationSystem extends Migration
{
    public function up()
    {
        // Create vat_rates table if not exists
        if (!Schema::hasTable('vat_rates')) {
            Schema::create('vat_rates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('rate', 5, 2);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Add vat column to pricing_plans
        Schema::table('pricing_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('pricing_plans', 'vat')) {
                $table->decimal('vat', 5, 2)->default(0)->after('price_per_kwh');
            }
        });

        // Insert default VAT rates
        DB::table('vat_rates')->insertOrIgnore([
            [
                'name' => 'Sans TVA',
                'rate' => 0.00,
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'TVA standard',
                'rate' => 20.00,
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('vat_rates');
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropColumn('vat');
        });
    }
}
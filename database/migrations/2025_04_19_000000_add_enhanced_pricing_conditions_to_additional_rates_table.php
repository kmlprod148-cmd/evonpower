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
        Schema::table('additional_rates', function (Blueprint $table) {
            // Duration-based conditions
            $table->integer('min_duration')->nullable()->after('priority')->comment('Minimum duration in minutes');
            $table->integer('max_duration')->nullable()->after('min_duration')->comment('Maximum duration in minutes');

            // Power-based conditions
            $table->decimal('min_power', 8, 2)->nullable()->after('max_duration')->comment('Minimum power in kW');
            $table->decimal('max_power', 8, 2)->nullable()->after('min_power')->comment('Maximum power in kW');

            // Customer segment targeting
            $table->string('customer_segment', 50)->nullable()->after('max_power')->comment('Customer segment: new, returning, vip');

            // Location/zone-based pricing
            $table->string('location_zone', 100)->nullable()->after('customer_segment')->comment('Location zone identifier');

            // Quantity/volume-based discounts
            $table->integer('quantity_min')->nullable()->after('location_zone')->comment('Minimum quantity for rate to apply');
            $table->integer('quantity_max')->nullable()->after('quantity_min')->comment('Maximum quantity for rate to apply');

            // Percentage-based rate adjustments (discounts/surcharges)
            $table->boolean('is_percentage')->default(false)->after('quantity_max')->comment('If true, price is a percentage adjustment');
            $table->decimal('percentage_value', 6, 2)->nullable()->after('is_percentage')->comment('Percentage value (0-100) for adjustments');

            // Rate application mode: 'multiply' (factor), 'add' (fixed add), 'replace' (replace base)
            $table->string('apply_type', 20)->default('add')->after('percentage_value')->comment('How to apply: add, multiply, replace');

            // For holiday/special date conditions
            $table->json('applicable_dates')->nullable()->after('apply_type')->comment('Specific dates when rate applies (YYYY-MM-DD)');
            $table->json('excluded_dates')->nullable()->after('applicable_dates')->comment('Specific dates to exclude');

            // Indexes for better query performance
            $table->index(['is_active', 'priority']);
            $table->index(['condition_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('additional_rates', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'priority']);
            $table->dropIndex(['condition_type', 'is_active']);
            $table->dropColumn([
                'min_duration',
                'max_duration',
                'min_power',
                'max_power',
                'customer_segment',
                'location_zone',
                'quantity_min',
                'quantity_max',
                'is_percentage',
                'percentage_value',
                'apply_type',
                'applicable_dates',
                'excluded_dates',
            ]);
        });
    }
};

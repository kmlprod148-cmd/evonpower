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
        Schema::dropIfExists('pricing_plans'); // Ensure table is dropped if it exists

        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('rate_type')->default('fixed'); // 'fixed', 'time', 'energy'
            $table->decimal('base_rate', 8, 4)->nullable();
            $table->decimal('price_per_kwh', 8, 4)->nullable();
            $table->decimal('price_per_minute', 8, 4)->nullable();
            $table->decimal('activation_fee', 8, 2)->default(0.00);
            $table->unsignedBigInteger('vat_rate_id')->nullable();
            $table->integer('priority')->default(0);
            $table->integer('max_duration')->nullable(); // in minutes
            $table->decimal('max_energy', 8, 4)->nullable(); // in kWh
            $table->integer('min_charge_duration')->nullable(); // in minutes
            $table->boolean('is_active')->default(true);
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_plans');
    }
};

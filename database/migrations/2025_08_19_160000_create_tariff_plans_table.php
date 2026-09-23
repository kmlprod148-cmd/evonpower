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
        if (!Schema::hasTable('tariff_plans')) {
            Schema::create('tariff_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price_per_minute', 8, 4)->default(0);
                $table->decimal('price_per_kwh', 8, 4)->default(0);
                $table->decimal('base_rate', 8, 4)->default(0);
                $table->decimal('activation_fee', 8, 2)->default(0);
                $table->integer('max_minutes_per_reservation')->default(120);
                $table->integer('priority')->default(0);
                $table->boolean('is_active')->default(true);
                $table->string('currency', 3)->default('EUR');
                $table->foreignId('vat_rate_id')->nullable()->constrained()->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tariff_plans');
    }
};

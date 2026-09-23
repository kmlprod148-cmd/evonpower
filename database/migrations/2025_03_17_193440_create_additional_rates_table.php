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
        Schema::create('additional_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pricing_plan_id');
            $table->string('name');
            $table->string('rate_type')->default('fixed'); // 'fixed', 'time', 'energy'
            $table->decimal('price', 8, 4)->default(0.00);
            $table->unsignedBigInteger('vat_rate_id')->nullable();
            $table->string('condition_type')->default('all'); // e.g., 'all', 'time', 'day', 'power', 'duration'
            $table->string('time_start')->nullable(); // HH:MM
            $table->string('time_end')->nullable();   // HH:MM
            $table->json('days')->nullable(); // JSON array of days, e.g., [1,2,3] for Mon, Tue, Wed
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('custom_condition')->nullable(); // For more complex conditions
            $table->timestamps();

            $table->foreign('pricing_plan_id')->references('id')->on('pricing_plans')->onDelete('cascade');
            $table->foreign('vat_rate_id')->references('id')->on('vat_rates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('additional_rates', function (Blueprint $table) {
            $table->dropForeign(['pricing_plan_id']);
            $table->dropForeign(['vat_rate_id']);
        });
        Schema::dropIfExists('additional_rates');
    }
};
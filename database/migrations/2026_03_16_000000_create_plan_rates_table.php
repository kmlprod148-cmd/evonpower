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
        Schema::create('plan_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id');
            $table->string('name');
            $table->string('rate_type'); // 'minute', 'kwh', 'fixed', etc.
            $table->decimal('price', 10, 4)->default(0);
            $table->unsignedBigInteger('vat_rate_id')->nullable();
            $table->string('condition_type')->nullable(); // 'time', 'day', 'vehicle_type', etc.
            $table->string('time_start')->nullable();
            $table->string('time_end')->nullable();
            $table->string('days')->nullable(); // JSON array of days: ['monday', 'tuesday', etc.]
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->text('description')->nullable();
            $table->text('custom_condition')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('plan_id')
                ->references('id')
                ->on('pricing_plans')
                ->onDelete('cascade');

            $table->foreign('vat_rate_id')
                ->references('id')
                ->on('vat_rates')
                ->onDelete('set null');

            // Indexes
            $table->index('plan_id');
            $table->index('is_active');
            $table->index('rate_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_rates');
    }
};

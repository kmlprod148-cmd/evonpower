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
        Schema::create('partner_pricing_plan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('pricing_plan_id');
            $table->timestamps();

            $table->foreign('partner_id')
                  ->references('id')
                  ->on('partners')
                  ->onDelete('cascade');

            $table->foreign('pricing_plan_id')
                  ->references('id')
                  ->on('pricing_plans')
                  ->onDelete('cascade');

            // Unique constraint to prevent duplicate associations
            $table->unique(['partner_id', 'pricing_plan_id'], 'partner_pricing_plan_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_pricing_plan');
    }
};

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
        Schema::create('business_profile_pricing_plan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_profile_id');
            $table->unsignedBigInteger('pricing_plan_id');
            $table->timestamps();

            $table->foreign('business_profile_id')
                  ->references('id')
                  ->on('business_profiles')
                  ->onDelete('cascade');

            $table->foreign('pricing_plan_id')
                  ->references('id')
                  ->on('pricing_plans')
                  ->onDelete('cascade');

            // Use a shorter name for the unique index
            $table->unique(['business_profile_id', 'pricing_plan_id'], 'bp_pp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_profile_pricing_plan');
    }
};
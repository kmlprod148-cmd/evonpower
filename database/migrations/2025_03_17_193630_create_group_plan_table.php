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
        Schema::dropIfExists('group_plan'); // Ensure table is dropped if it exists

        Schema::create('group_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('plan_id');
            $table->timestamps();
            
            // Optional: Add a unique constraint to prevent duplicate associations
            $table->unique(['group_id', 'plan_id']);

            // Explicitly define the foreign key to the pricing_plans table
            $table->foreign('plan_id')->references('id')->on('pricing_plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_plan');
    }
};
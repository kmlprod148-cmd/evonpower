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
        Schema::create('pricing_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pricing_plan_id');
            $table->string('name');
            $table->string('condition_type')->comment('Type of condition: and, or, single');
            $table->json('conditions')->comment('JSON array of condition objects');
            $table->string('rate_type')->default('fixed'); // fixed, percentage, time, energy
            $table->decimal('price_value', 10, 4)->comment('Price adjustment value');
            $table->boolean('is_percentage')->default(false);
            $table->string('apply_type')->default('add'); // add, multiply, replace
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('pricing_plan_id')
                ->references('id')
                ->on('pricing_plans')
                ->onDelete('cascade');

            $table->index(['pricing_plan_id', 'is_active', 'priority']);
            $table->index('condition_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_rule_conditions', function (Blueprint $table) {
            $table->dropForeign(['pricing_plan_id']);
        });
        Schema::dropIfExists('pricing_rule_conditions');
    }
};

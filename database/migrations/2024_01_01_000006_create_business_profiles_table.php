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
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('operator_commission', 5, 2);
            $table->decimal('integrator_commission', 5, 2);
            $table->decimal('owner_commission', 5, 2);
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('integrator_id')->constrained('integrators')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
}; 
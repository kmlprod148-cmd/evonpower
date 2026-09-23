<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('partner_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            
            // Conditional foreign key for integrator relationship
            if (Schema::hasTable('integrators')) {
                $table->foreignId('integrator_id')
                      ->nullable()
                      ->constrained()
                      ->onDelete('set null');
            }
            
            $table->decimal('commission_rate', 5, 2)->default(15.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_profiles');
    }
};

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
        Schema::create('enhanced_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'integrator', 'operator'])->default('operator');
            $table->foreignId('business_profile_id')->nullable()->constrained('business_profiles')->nullOnDelete();
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->string('currency', 3)->default('EUR');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes
            $table->index(['role', 'is_active']);
            $table->index('business_profile_id');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enhanced_users');
    }
};

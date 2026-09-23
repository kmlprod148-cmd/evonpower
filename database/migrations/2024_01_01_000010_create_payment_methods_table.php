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
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // CMI, Stripe, etc.
            $table->string('slug')->unique(); // cmi, stripe, etc.
            $table->string('provider'); // CMI, Stripe, etc.
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable(); // Configuration spécifique
            $table->string('logo_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};

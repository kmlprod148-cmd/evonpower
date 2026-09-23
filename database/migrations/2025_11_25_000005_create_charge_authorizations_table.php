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
        Schema::create('charge_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reference_type'); // Reservation, OcppTransaction, etc.
            $table->unsignedBigInteger('reference_id');
            $table->decimal('authorized_amount', 10, 2);
            $table->decimal('estimated_amount', 10, 2);
            $table->decimal('actual_amount', 10, 2)->nullable();
            $table->enum('status', ['PENDING', 'AUTHORIZED', 'CAPTURED', 'RELEASED', 'FAILED'])->default('PENDING');
            $table->string('currency', 3)->default('EUR');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('status');
            $table->index('authorized_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charge_authorizations');
    }
};


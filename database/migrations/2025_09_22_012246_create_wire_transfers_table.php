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
        Schema::create('wire_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_reference')->unique();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('transfer_type', [
                'admin_to_integrator',
                'integrator_to_operator', 
                'operator_to_client',
                'admin_to_operator',
                'integrator_to_client',
                'manual'
            ]);
            $table->enum('status', [
                'pending',
                'processing', 
                'completed',
                'rejected',
                'cancelled'
            ])->default('pending');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Index pour les performances
            $table->index(['sender_id', 'status']);
            $table->index(['recipient_id', 'status']);
            $table->index(['transfer_type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wire_transfers');
    }
};

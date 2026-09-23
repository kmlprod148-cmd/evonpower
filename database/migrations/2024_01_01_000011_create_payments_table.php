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
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Clé étrangère vers reservations - La contrainte sera ajoutée plus tard après création de la table reservations
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique(); // ID unique de la transaction
            $table->string('external_id')->nullable(); // ID externe (CMI, Stripe, etc.)
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->json('payment_data')->nullable(); // Données spécifiques au fournisseur
            $table->json('webhook_data')->nullable(); // Données des webhooks
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index(['reservation_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

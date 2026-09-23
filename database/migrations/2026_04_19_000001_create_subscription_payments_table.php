<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table qui trace chaque tentative de paiement liée à un abonnement.
     * Permet l'audit complet Stripe / CMI / Wallet / Offline.
     */
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_subscription_id')
                ->constrained('user_subscriptions')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Montants
            $table->decimal('amount', 10, 2);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');

            // Méthode de paiement : stripe, cmi, wallet, offline
            $table->string('payment_method', 20);

            // Statut : pending | completed | failed | cancelled | refunded
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled', 'refunded'])
                ->default('pending');

            // Référence externe (Stripe PaymentIntent ID, CMI TransId, etc.)
            $table->string('external_id')->nullable()->index();
            $table->string('reference')->unique()->nullable();

            // Données brutes renvoyées par la passerelle
            $table->json('gateway_data')->nullable();

            // Horodatages métier
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_subscription_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};

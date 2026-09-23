<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table de journalisation des consommations d'abonnement pour traçabilité
     */
    public function up(): void
    {
        Schema::create('subscription_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_subscription_id')->constrained('user_subscriptions')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->foreignId('charging_session_id')->nullable()->constrained('charging_sessions')->nullOnDelete();
            
            // Type de consommation
            $table->enum('usage_type', [
                'session',        // Une session de charge
                'kwh',           // Consommation kWh
                'duration',      // Durée d'utilisation
            ]);
            
            // Quantités consommées
            $table->integer('sessions_consumed')->default(0);
            $table->decimal('kwh_consumed', 10, 2)->default(0);
            $table->integer('duration_minutes_consumed')->default(0);
            
            // Solde avant/après
            $table->integer('sessions_before')->default(0);
            $table->integer('sessions_after')->default(0);
            $table->decimal('kwh_before', 10, 2)->default(0);
            $table->decimal('kwh_after', 10, 2)->default(0);
            $table->integer('duration_before')->default(0);
            $table->integer('duration_after')->default(0);
            
            // Métadonnées
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_subscription_id', 'created_at'], 'usage_log_sub_idx');
            $table->index(['transaction_id'], 'usage_log_tx_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_usage_logs');
    }
};

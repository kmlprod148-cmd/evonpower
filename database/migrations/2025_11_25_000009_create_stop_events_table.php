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
        Schema::create('stop_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ocpp_transaction_id')->constrained('ocpp_transactions')->onDelete('cascade');
            $table->string('reason')->comment('end, reservation_end, insufficient_balance, payment_failed, manual, safety, operator');
            $table->text('description')->nullable()->comment('Description détaillée de la raison');
            $table->json('metadata')->nullable()->comment('Données additionnelles (balance, threshold, etc.)');
            $table->foreignId('triggered_by')->nullable()->constrained('users')->onDelete('set null')->comment('Utilisateur qui a déclenché l\'arrêt (si manuel)');
            $table->timestamp('triggered_at');
            $table->boolean('stop_requested')->default(false)->comment('Indique si la requête remote-stop a été envoyée');
            $table->boolean('stop_confirmed')->default(false)->comment('Indique si la borne a confirmé l\'arrêt');
            $table->text('stop_response')->nullable()->comment('Réponse de la borne');
            $table->timestamps();
            
            $table->index(['ocpp_transaction_id', 'triggered_at']);
            $table->index('reason');
            $table->index('triggered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stop_events');
    }
};


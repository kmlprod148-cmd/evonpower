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
        Schema::create('balance_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('previous_balance', 15, 2)->default(0);
            $table->decimal('new_balance', 15, 2)->default(0);
            $table->decimal('amount_changed', 15, 2)->default(0);
            $table->string('change_type')->comment('credit, debit, adjustment, recalculation');
            $table->string('source')->comment('transaction, commission, manual, system');
            $table->foreignId('source_id')->nullable()->comment('ID de la source (transaction, etc.)');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable()->comment('Données supplémentaires');
            $table->timestamp('calculated_at');
            $table->timestamps();

            // Index pour les performances
            $table->index(['user_id', 'calculated_at']);
            $table->index(['change_type', 'source']);
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_history');
    }
};

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
        Schema::create('balance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('total_commissions', 15, 2)->default(0);
            $table->decimal('pending_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->timestamp('last_transaction_date')->nullable();
            $table->json('hierarchy_data')->nullable()->comment('Données hiérarchiques');
            $table->json('commission_breakdown')->nullable()->comment('Breakdown des commissions');
            $table->json('performance_metrics')->nullable()->comment('Métriques de performance');
            $table->timestamp('snapshot_date')->nullable();
            $table->timestamps();

            // Index pour les performances
            $table->index(['user_id', 'snapshot_date']);
            $table->index('snapshot_date');
            $table->index('last_transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_snapshots');
    }
};

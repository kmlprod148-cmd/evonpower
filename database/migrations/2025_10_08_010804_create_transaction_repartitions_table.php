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
        Schema::create('transaction_repartitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->decimal('admin_amount', 15, 2)->default(0);
            $table->decimal('integrator_amount', 15, 2)->default(0);
            $table->decimal('operator_amount', 15, 2)->default(0);
            $table->timestamps();
            
            // Index pour optimiser les requêtes
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_repartitions');
    }
};

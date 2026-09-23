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
        // Vérifier si la table existe déjà
        if (!Schema::hasTable('transaction_repartitions')) {
            Schema::create('transaction_repartitions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transaction_id');
                $table->decimal('admin_amount', 10, 2)->default(0);
                $table->decimal('integrator_amount', 10, 2)->default(0);
                $table->decimal('operator_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2);
                $table->decimal('integrator_fee', 10, 2)->default(0);
                $table->decimal('admin_fee', 10, 2)->default(0);
                $table->timestamps();

                $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
                $table->index(['transaction_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_repartitions');
    }
};

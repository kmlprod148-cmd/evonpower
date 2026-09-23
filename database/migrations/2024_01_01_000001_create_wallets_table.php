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
        try {
            if (!Schema::hasTable('wallets')) {
                Schema::create('wallets', function (Blueprint $table) {
                    $table->id();
                    $table->morphs('owner'); // owner_type and owner_id
                    $table->decimal('balance', 15, 2)->default(0);
                    $table->string('currency', 3)->default('EUR');
                    $table->boolean('is_active')->default(true);
                    $table->string('name')->nullable();
                    $table->text('description')->nullable();
                    $table->decimal('min_balance', 15, 2)->nullable();
                    $table->decimal('max_balance', 15, 2)->nullable();
                    $table->boolean('auto_recharge')->default(false);
                    $table->decimal('auto_recharge_threshold', 15, 2)->nullable();
                    $table->decimal('auto_recharge_amount', 15, 2)->nullable();
                    $table->timestamps();

                    // Indexes
                    $table->index(['is_active', 'balance']);
                    $table->index('currency');
                    $table->index('auto_recharge');
                });
            }
        } catch (\Exception $e) {
            // Si la table existe déjà, on ignore l'erreur
            if (strpos($e->getMessage(), 'already exists') !== false) {
                return;
            }
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};

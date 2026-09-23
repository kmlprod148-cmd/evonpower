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
        if (!Schema::hasTable('reservation_participants')) {
            Schema::create('reservation_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reservation_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->enum('share_type', ['percentage', 'fixed'])->default('percentage');
                $table->decimal('share_value', 10, 4)->default(0);
                $table->decimal('share_amount', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->enum('status', ['pending', 'partial', 'paid', 'refunded', 'cancelled'])->default('pending');
                $table->unsignedBigInteger('last_wallet_transaction_id')->nullable();
                $table->unsignedBigInteger('last_refund_transaction_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['reservation_id', 'user_id']);
                $table->index(['reservation_id', 'status']);
                $table->index(['user_id', 'status']);
            });

            Schema::table('reservation_participants', function (Blueprint $table) {
                $table->foreign('last_wallet_transaction_id')
                    ->references('id')
                    ->on('wallet_transactions')
                    ->nullOnDelete();
                $table->foreign('last_refund_transaction_id')
                    ->references('id')
                    ->on('wallet_transactions')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_participants');
    }
};

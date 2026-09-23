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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null'); // Charging session transaction
            $table->foreignId('revenue_share_id')->nullable()->constrained('revenue_shares')->onDelete('set null');
            $table->foreignId('payer_account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('payee_account_id')->constrained('accounts')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('type'); // e.g., 'revenue_distribution', 'withdrawal', 'deposit'
            $table->string('status')->default('completed'); // e.g., 'pending', 'completed', 'failed'
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};

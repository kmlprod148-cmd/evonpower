<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('action', 100);
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            // Foreign keys
            $table->foreign('transaction_id')
                  ->references('id')
                  ->on('transactions')
                  ->onDelete('set null');
                  
            $table->foreign('performed_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
            
            // Indexes
            $table->index('transaction_id', 'idx_transaction_logs_transaction_id');
            $table->index('action', 'idx_transaction_logs_action');
            $table->index('created_at', 'idx_transaction_logs_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
    }
};

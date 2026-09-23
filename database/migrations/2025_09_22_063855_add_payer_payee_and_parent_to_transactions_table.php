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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('payer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('payee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('parent_transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['payer_id']);
            $table->dropColumn('payer_id');
            $table->dropForeign(['payee_id']);
            $table->dropColumn('payee_id');
            $table->dropForeign(['parent_transaction_id']);
            $table->dropColumn('parent_transaction_id');
        });
    }
};

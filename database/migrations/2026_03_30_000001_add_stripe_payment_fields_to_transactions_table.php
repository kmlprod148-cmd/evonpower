<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->index();
            }

            if (!Schema::hasColumn('transactions', 'stripe_session_id')) {
                $table->string('stripe_session_id')->nullable()->index();
            }

            if (!Schema::hasColumn('transactions', 'gateway_response')) {
                $table->json('gateway_response')->nullable();
            }

            if (!Schema::hasColumn('transactions', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }

            if (!Schema::hasColumn('transactions', 'webhook_data')) {
                $table->json('webhook_data')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $columns = ['payment_reference', 'stripe_session_id', 'gateway_response', 'completed_at', 'webhook_data'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

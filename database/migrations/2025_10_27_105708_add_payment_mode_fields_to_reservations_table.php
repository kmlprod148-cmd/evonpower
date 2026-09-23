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
        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('payment_mode', ['prepaid', 'postpaid'])->nullable()->after('payment_type');
            $table->decimal('prepaid_amount', 10, 2)->nullable()->after('payment_mode');
            $table->decimal('refund_amount', 10, 2)->nullable()->after('prepaid_amount');
            $table->decimal('min_threshold', 10, 2)->nullable()->after('refund_amount');
            $table->boolean('wallet_validation_passed')->default(false)->after('min_threshold');
            $table->timestamp('wallet_validated_at')->nullable()->after('wallet_validation_passed');
            $table->string('payment_method')->nullable()->after('wallet_validated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'payment_mode',
                'prepaid_amount',
                'refund_amount',
                'min_threshold',
                'wallet_validation_passed',
                'wallet_validated_at',
                'payment_method'
            ]);
        });
    }
};

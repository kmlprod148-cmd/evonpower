<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute les champs nécessaires pour le paiement postpaid guest (authorization hold)
     */
    public function up(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            // Authorization hold fields for postpaid payments
            if (!Schema::hasColumn('charging_sessions', 'authorization_hold_id')) {
                $table->string('authorization_hold_id')->nullable()->after('guest_payment_method_id');
            }
            if (!Schema::hasColumn('charging_sessions', 'authorization_hold_amount')) {
                $table->decimal('authorization_hold_amount', 10, 2)->nullable()->after('authorization_hold_id');
            }
            if (!Schema::hasColumn('charging_sessions', 'authorization_hold_created_at')) {
                $table->timestamp('authorization_hold_created_at')->nullable()->after('authorization_hold_amount');
            }

            // Capture status fields
            if (!Schema::hasColumn('charging_sessions', 'capture_status')) {
                $table->enum('capture_status', [
                    'pending',
                    'processing',
                    'captured',
                    'failed',
                    'cancelled',
                    'expired'
                ])->nullable()->after('authorization_hold_created_at');
            }
            if (!Schema::hasColumn('charging_sessions', 'capture_attempted_at')) {
                $table->timestamp('capture_attempted_at')->nullable()->after('capture_status');
            }
            if (!Schema::hasColumn('charging_sessions', 'capture_transaction_id')) {
                $table->string('capture_transaction_id')->nullable()->after('capture_attempted_at');
            }
            if (!Schema::hasColumn('charging_sessions', 'capture_error')) {
                $table->text('capture_error')->nullable()->after('capture_transaction_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('charging_sessions', 'capture_error')) {
                $table->dropColumn('capture_error');
            }
            if (Schema::hasColumn('charging_sessions', 'capture_transaction_id')) {
                $table->dropColumn('capture_transaction_id');
            }
            if (Schema::hasColumn('charging_sessions', 'capture_attempted_at')) {
                $table->dropColumn('capture_attempted_at');
            }
            if (Schema::hasColumn('charging_sessions', 'capture_status')) {
                $table->dropColumn('capture_status');
            }
            if (Schema::hasColumn('charging_sessions', 'authorization_hold_created_at')) {
                $table->dropColumn('authorization_hold_created_at');
            }
            if (Schema::hasColumn('charging_sessions', 'authorization_hold_amount')) {
                $table->dropColumn('authorization_hold_amount');
            }
            if (Schema::hasColumn('charging_sessions', 'authorization_hold_id')) {
                $table->dropColumn('authorization_hold_id');
            }
        });
    }
};

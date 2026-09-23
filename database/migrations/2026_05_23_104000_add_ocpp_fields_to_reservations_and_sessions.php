<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('ocpp_reservation_id')->nullable()->after('connector_id');
            $table->timestamp('remote_start_sent_at')->nullable()->after('expires_at');
            $table->timestamp('transaction_received_at')->nullable()->after('transaction_id');
            $table->string('start_initiation_status')->nullable()->after('session_initiation_status');
            $table->string('start_failure_reason')->nullable()->after('last_error');
        });

        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->timestamp('expected_stop_at')->nullable()->after('stop_reason');
            $table->timestamp('start_transaction_received_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'ocpp_reservation_id',
                'remote_start_sent_at',
                'transaction_received_at',
                'start_initiation_status',
                'start_failure_reason',
            ]);
        });

        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'expected_stop_at',
                'start_transaction_received_at',
            ]);
        });
    }
};
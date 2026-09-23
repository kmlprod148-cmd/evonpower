<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('reservations')) {
            return;
        }

        Schema::table('reservations', function (Blueprint $table) {
            // Connector locking status
            if (!Schema::hasColumn('reservations', 'connector_locked')) {
                $table->boolean('connector_locked')->default(false)->after('status')
                    ->comment('Indicates if the connector is physically locked');
            }

            if (!Schema::hasColumn('reservations', 'connector_locked_at')) {
                $table->timestamp('connector_locked_at')->nullable()->after('connector_locked')
                    ->comment('Timestamp when the connector was locked');
            }

            if (!Schema::hasColumn('reservations', 'connector_unlocked_at')) {
                $table->timestamp('connector_unlocked_at')->nullable()->after('connector_locked_at')
                    ->comment('Timestamp when the connector was unlocked');
            }

            // OCPP operation tracking
            if (!Schema::hasColumn('reservations', 'lock_operation_status')) {
                $table->string('lock_operation_status', 50)->nullable()->after('connector_unlocked_at')
                    ->comment('Status of the lock operation: pending, success, failed');
            }

            if (!Schema::hasColumn('reservations', 'lock_operation_attempts')) {
                $table->integer('lock_operation_attempts')->default(0)->after('lock_operation_status')
                    ->comment('Number of lock operation attempts');
            }

            if (!Schema::hasColumn('reservations', 'lock_operation_last_attempt_at')) {
                $table->timestamp('lock_operation_last_attempt_at')->nullable()->after('lock_operation_attempts')
                    ->comment('Timestamp of the last lock operation attempt');
            }

            // Payment confirmation tracking for credit balance
            if (!Schema::hasColumn('reservations', 'payment_confirmation_expires_at')) {
                $table->timestamp('payment_confirmation_expires_at')->nullable()->after('confirmed_at')
                    ->comment('Deadline for payment confirmation (5 min timeout)');
            }

            if (!Schema::hasColumn('reservations', 'payment_confirmed_at')) {
                $table->timestamp('payment_confirmed_at')->nullable()->after('payment_confirmation_expires_at')
                    ->comment('Timestamp when payment was confirmed by user');
            }

            // Card payment tracking
            if (!Schema::hasColumn('reservations', 'payment_gateway_transaction_id')) {
                $table->string('payment_gateway_transaction_id', 255)->nullable()->after('payment_confirmed_at')
                    ->comment('External payment gateway transaction ID');
            }

            if (!Schema::hasColumn('reservations', 'payment_webhook_received_at')) {
                $table->timestamp('payment_webhook_received_at')->nullable()->after('payment_gateway_transaction_id')
                    ->comment('Timestamp when payment webhook was received');
            }

            // Session initiation tracking
            if (!Schema::hasColumn('reservations', 'session_initiated_at')) {
                $table->timestamp('session_initiated_at')->nullable()->after('payment_webhook_received_at')
                    ->comment('Timestamp when charging session was initiated');
            }

            if (!Schema::hasColumn('reservations', 'session_initiation_status')) {
                $table->string('session_initiation_status', 50)->nullable()->after('session_initiated_at')
                    ->comment('Status of session initiation: pending, success, failed');
            }

            // Reservation expiration tracking
            if (!Schema::hasColumn('reservations', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('end_time')
                    ->comment('Reservation expiration timestamp');
            }

            if (!Schema::hasColumn('reservations', 'expiration_processed_at')) {
                $table->timestamp('expiration_processed_at')->nullable()->after('expires_at')
                    ->comment('Timestamp when expiration was processed');
            }

            // Audit and error tracking
            if (!Schema::hasColumn('reservations', 'ocpp_operations_log')) {
                $table->json('ocpp_operations_log')->nullable()->after('session_initiation_status')
                    ->comment('Log of OCPP operations for this reservation');
            }

            if (!Schema::hasColumn('reservations', 'last_error')) {
                $table->text('last_error')->nullable()->after('ocpp_operations_log')
                    ->comment('Last error message if any');
            }

            if (!Schema::hasColumn('reservations', 'last_error_at')) {
                $table->timestamp('last_error_at')->nullable()->after('last_error')
                    ->comment('Timestamp of the last error');
            }
        });

        // Add indexes for performance
        Schema::table('reservations', function (Blueprint $table) {
            if (!$this->indexExists('reservations', 'idx_reservations_connector_locked')) {
                $table->index('connector_locked', 'idx_reservations_connector_locked');
            }
            if (!$this->indexExists('reservations', 'idx_reservations_expires_at')) {
                $table->index('expires_at', 'idx_reservations_expires_at');
            }
            if (!$this->indexExists('reservations', 'idx_reservations_payment_confirmation')) {
                $table->index(['payment_confirmation_expires_at', 'status'], 'idx_reservations_payment_confirmation');
            }
            if (!$this->indexExists('reservations', 'idx_reservations_session_initiation')) {
                $table->index(['session_initiation_status', 'status'], 'idx_reservations_session_initiation');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('reservations')) {
            return;
        }

        Schema::table('reservations', function (Blueprint $table) {
            $columns = [
                'connector_locked',
                'connector_locked_at',
                'connector_unlocked_at',
                'lock_operation_status',
                'lock_operation_attempts',
                'lock_operation_last_attempt_at',
                'payment_confirmation_expires_at',
                'payment_confirmed_at',
                'payment_gateway_transaction_id',
                'payment_webhook_received_at',
                'session_initiated_at',
                'session_initiation_status',
                'expires_at',
                'expiration_processed_at',
                'ocpp_operations_log',
                'last_error',
                'last_error_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('reservations', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Drop indexes
            $indexes = [
                'idx_reservations_connector_locked',
                'idx_reservations_expires_at',
                'idx_reservations_payment_confirmation',
                'idx_reservations_session_initiation',
            ];

            foreach ($indexes as $index) {
                if ($this->indexExists('reservations', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support dropping indexes by name easily
            return false;
        }

        $indexes = DB::select("SHOW INDEX FROM {$table}");
        foreach ($indexes as $idx) {
            if ($idx->Key_name === $index) {
                return true;
            }
        }
        return false;
    }
};

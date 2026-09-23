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
        Schema::table('charging_sessions', function (Blueprint $table) {
            // Ajouter reservation_id si n'existe pas
            if (!Schema::hasColumn('charging_sessions', 'reservation_id')) {
                $table->foreignId('reservation_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained()
                    ->nullOnDelete();
            }

            // Champs Steve API
            if (!Schema::hasColumn('charging_sessions', 'steve_transaction_id')) {
                $table->string('steve_transaction_id')->nullable()->after('session_id');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'connector_id')) {
                $table->integer('connector_id')->default(1)->after('steve_transaction_id');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'ocpp_tag')) {
                $table->string('ocpp_tag')->nullable()->after('connector_id');
            }

            // Champs de paiement
            if (!Schema::hasColumn('charging_sessions', 'payment_mode')) {
                $table->enum('payment_mode', ['prepaid', 'postpaid'])->nullable()->after('payment_status');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'prepaid_amount')) {
                $table->decimal('prepaid_amount', 10, 2)->nullable()->after('payment_mode');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'refund_amount')) {
                $table->decimal('refund_amount', 10, 2)->nullable()->after('prepaid_amount');
            }

            // Champs d'estimation
            if (!Schema::hasColumn('charging_sessions', 'estimated_cost')) {
                $table->decimal('estimated_cost', 10, 2)->nullable()->after('cost');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'estimated_energy')) {
                $table->decimal('estimated_energy', 10, 2)->nullable()->after('estimated_cost');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'estimated_duration')) {
                $table->integer('estimated_duration')->nullable()->comment('Estimated duration in minutes')->after('estimated_energy');
            }

            // Champs actuels renommés pour cohérence
            if (Schema::hasColumn('charging_sessions', 'cost') && !Schema::hasColumn('charging_sessions', 'actual_cost')) {
                $table->renameColumn('cost', 'actual_cost');
            }
            
            if (Schema::hasColumn('charging_sessions', 'energy_consumed') && !Schema::hasColumn('charging_sessions', 'actual_energy')) {
                $table->renameColumn('energy_consumed', 'actual_energy');
            }
            
            if (Schema::hasColumn('charging_sessions', 'duration') && !Schema::hasColumn('charging_sessions', 'actual_duration')) {
                $table->renameColumn('duration', 'actual_duration');
            }

            // Transactions wallet
            if (!Schema::hasColumn('charging_sessions', 'wallet_transaction_id')) {
                $table->unsignedBigInteger('wallet_transaction_id')->nullable()->after('refund_amount');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'refund_transaction_id')) {
                $table->unsignedBigInteger('refund_transaction_id')->nullable()->after('wallet_transaction_id');
            }

            // Réponses Steve API
            if (!Schema::hasColumn('charging_sessions', 'steve_response')) {
                $table->json('steve_response')->nullable()->comment('Response from Steve API on start')->after('metadata');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'steve_stop_response')) {
                $table->json('steve_stop_response')->nullable()->comment('Response from Steve API on stop')->after('steve_response');
            }

            // Valeurs de compteur
            if (!Schema::hasColumn('charging_sessions', 'meter_start')) {
                $table->decimal('meter_start', 10, 2)->nullable()->comment('Meter value at start')->after('steve_stop_response');
            }
            
            if (!Schema::hasColumn('charging_sessions', 'meter_stop')) {
                $table->decimal('meter_stop', 10, 2)->nullable()->comment('Meter value at stop')->after('meter_start');
            }

            // Renommer started_at/ended_at si nécessaire
            if (Schema::hasColumn('charging_sessions', 'ended_at') && !Schema::hasColumn('charging_sessions', 'stopped_at')) {
                $table->renameColumn('ended_at', 'stopped_at');
            }

            // Mettre à jour l'enum status pour inclure 'active'
            $table->enum('status', ['active', 'in_progress', 'completed', 'stopped', 'error'])
                ->default('active')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $columnsToDropIfExist = [
                'reservation_id',
                'steve_transaction_id',
                'connector_id',
                'ocpp_tag',
                'payment_mode',
                'prepaid_amount',
                'refund_amount',
                'estimated_cost',
                'estimated_energy',
                'estimated_duration',
                'wallet_transaction_id',
                'refund_transaction_id',
                'steve_response',
                'steve_stop_response',
                'meter_start',
                'meter_stop'
            ];

            foreach ($columnsToDropIfExist as $column) {
                if (Schema::hasColumn('charging_sessions', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Restaurer les noms de colonnes originaux si renommés
            if (Schema::hasColumn('charging_sessions', 'actual_cost')) {
                $table->renameColumn('actual_cost', 'cost');
            }
            if (Schema::hasColumn('charging_sessions', 'actual_energy')) {
                $table->renameColumn('actual_energy', 'energy_consumed');
            }
            if (Schema::hasColumn('charging_sessions', 'actual_duration')) {
                $table->renameColumn('actual_duration', 'duration');
            }
            if (Schema::hasColumn('charging_sessions', 'stopped_at')) {
                $table->renameColumn('stopped_at', 'ended_at');
            }
        });
    }
};

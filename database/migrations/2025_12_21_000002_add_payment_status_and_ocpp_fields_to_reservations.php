<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ajoute les champs manquants pour la logique OCPP/SteVe selon les spécifications
     */
    public function up(): void
    {
        // Vérifier que la table existe avant de la modifier
        if (!Schema::hasTable('reservations')) {
            return;
        }
        
        Schema::table('reservations', function (Blueprint $table) {
            // Payment status (CRITICAL selon specs)
            if (!Schema::hasColumn('reservations', 'payment_status')) {
                $table->enum('payment_status', ['PENDING', 'PAID', 'FAILED', 'REFUNDED'])
                    ->default('PENDING')
                    ->after('status')
                    ->comment('Statut du paiement - doit être PAID pour démarrer');
            }
            
            // Limites pour enforcement (selon specs: balance OR time OR kWh)
            if (!Schema::hasColumn('reservations', 'max_kwh')) {
                $table->decimal('max_kwh', 10, 2)
                    ->nullable()
                    ->after('estimated_cost')
                    ->comment('Limite maximale en kWh');
            }
            
            if (!Schema::hasColumn('reservations', 'max_minutes')) {
                $table->integer('max_minutes')
                    ->nullable()
                    ->after('max_kwh')
                    ->comment('Limite maximale en minutes');
            }
            
            // Connector ID (chaque réservation doit cibler un connecteur spécifique)
            if (!Schema::hasColumn('reservations', 'connector_id')) {
                $table->foreignId('connector_id')
                    ->nullable()
                    ->after('charging_point_id')
                    ->constrained('connectors')
                    ->onDelete('set null')
                    ->comment('Connecteur réservé (OCPP connectorId)');
            }
            
            // OCPP Tag utilisé pour cette réservation
            if (!Schema::hasColumn('reservations', 'ocpp_tag_id')) {
                $table->foreignId('ocpp_tag_id')
                    ->nullable()
                    ->after('connector_id')
                    ->constrained('ocpp_tags')
                    ->onDelete('set null')
                    ->comment('Tag OCPP utilisé pour démarrer la session');
            }
            
            // Date d'approbation (pour traçabilité)
            if (!Schema::hasColumn('reservations', 'approved_at')) {
                $table->timestamp('approved_at')
                    ->nullable()
                    ->after('confirmed_at')
                    ->comment('Date et heure d\'approbation admin ou paiement');
            }
            
            if (!Schema::hasColumn('reservations', 'approved_by')) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->after('approved_at')
                    ->constrained('users')
                    ->onDelete('set null')
                    ->comment('Admin qui a approuvé (si approbation manuelle)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('reservations', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('reservations', 'ocpp_tag_id')) {
                $table->dropForeign(['ocpp_tag_id']);
                $table->dropColumn('ocpp_tag_id');
            }
            if (Schema::hasColumn('reservations', 'connector_id')) {
                $table->dropForeign(['connector_id']);
                $table->dropColumn('connector_id');
            }
            if (Schema::hasColumn('reservations', 'max_minutes')) {
                $table->dropColumn('max_minutes');
            }
            if (Schema::hasColumn('reservations', 'max_kwh')) {
                $table->dropColumn('max_kwh');
            }
            if (Schema::hasColumn('reservations', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};


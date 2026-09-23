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
        // Vérifier que la table charging_points existe avant de la modifier
        if (Schema::hasTable('charging_points')) {
            Schema::table('charging_points', function (Blueprint $table) {
                // Colonnes pour le monitoring SteVe - vérifier l'existence avant d'ajouter
                if (!Schema::hasColumn('charging_points', 'steve_charge_box_id')) {
                    $table->string('steve_charge_box_id')->nullable();
                }
                if (!Schema::hasColumn('charging_points', 'steve_status')) {
                    $table->enum('steve_status', ['online', 'offline', 'unknown'])->default('unknown');
                }
                if (!Schema::hasColumn('charging_points', 'steve_last_connected_at')) {
                    $table->timestamp('steve_last_connected_at')->nullable();
                }
                if (!Schema::hasColumn('charging_points', 'steve_last_disconnected_at')) {
                    $table->timestamp('steve_last_disconnected_at')->nullable();
                }
                if (!Schema::hasColumn('charging_points', 'steve_ocpp_configuration')) {
                    $table->json('steve_ocpp_configuration')->nullable();
                }
                if (!Schema::hasColumn('charging_points', 'steve_monitoring_data')) {
                    $table->json('steve_monitoring_data')->nullable();
                }
                if (!Schema::hasColumn('charging_points', 'steve_last_error')) {
                    $table->text('steve_last_error')->nullable();
                }
                if (!Schema::hasColumn('charging_points', 'steve_connection_attempts')) {
                    $table->integer('steve_connection_attempts')->default(0);
                }
                if (!Schema::hasColumn('charging_points', 'steve_last_heartbeat')) {
                    $table->timestamp('steve_last_heartbeat')->nullable();
                }
            });
        }

        // Créer une table pour les logs de monitoring
        if (!Schema::hasTable('steve_monitoring_logs')) {
            Schema::create('steve_monitoring_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('charging_point_id')->nullable();
                $table->string('event_type'); // connection, disconnection, command, error
                $table->string('level'); // info, warning, error, critical
                $table->text('message');
                $table->json('data')->nullable();
                $table->timestamps();
                
                $table->index(['charging_point_id', 'created_at']);
                $table->index(['event_type', 'level']);
            });
        }

        // Créer une table pour les commandes OCPP
        if (!Schema::hasTable('steve_ocpp_commands')) {
            Schema::create('steve_ocpp_commands', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('charging_point_id');
                $table->string('command'); // StartTransaction, StopTransaction, etc.
                $table->json('parameters');
                $table->json('response')->nullable();
                $table->enum('status', ['pending', 'success', 'failed', 'timeout'])->default('pending');
                $table->text('error_message')->nullable();
                $table->integer('response_time_ms')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();
                
                $table->index(['charging_point_id', 'sent_at']);
                $table->index(['command', 'status']);
            });
        }

        // Créer une table pour les alertes
        if (!Schema::hasTable('steve_alerts')) {
            Schema::create('steve_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('type'); // connectivity, performance, error
                $table->string('severity'); // info, warning, error, critical
                $table->string('title');
                $table->text('message');
                $table->json('data')->nullable();
                $table->boolean('resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                
                $table->index(['type', 'severity']);
                $table->index(['resolved', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('steve_alerts');
        Schema::dropIfExists('steve_ocpp_commands');
        Schema::dropIfExists('steve_monitoring_logs');
        
        Schema::table('charging_points', function (Blueprint $table) {
            $table->dropColumn([
                'steve_charge_box_id',
                'steve_status',
                'steve_last_connected_at',
                'steve_last_disconnected_at',
                'steve_ocpp_configuration',
                'steve_monitoring_data',
                'steve_last_error',
                'steve_connection_attempts',
                'steve_last_heartbeat'
            ]);
        });
    }
};

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
        // Ajouter les contraintes de clés étrangères pour plusieurs tables
        // Cette migration s'exécute après la création des tables charging_points et reservations
        
        // 1. Contraintes pour credit_requests -> charging_points
        if (Schema::hasTable('credit_requests') && Schema::hasTable('charging_points')) {
            try {
                Schema::table('credit_requests', function (Blueprint $table) {
                    $table->foreign('charging_point_id')
                        ->references('id')
                        ->on('charging_points')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                \Log::info('FK credit_requests->charging_points déjà existante: ' . $e->getMessage());
            }
        }
        
        // 2. Contraintes pour credit_requests -> reservations
        if (Schema::hasTable('credit_requests') && Schema::hasTable('reservations')) {
            try {
                Schema::table('credit_requests', function (Blueprint $table) {
                    $table->foreign('reservation_id')
                        ->references('id')
                        ->on('reservations')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                \Log::info('FK credit_requests->reservations déjà existante: ' . $e->getMessage());
            }
        }
        
        // 3. Contraintes pour auto_remote_start_logs -> charging_points
        if (Schema::hasTable('auto_remote_start_logs') && Schema::hasTable('charging_points')) {
            try {
                Schema::table('auto_remote_start_logs', function (Blueprint $table) {
                    $table->foreign('charging_point_id')
                        ->references('id')
                        ->on('charging_points')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                \Log::info('FK auto_remote_start_logs->charging_points déjà existante: ' . $e->getMessage());
            }
        }
        
        // 4. Contraintes pour auto_remote_start_logs -> reservations
        if (Schema::hasTable('auto_remote_start_logs') && Schema::hasTable('reservations')) {
            try {
                Schema::table('auto_remote_start_logs', function (Blueprint $table) {
                    $table->foreign('reservation_id')
                        ->references('id')
                        ->on('reservations')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                \Log::info('FK auto_remote_start_logs->reservations déjà existante: ' . $e->getMessage());
            }
        }
        
        // 5. Contraintes pour auto_remote_start_logs -> connectors
        if (Schema::hasTable('auto_remote_start_logs') && Schema::hasTable('connectors')) {
            try {
                Schema::table('auto_remote_start_logs', function (Blueprint $table) {
                    $table->foreign('connector_id')
                        ->references('id')
                        ->on('connectors')
                        ->onDelete('set null');
                });
            } catch (\Exception $e) {
                \Log::info('FK auto_remote_start_logs->connectors déjà existante: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Supprimer contraintes de credit_requests
        if (Schema::hasTable('credit_requests')) {
            Schema::table('credit_requests', function (Blueprint $table) {
                try {
                    $table->dropForeign(['charging_point_id']);
                } catch (\Exception $e) {}
                
                try {
                    $table->dropForeign(['reservation_id']);
                } catch (\Exception $e) {}
            });
        }
        
        // 2. Supprimer contraintes de auto_remote_start_logs
        if (Schema::hasTable('auto_remote_start_logs')) {
            Schema::table('auto_remote_start_logs', function (Blueprint $table) {
                try {
                    $table->dropForeign(['charging_point_id']);
                } catch (\Exception $e) {}
                
                try {
                    $table->dropForeign(['reservation_id']);
                } catch (\Exception $e) {}
                
                try {
                    $table->dropForeign(['connector_id']);
                } catch (\Exception $e) {}
            });
        }
    }
};


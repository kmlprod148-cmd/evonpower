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
        // Ajouter les contraintes de clés étrangères pour plusieurs tables
        // Cette migration s'exécute après la création des tables charging_points et reservations
        
        // 1. Contraintes pour credit_requests
        if (Schema::hasTable('credit_requests')) {
            Schema::table('credit_requests', function (Blueprint $table) {
                // Ajouter FK vers charging_points (seulement si la colonne existe)
                if (Schema::hasTable('charging_points') && Schema::hasColumn('credit_requests', 'charging_point_id')) {
                    try {
                        $table->foreign('charging_point_id')
                            ->references('id')
                            ->on('charging_points')
                            ->onDelete('set null');
                    } catch (\Exception $e) {
                        // La contrainte existe déjà, on ignore
                    }
                }
                
                // Ajouter FK vers reservations (seulement si la colonne existe)
                if (Schema::hasTable('reservations') && Schema::hasColumn('credit_requests', 'reservation_id')) {
                    try {
                        $table->foreign('reservation_id')
                            ->references('id')
                            ->on('reservations')
                            ->onDelete('set null');
                    } catch (\Exception $e) {
                        // La contrainte existe déjà, on ignore
                    }
                }
            });
        }
        
        // 2. Contraintes pour auto_remote_start_logs
        if (Schema::hasTable('auto_remote_start_logs')) {
            // SQLite ne supporte pas la modification de tables avec des vues dépendantes
            // On doit supprimer la vue, modifier la table, puis recréer la vue
            $viewExists = false;
            try {
                // Vérifier si la vue existe
                $result = DB::select("SELECT name FROM sqlite_master WHERE type='view' AND name='auto_remote_start_stats_view'");
                $viewExists = !empty($result);
            } catch (\Exception $e) {
                // Pour MySQL/PostgreSQL, on ignore simplement
            }
            
            // Supprimer la vue si elle existe
            if ($viewExists) {
                DB::statement("DROP VIEW IF EXISTS auto_remote_start_stats_view");
            }
            
            Schema::table('auto_remote_start_logs', function (Blueprint $table) {
                // Ajouter FK vers charging_points (seulement si la colonne existe)
                if (Schema::hasTable('charging_points') && Schema::hasColumn('auto_remote_start_logs', 'charging_point_id')) {
                    try {
                        $table->foreign('charging_point_id')
                            ->references('id')
                            ->on('charging_points')
                            ->onDelete('set null');
                    } catch (\Exception $e) {
                        // La contrainte existe déjà, on ignore
                    }
                }
                
                // Ajouter FK vers reservations (seulement si la colonne existe)
                if (Schema::hasTable('reservations') && Schema::hasColumn('auto_remote_start_logs', 'reservation_id')) {
                    try {
                        $table->foreign('reservation_id')
                            ->references('id')
                            ->on('reservations')
                            ->onDelete('set null');
                    } catch (\Exception $e) {
                        // La contrainte existe déjà, on ignore
                    }
                }
                
                // Ajouter FK vers connectors (seulement si la colonne existe)
                if (Schema::hasTable('connectors') && Schema::hasColumn('auto_remote_start_logs', 'connector_id')) {
                    try {
                        $table->foreign('connector_id')
                            ->references('id')
                            ->on('connectors')
                            ->onDelete('set null');
                    } catch (\Exception $e) {
                        // La contrainte existe déjà, on ignore
                    }
                }
            });
            
            // Recréer la vue si elle existait
            if ($viewExists) {
                DB::statement("
                    CREATE VIEW auto_remote_start_stats_view AS
                    SELECT 
                        DATE(processed_at) as date,
                        status,
                        COUNT(*) as total,
                        AVG(processing_duration_ms) as avg_duration_ms,
                        MIN(processing_duration_ms) as min_duration_ms,
                        MAX(processing_duration_ms) as max_duration_ms
                    FROM auto_remote_start_logs
                    GROUP BY DATE(processed_at), status
                ");
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
                if (Schema::hasColumn('credit_requests', 'charging_point_id')) {
                    try {
                        $table->dropForeign(['charging_point_id']);
                    } catch (\Exception $e) {}
                }
                
                if (Schema::hasColumn('credit_requests', 'reservation_id')) {
                    try {
                        $table->dropForeign(['reservation_id']);
                    } catch (\Exception $e) {}
                }
            });
        }
        
        // 2. Supprimer contraintes de auto_remote_start_logs
        if (Schema::hasTable('auto_remote_start_logs')) {
            // Gérer la vue dépendante pour SQLite
            $viewExists = false;
            try {
                $result = DB::select("SELECT name FROM sqlite_master WHERE type='view' AND name='auto_remote_start_stats_view'");
                $viewExists = !empty($result);
            } catch (\Exception $e) {}
            
            if ($viewExists) {
                DB::statement("DROP VIEW IF EXISTS auto_remote_start_stats_view");
            }
            
            Schema::table('auto_remote_start_logs', function (Blueprint $table) {
                if (Schema::hasColumn('auto_remote_start_logs', 'charging_point_id')) {
                    try {
                        $table->dropForeign(['charging_point_id']);
                    } catch (\Exception $e) {}
                }
                
                if (Schema::hasColumn('auto_remote_start_logs', 'reservation_id')) {
                    try {
                        $table->dropForeign(['reservation_id']);
                    } catch (\Exception $e) {}
                }
                
                if (Schema::hasColumn('auto_remote_start_logs', 'connector_id')) {
                    try {
                        $table->dropForeign(['connector_id']);
                    } catch (\Exception $e) {}
                }
            });
            
            // Recréer la vue si elle existait
            if ($viewExists) {
                DB::statement("
                    CREATE VIEW auto_remote_start_stats_view AS
                    SELECT 
                        DATE(processed_at) as date,
                        status,
                        COUNT(*) as total,
                        AVG(processing_duration_ms) as avg_duration_ms,
                        MIN(processing_duration_ms) as min_duration_ms,
                        MAX(processing_duration_ms) as max_duration_ms
                    FROM auto_remote_start_logs
                    GROUP BY DATE(processed_at), status
                ");
            }
        }
    }
};


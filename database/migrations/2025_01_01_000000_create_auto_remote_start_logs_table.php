<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration pour la table des logs de démarrage automatique OCPP
 * 
 * Cette table stocke l'historique de toutes les tentatives de démarrage
 * automatique de transactions, permettant le suivi, le debugging et les statistiques.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('auto_remote_start_logs')) {
            return; // La table existe déjà, on skip
        }
        
        Schema::create('auto_remote_start_logs', function (Blueprint $table) {
            $table->id();
            
            // Relations - Les contraintes FK seront ajoutées dans une migration ultérieure
            $table->unsignedBigInteger('reservation_id')
                ->nullable()
                ->comment('Réservation concernée');
                
            $table->unsignedBigInteger('charging_point_id')
                ->nullable()
                ->comment('Point de charge utilisé');
                
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Utilisateur de la réservation');
                
            $table->unsignedBigInteger('connector_id')
                ->nullable()
                ->comment('Connecteur utilisé');
                
            // Statut et résultat
            $table->enum('status', [
                'success',      // Démarrage réussi
                'failed',       // Échec du démarrage
                'skipped',      // Ignoré (conditions non remplies)
                'error',        // Erreur technique
                'retry'         // En cours de retry
            ])->comment('Statut de la tentative');
            
            $table->string('code', 50)
                ->nullable()
                ->comment('Code d\'erreur ou de succès');
            
            $table->text('message')
                ->nullable()
                ->comment('Message descriptif');
                
            // Détails de la tentative
            $table->integer('attempt_number')
                ->default(1)
                ->comment('Numéro de la tentative (1, 2, 3...)');
                
            $table->integer('max_attempts')
                ->default(3)
                ->comment('Nombre maximum de tentatives configurées');
                
            $table->boolean('is_final_attempt')
                ->default(false)
                ->comment('Indique si c\'était la dernière tentative');
                
            $table->boolean('auto_started')
                ->default(true)
                ->comment('Démarré automatiquement ou manuellement forcé');
                
            // Données techniques
            $table->string('ocpp_tag', 50)
                ->nullable()
                ->comment('Tag OCPP utilisé');
                
            $table->integer('connector_number')
                ->nullable()
                ->comment('Numéro du connecteur');
                
            $table->string('charge_box_id', 100)
                ->nullable()
                ->comment('ID de la borne dans Steve');
                
            $table->string('transaction_id', 100)
                ->nullable()
                ->comment('ID de la transaction OCPP démarrée');
                
            // Timings
            $table->timestamp('start_time')
                ->nullable()
                ->comment('Heure de début prévue de la réservation');
                
            $table->timestamp('processed_at')
                ->useCurrent()
                ->comment('Heure de traitement du log');
                
            $table->integer('processing_duration_ms')
                ->nullable()
                ->comment('Durée du traitement en millisecondes');
                
            // Conditions de validation
            $table->json('validation_checks')
                ->nullable()
                ->comment('Résultats des vérifications d\'éligibilité');
                
            $table->json('eligibility_data')
                ->nullable()
                ->comment('Données d\'éligibilité au moment du traitement');
                
            // Données de la réponse OCPP
            $table->json('ocpp_response')
                ->nullable()
                ->comment('Réponse complète de l\'API OCPP/Steve');
                
            $table->integer('ocpp_status_code')
                ->nullable()
                ->comment('Code HTTP de la réponse');
                
            $table->string('ocpp_status', 50)
                ->nullable()
                ->comment('Statut OCPP (Accepted, Rejected, etc.)');
                
            // Informations d\'erreur
            $table->text('error_message')
                ->nullable()
                ->comment('Message d\'erreur détaillé');
                
            $table->text('error_trace')
                ->nullable()
                ->comment('Stack trace en cas d\'exception');
                
            $table->string('error_type', 100)
                ->nullable()
                ->comment('Type d\'erreur (technical, validation, business, etc.)');
                
            // Métadonnées additionnelles
            $table->json('metadata')
                ->nullable()
                ->comment('Données additionnelles (contexte, debug info, etc.)');
                
            // Informations système
            $table->string('triggered_by', 50)
                ->default('scheduler')
                ->comment('Comment le démarrage a été déclenché (scheduler, manual, api, event)');
                
            $table->string('processing_mode', 20)
                ->default('queue')
                ->comment('Mode de traitement (queue, sync)');
                
            $table->string('server_hostname', 100)
                ->nullable()
                ->comment('Nom du serveur qui a traité');
                
            $table->string('job_id', 100)
                ->nullable()
                ->comment('ID du job Laravel si traité en queue');
                
            // Flags de suivi
            $table->boolean('user_notified')
                ->default(false)
                ->comment('Utilisateur notifié du résultat');
                
            $table->boolean('admin_alerted')
                ->default(false)
                ->comment('Administrateurs alertés en cas de problème');
                
            $table->boolean('requires_manual_action')
                ->default(false)
                ->comment('Nécessite une intervention manuelle');
                
            // Timestamps
            $table->timestamps();
            
            // Index pour les performances
            $table->index(['reservation_id', 'status']);
            $table->index(['charging_point_id', 'processed_at']);
            $table->index(['user_id', 'processed_at']);
            $table->index(['status', 'processed_at']);
            $table->index(['created_at']);
            $table->index(['auto_started', 'status']);
        });

        // Créer une vue pour les statistiques rapides (optionnel)
        // Note: Ne pas créer la vue en SQLite car elle cause des problèmes avec les migrations
        // SQLite ne gère pas bien les vues dépendantes lors des ALTER TABLE
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("DROP VIEW IF EXISTS auto_remote_start_stats_view");
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS auto_remote_start_stats_view");
        Schema::dropIfExists('auto_remote_start_logs');
    }
};


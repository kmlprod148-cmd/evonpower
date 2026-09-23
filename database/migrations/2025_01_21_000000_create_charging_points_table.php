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
        if (!Schema::hasTable('charging_points')) {
            Schema::create('charging_points', function (Blueprint $table) {
                $table->id();
                $table->string('external_id')->nullable()->unique(); // ID externe pour intégration avec d'autres systèmes
            
            // Relations
            $table->foreignId('integrator_id')->constrained()->onDelete('cascade');
            $table->foreignId('partner_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('pricing_plan_id')->nullable()->constrained()->onDelete('set null');
            
            // Informations de base
            $table->string('serial_number')->unique()->nullable(false);
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('firmware_version')->nullable();
            $table->enum('status', ['online', 'offline', 'charging', 'error', 'maintenance', 'reserved'])->default('offline');
            
            // Localisation
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable(); // Corrected this line
            $table->string('country')->nullable();
            $table->string('timezone')->default('UTC');
            
            // Informations d'installation et de maintenance
            $table->date('installation_date')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->string('installation_notes')->nullable();
            
            // Accès et configuration
            $table->enum('access_type', ['public', 'private', 'restricted'])->default('public');
            $table->boolean('public_access')->default(true);
            $table->string('access_code')->nullable();
            $table->json('access_control')->nullable(); // Configuration de contrôle d'accès
            
            // Informations techniques
            $table->string('ip_address')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('sim_card_number')->nullable();
            $table->string('communication_protocol')->nullable(); // OCPP, propriétaire, etc.
            $table->string('communication_protocol_version')->nullable(); // 1.6, 2.0, etc.
            
            // Informations contractuelles et commerciales
            $table->decimal('commission_rate', 5, 2)->nullable(); // Taux de commission spécifique à cette borne
            $table->string('contract_reference')->nullable();
            
            // QR code et identification
            $table->string('qr_code')->nullable();
            $table->string('evse_id')->nullable(); // ID standard format pour les points de charge (DE*ABC*E12345*1)
            
            // Configuration et paramètres
            $table->json('configuration')->nullable(); // Configuration générale
            $table->json('capabilities')->nullable(); // Capacités de la borne
            $table->json('smart_charging_profile')->nullable(); // Profil de charge intelligente
            $table->json('load_balancing_settings')->nullable(); // Paramètres d'équilibrage de charge
            
            // Suivi et mesures
            $table->decimal('total_energy_delivered', 12, 3)->default(0); // Total énergie délivrée (kWh)
            $table->integer('total_charging_sessions')->default(0); // Nombre total de sessions
            $table->timestamp('last_connection')->nullable(); // Dernière connexion
            $table->timestamp('last_status_update')->nullable(); // Dernière mise à jour de statut
            $table->timestamp('last_used_at')->nullable(); // Dernière utilisation
            
            // Champs additionnels
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Métadonnées supplémentaires
            
            // Timestamps standard
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour améliorer les performances des requêtes
            $table->index('status');
            $table->index('access_type');
            $table->index('public_access');
            $table->index(['latitude', 'longitude']);
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charging_points');
    }
};
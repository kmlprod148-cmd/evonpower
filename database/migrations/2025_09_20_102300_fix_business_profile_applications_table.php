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
        // Vérifier si la table existe
        if (Schema::hasTable('business_profile_applications')) {
            // Supprimer la table existante pour la recréer avec la bonne structure
            Schema::dropIfExists('business_profile_applications');
        }
        
        // Créer la table avec la structure corrigée
        Schema::create('business_profile_applications', function (Blueprint $table) {
            $table->id();
            
            // Références aux entités
            $table->unsignedBigInteger('business_profile_id')->comment('Business profile appliqué');
            $table->unsignedBigInteger('integrator_id')->nullable()->comment('Intégrateur concerné (référence la table integrators)');
            $table->unsignedBigInteger('partner_id')->nullable()->comment('Partenaire concerné (référence la table partners)');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Utilisateur concerné (référence la table users)');
            
            // Qui applique le business profile
            $table->unsignedBigInteger('applied_by_id')->comment('ID de celui qui applique');
            $table->string('applied_by_type')->comment('Type de celui qui applique (User, Admin, etc.)');
            
            // Données de l'application
            $table->json('application_data')->nullable()->comment('Détails de l\'application');
            $table->timestamp('applied_at')->useCurrent()->comment('Date d\'application');
            
            // Métadonnées
            $table->text('notes')->nullable()->comment('Notes sur l\'application');
            $table->enum('status', ['applied', 'reverted', 'pending'])->default('applied')->comment('Statut de l\'application');
            
            $table->timestamps();
            
            // Index pour les performances
            $table->index(['business_profile_id', 'status'], 'bp_app_bp_status_idx');
            $table->index(['integrator_id', 'status'], 'bp_app_integrator_status_idx');
            $table->index(['partner_id', 'status'], 'bp_app_partner_status_idx');
            $table->index(['user_id', 'status'], 'bp_app_user_status_idx');
            $table->index(['applied_by_id', 'applied_by_type'], 'bp_app_applied_by_idx');
            $table->index('applied_at', 'bp_app_applied_at_idx');
            
            // Contraintes de clés étrangères (seulement si les tables existent)
            if (Schema::hasTable('business_profiles')) {
                $table->foreign('business_profile_id')->references('id')->on('business_profiles')->onDelete('cascade');
            }
            if (Schema::hasTable('integrators')) {
                $table->foreign('integrator_id')->references('id')->on('integrators')->onDelete('cascade');
            }
            if (Schema::hasTable('partners')) {
                $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
            }
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_profile_applications');
    }
};

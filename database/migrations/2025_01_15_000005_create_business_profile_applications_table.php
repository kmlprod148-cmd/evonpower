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
        if (!Schema::hasTable('business_profile_applications')) {
            Schema::create('business_profile_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('operator_id')->constrained('users')->onDelete('cascade')->comment('Opérateur concerné');
                $table->foreignId('business_profile_id')->constrained('business_profiles')->onDelete('cascade')->comment('Business profile appliqué');
                $table->foreignId('integrator_id')->constrained('users')->onDelete('cascade')->comment('Intégrateur qui applique');
                
                // Données de l'application
                $table->json('application_data')->comment('Détails de l\'application (partenaires, charging points modifiés)');
                $table->timestamp('applied_at')->comment('Date d\'application');
                
                // Métadonnées
                $table->text('notes')->nullable()->comment('Notes sur l\'application');
                $table->enum('status', ['applied', 'reverted', 'pending'])->default('applied')->comment('Statut de l\'application');
                
                $table->timestamps();
                
                // Index avec noms courts pour éviter l'erreur MySQL
                $table->index(['integrator_id', 'applied_at'], 'bp_app_integrator_date_idx');
                $table->index(['operator_id', 'applied_at'], 'bp_app_operator_date_idx');
                $table->index(['business_profile_id', 'applied_at'], 'bp_app_bp_date_idx');
                $table->index('status', 'bp_app_status_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_profile_applications');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table des plans d'abonnement EV avec tous les types de plans supportés
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('type', [
                'per_charge',      // Basé sur le nombre de recharges
                'per_kwh',         // Basé sur un quota kWh
                'per_time',        // Basé sur le temps d'utilisation global
                'per_session',     // Basé sur la durée/session
                'monthly',         // Cycle mensuel (1 mois)
                'quarterly',       // Cycle trimestriel (3 mois)
                'semi_annual',     // Cycle semestriel (6 mois)
                'annual',          // Cycle annuel (12 mois)
            ]);
            $table->decimal('price', 10, 2);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->foreignId('vat_rate_id')->nullable()->constrained('vat_rates')->nullOnDelete();
            
            // Cycle de durée
            $table->integer('duration_months')->default(1); // 1, 3, 6, 12
            
            // Limitations et quotas
            $table->integer('max_sessions')->nullable();  // Nombre max de sessions (pour per_charge, per_session)
            $table->decimal('max_kwh', 10, 2)->nullable();  // Quota kWh max (pour per_kwh)
            $table->integer('max_duration_minutes')->nullable();  // Durée max en minutes (pour per_time, per_session)
            $table->integer('max_charging_points')->nullable();  // Nb max de bornes accessibles
            
            // Conditions générales
            $table->text('terms_conditions')->nullable();
            $table->text('features')->nullable(); // JSON array of features
            
            // Règles métier additionnelles
            $table->boolean('allow_renewal')->default(true);
            $table->boolean('allow_upgrade')->default(false);
            $table->boolean('allow_downgrade')->default(false);
            $table->decimal('cancellation_fee', 10, 2)->nullable();
            $table->integer('min_contract_months')->default(1);
            
            // Accès et visibilité
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            
            // Métadonnées
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};

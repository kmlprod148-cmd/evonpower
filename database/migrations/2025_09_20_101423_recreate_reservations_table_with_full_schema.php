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
        // Si la table existe déjà, on skip (évite de supprimer les données existantes)
        if (Schema::hasTable('reservations')) {
            \Log::info('Table reservations existe déjà, migration skippée');
            return;
        }
        
        // Désactiver temporairement les contraintes de clés étrangères
        Schema::disableForeignKeyConstraints();
        
        // Supprimer la table existante et la recréer avec le bon schéma
        Schema::dropIfExists('reservations');
        
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('charging_point_id')->constrained()->onDelete('cascade');
            $table->foreignId('pricing_plan_id')->constrained()->onDelete('cascade');
            
            // Type de réservation (kwh ou minute)
            $table->enum('reservation_type', ['kwh', 'minute']);
            
            // Valeur de la réservation (kWh ou minutes)
            $table->decimal('reservation_value', 8, 2);
            
            // Estimations
            $table->integer('estimated_duration')->nullable()->comment('Durée estimée en minutes');
            $table->decimal('estimated_energy', 8, 2)->nullable()->comment('Énergie estimée en kWh');
            $table->decimal('estimated_cost', 10, 2)->nullable()->comment('Coût estimé');
            
            // Limites du plan tarifaire
            $table->integer('max_duration')->nullable()->comment('Durée maximale selon le plan');
            $table->decimal('max_energy', 8, 2)->nullable()->comment('Énergie maximale selon le plan');
            
            // Statut de la réservation
            $table->enum('status', ['pending', 'pending_confirmation', 'confirmed', 'active', 'completed', 'cancelled'])->default('pending');
            
            // Date de confirmation
            $table->timestamp('confirmed_at')->nullable()->comment('Date et heure de confirmation de la réservation');

            // Type de paiement
            $table->enum('payment_type', ['cmi', 'offline'])->default('cmi');
            
            // Horodatage
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();

            // Order and Guest Information
            $table->string('order_id')->nullable()->comment('ID de commande unique');
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            
            // Colonnes supplémentaires utilisées par le code
            $table->decimal('amount', 10, 2)->nullable()->comment('Montant total de la réservation');
            $table->decimal('energy_kwh', 8, 2)->nullable()->comment('Énergie en kWh');
            $table->integer('duration_minutes')->nullable()->comment('Durée en minutes');
            
            // Valeurs réelles (après la session)
            $table->integer('actual_duration')->nullable()->comment('Durée réelle en minutes');
            $table->decimal('actual_energy', 8, 2)->nullable()->comment('Énergie réelle en kWh');
            $table->decimal('actual_cost', 10, 2)->nullable()->comment('Coût réel');
            
            // Informations supplémentaires
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable()->comment('Métadonnées additionnelles');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index pour les performances
            $table->index(['user_id', 'status']);
            $table->index(['charging_point_id', 'status']);
            $table->index(['payment_type', 'status']);
            $table->index(['pricing_plan_id']);
            $table->index(['start_time', 'end_time']);
        });
        
        // Réactiver les contraintes de clés étrangères
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('reservations');
        Schema::enableForeignKeyConstraints();
    }
};

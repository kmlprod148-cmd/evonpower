<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crée la table des véhicules pour les clients finaux
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            
            // Information du véhicule
            $table->string('make', 100)->comment('Marque du véhicule');
            $table->string('model', 100)->comment('Modèle du véhicule');
            $table->string('registration', 50)->unique()->comment('Immatriculation');
            $table->string('battery_capacity', 20)->nullable()->comment('Capacité batterie (kWh)');
            $table->string('connector_type', 50)->nullable()->comment('Type de connecteur');
            $table->integer('year')->nullable()->comment('Année de fabrication');
            $table->string('color', 50)->nullable()->comment('Couleur');
            $table->string('vin', 50)->nullable()->unique()->comment('Numéro VIN');
            
            // Statut
            $table->boolean('is_active')->default(true)->comment('Véhicule actif');
            $table->boolean('is_primary')->default(false)->comment('Véhicule principal');
            
            // Relations
            $table->foreignId('client_user_id')->constrained('client_users')->onDelete('cascade')->comment('Propriétaire du véhicule');
            
            // Notes
            $table->text('notes')->nullable()->comment('Notes supplémentaires');
            
            // Timestamps
            $table->timestamps();
            
            // Index
            $table->index('client_user_id');
            $table->index('is_primary');
            $table->index(['make', 'model']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

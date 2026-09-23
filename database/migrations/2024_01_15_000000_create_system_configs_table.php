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
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integrator_id')->constrained('integrators')->onDelete('cascade');
            $table->decimal('commission_rate', 5, 4)->default(0.05); // Taux de commission (0.0000 à 1.0000)
            $table->integer('max_operators')->default(10); // Nombre maximum d'opérateurs
            $table->boolean('auto_approve_operators')->default(false); // Approbation automatique des opérateurs
            $table->string('notification_email'); // Email pour les notifications
            $table->boolean('is_active')->default(true); // Configuration active
            $table->json('settings')->nullable(); // Paramètres additionnels
            $table->timestamps();

            // Index pour les performances
            $table->index('integrator_id');
            $table->index('is_active');
            
            // Contrainte unique pour s'assurer qu'un intégrateur n'a qu'une seule configuration
            $table->unique('integrator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_configs');
    }
};

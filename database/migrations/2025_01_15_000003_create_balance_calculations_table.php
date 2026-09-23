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
        Schema::create('balance_calculations', function (Blueprint $table) {
            $table->id();
            $table->string('calculation_type')->comment('user, role, global, manual');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('role')->nullable()->comment('admin, integrator, operator');
            $table->json('parameters')->nullable()->comment('Paramètres du calcul');
            $table->json('results')->nullable()->comment('Résultats du calcul');
            $table->integer('processed_users')->default(0);
            $table->integer('errors_count')->default(0);
            $table->json('errors')->nullable()->comment('Détails des erreurs');
            $table->decimal('execution_time', 8, 3)->nullable()->comment('Temps d\'exécution en secondes');
            $table->string('status')->default('pending')->comment('pending, running, completed, failed');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Index pour les performances
            $table->index(['calculation_type', 'status']);
            $table->index(['user_id', 'started_at']);
            $table->index('started_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_calculations');
    }
};

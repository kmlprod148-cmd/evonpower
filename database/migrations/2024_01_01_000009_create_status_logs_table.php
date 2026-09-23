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
        Schema::create('status_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // 'api', 'charger', 'service'
            $table->unsignedBigInteger('entity_id')->nullable(); // ID de l'entité (charger_id, etc.)
            $table->string('entity_name'); // Nom de l'entité (API name, charger name, etc.)
            $table->string('status'); // 'online', 'offline', 'degraded', 'unknown'
            $table->string('health_status'); // 'healthy', 'warning', 'critical', 'unknown'
            $table->integer('response_time_ms')->nullable();
            $table->integer('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Données supplémentaires
            $table->timestamp('checked_at');
            $table->timestamps();
            
            // Indexes pour les performances
            $table->index(['entity_type', 'entity_id']);
            $table->index(['entity_type', 'status']);
            $table->index(['entity_type', 'health_status']);
            $table->index(['checked_at']);
            $table->index(['entity_type', 'entity_id', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_logs');
    }
};

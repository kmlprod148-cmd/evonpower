<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Table pour stocker les tags OCPP (idTag) utilisés pour l'authentification OCPP
     * Selon la logique SteVe: chaque utilisateur peut avoir un ou plusieurs tags OCPP
     */
    public function up(): void
    {
        if (!Schema::hasTable('ocpp_tags')) {
            Schema::create('ocpp_tags', function (Blueprint $table) {
                $table->id();
                
                // Tag OCPP (idTag dans la spec OCPP)
                $table->string('ocpp_tag', 50)->unique()->comment('Tag OCPP unique (idTag)');
                
                // Relation avec l'utilisateur
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained()
                    ->onDelete('cascade')
                    ->comment('Utilisateur propriétaire du tag');
                
                // Statut du tag
                $table->boolean('blocked')
                    ->default(false)
                    ->comment('Si true, le tag ne peut pas démarrer de session');
                
                $table->boolean('is_default')
                    ->default(false)
                    ->comment('Tag par défaut pour cet utilisateur');
                
                // Informations supplémentaires
                $table->string('parent_id_tag', 50)->nullable()->comment('Tag parent (pour hiérarchie)');
                $table->timestamp('expiry_date')->nullable()->comment('Date d\'expiration du tag');
                $table->text('note')->nullable()->comment('Notes administratives');
                
                // Métadonnées pour suivi
                $table->integer('total_sessions')->default(0)->comment('Nombre total de sessions');
                $table->timestamp('last_used_at')->nullable()->comment('Dernière utilisation');
                
                $table->timestamps();
                $table->softDeletes();
                
                // Index pour performance
                $table->index(['user_id', 'blocked']);
                $table->index(['ocpp_tag', 'blocked']);
                $table->index('is_default');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ocpp_tags');
    }
};


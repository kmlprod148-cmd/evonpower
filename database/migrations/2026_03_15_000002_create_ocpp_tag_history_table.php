<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crée la table d'historique des associations de tags OCPP
     */
    public function up(): void
    {
        Schema::create('ocpp_tag_history', function (Blueprint $table) {
            $table->id();
            
            // Information du tag
            $table->string('ocpp_tag', 50)->comment('ID Tag OCPP');
            $table->foreignId('ocpp_tag_id')->constrained('ocpp_tags')->onDelete('cascade')->comment('Référence au tag');
            $table->foreignId('client_user_id')->constrained('client_users')->onDelete('cascade')->comment('Client associé');
            
            // Type d'action
            $table->enum('action', ['associated', 'dissociated', 'blocked', 'unblocked', 'created', 'expired'])
                ->comment('Type d\'action dans l\'historique');
            
            // Statut avant/après
            $table->string('status_before', 20)->nullable()->comment('Statut avant');
            $table->string('status_after', 20)->nullable()->comment('Statut après');
            
            // Information sur l'action
            $table->text('notes')->nullable()->comment('Notes sur l\'action');
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null')->comment('Utilisateur ayant effectué l\'action');
            
            // Timestamps
            $table->timestamp('action_date')->useCurrent()->comment('Date de l\'action');
            
            // Index
            $table->index('ocpp_tag_id');
            $table->index('client_user_id');
            $table->index('action');
            $table->index('action_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ocpp_tag_history');
    }
};

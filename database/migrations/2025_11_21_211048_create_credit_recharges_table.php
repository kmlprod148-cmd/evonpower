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
        if (Schema::hasTable('credit_recharges')) {
            return; // La table existe déjà, on skip
        }
        
        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        
        Schema::create('credit_recharges', function (Blueprint $table) use ($driver) {
            $table->id();
            
            // Relations (SQLite-compatible)
            if ($driver === 'sqlite') {
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('wallet_id')->nullable();
                $table->unsignedBigInteger('credit_pack_id')->nullable();
                $table->unsignedBigInteger('processed_by')->nullable();
            } else {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('wallet_id')->nullable()->constrained()->onDelete('set null');
                // credit_pack_id sans contrainte FK (table credit_packs créée plus tard)
                $table->unsignedBigInteger('credit_pack_id')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            }
            
            // Informations de base
            $table->boolean('is_custom')->default(false);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('payment_method', ['offline', 'cmi', 'stripe'])->default('offline');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            
            // Identifiants
            $table->string('reference')->unique();
            $table->string('external_id')->nullable()->comment('ID externe (CMI, Stripe, etc.)');
            
            // Descriptions et métadonnées
            $table->text('description')->nullable();
            $table->json('payment_data')->nullable()->comment('Données de paiement (réponse CMI, Stripe, etc.)');
            $table->json('metadata')->nullable()->comment('Métadonnées additionnelles');
            
            // Traitement
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index('reference');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_recharges');
    }
};

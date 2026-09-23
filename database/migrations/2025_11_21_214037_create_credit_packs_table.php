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
        Schema::create('credit_packs', function (Blueprint $table) {
            $table->id();
            
            // Informations de base
            $table->string('name');
            $table->text('description')->nullable();
            
            // Montants et prix
            $table->decimal('amount', 15, 2)->comment('Montant de crédit en EUR');
            $table->decimal('price', 15, 2)->comment('Prix d\'achat en EUR');
            $table->string('currency', 3)->default('EUR');
            
            // Ordre d'affichage
            $table->integer('order')->default(0)->comment('Ordre d\'affichage');
            
            // Statuts
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_client_only')->default(false)->comment('Réservé aux clients ayant fait des réservations');
            
            // Présentation
            $table->string('icon')->nullable()->comment('Icône (nom de classe ou URL)');
            $table->string('color')->nullable()->comment('Couleur (hex ou nom)');
            
            // Bonus (stocké en JSON)
            $table->json('bonus')->nullable()->comment('Bonus: {"type": "percentage", "value": 10} ou {"type": "fixed", "value": 5}');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('is_client_only');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_packs');
    }
};

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
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50)->index(); // system, business, financial, etc.
            $table->string('key', 100)->index(); // nom du paramètre
            $table->text('value')->nullable(); // valeur du paramètre
            $table->string('type', 20)->default('text'); // type de paramètre
            $table->text('description')->nullable(); // description du paramètre
            $table->boolean('is_active')->default(true); // paramètre actif
            $table->json('metadata')->nullable(); // métadonnées supplémentaires
            $table->timestamps();
            
            // Index composite pour éviter les doublons
            $table->unique(['category', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};

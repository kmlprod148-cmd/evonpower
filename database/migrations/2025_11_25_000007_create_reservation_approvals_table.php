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
        Schema::create('reservation_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->onDelete('cascade');
            $table->foreignId('approved_by')->constrained('users')->onDelete('cascade');
            $table->text('reason')->nullable()->comment('Raison de l\'approbation');
            $table->text('notes')->nullable()->comment('Notes additionnelles');
            $table->json('metadata')->nullable()->comment('Données additionnelles (IP, user agent, etc.)');
            $table->timestamps();
            
            $table->index(['reservation_id', 'created_at']);
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_approvals');
    }
};


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
        if (Schema::hasTable('credit_requests')) {
            return; // La table existe déjà, on skip (créée par 2024_12_22)
        }
        
        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        
        Schema::create('credit_requests', function (Blueprint $table) use ($driver) {
            $table->id();
            
            // Relations (SQLite-compatible)
            if ($driver === 'sqlite') {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('approved_by')->nullable();
            } else {
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            }
            
            // Informations de base
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('type', ['credit', 'refund', 'adjustment'])->default('credit');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            
            // Descriptions
            $table->text('description')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Métadonnées
            $table->json('metadata')->nullable()->comment('Métadonnées additionnelles');
            
            // Traitement
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('user_id');
            $table->index('status');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_requests');
    }
};

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
        Schema::create('commission_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('admin_percentage', 5, 2)->default(0);
            $table->decimal('integrator_percentage', 5, 2)->default(0);
            $table->decimal('partner_percentage', 5, 2)->default(0);
            $table->decimal('min_transaction_value', 10, 2)->nullable();
            $table->decimal('max_transaction_value', 10, 2)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Champs pour la gestion des transactions et accords
            $table->string('transaction_manager')->default('admin'); // 'admin', 'integrator', 'partner', 'shared'
            $table->json('transaction_permissions')->nullable(); // Permissions spécifiques pour chaque partie
            $table->boolean('requires_approval')->default(false); // Si les modifications nécessitent une approbation
            $table->json('approval_workflow')->nullable(); // Flux d'approbation
            $table->string('created_by_type')->nullable(); // 'admin', 'integrator'
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('applies_to_type')->default('global'); // 'global', 'integrator', 'partner', 'group'
            $table->unsignedBigInteger('applies_to_id')->nullable();
            $table->integer('priority')->default(0);
            $table->json('exclusions')->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['is_active', 'is_default']);
            $table->index(['applies_to_type', 'applies_to_id']);
            $table->index(['created_by_type', 'created_by_id']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_plans');
    }
};
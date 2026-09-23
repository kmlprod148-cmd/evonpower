<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This table stores the contract profile for each integrator with all billing configurations.
     */
    public function up(): void
    {
        Schema::create('integrator_contract_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integrator_id')->constrained('integrators')->onDelete('cascade');
            $table->string('contract_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'suspended', 'terminated', 'draft'])->default('draft');
            
            // Maintenance Fee Configuration (Frais maintenance)
            $table->boolean('maintenance_fee_enabled')->default(false);
            $table->decimal('maintenance_fee_amount', 10, 2)->default(0);
            $table->enum('maintenance_fee_period', ['monthly', 'quarterly', 'yearly'])->nullable();
            $table->date('maintenance_fee_start_date')->nullable();
            $table->date('maintenance_fee_next_due_date')->nullable();
            
            // Terminal Fee Configuration (Frais par borne active)
            $table->boolean('terminal_fee_enabled')->default(false);
            $table->decimal('terminal_fee_amount', 10, 2)->default(0);
            $table->enum('terminal_fee_period', ['monthly', 'quarterly', 'yearly'])->nullable();
            $table->integer('terminal_fee_minimum')->default(0);
            $table->integer('terminal_fee_free_count')->default(0);
            
            // Transaction Commission Configuration (Commission par transaction)
            $table->boolean('transaction_commission_enabled')->default(false);
            $table->decimal('transaction_commission_percentage', 5, 2)->default(0);
            $table->decimal('transaction_commission_fixed_amount', 10, 2)->default(0);
            $table->decimal('transaction_commission_min_amount', 10, 2)->default(0);
            $table->decimal('transaction_commission_max_amount', 10, 2)->nullable();
            $table->enum('transaction_commission_type', ['percentage', 'fixed', 'combined'])->default('percentage');
            
            // Currency
            $table->string('currency', 3)->default('EUR');
            
            // Contract dates
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->boolean('auto_renewal')->default(false);
            
            // Metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('integrator_id');
            $table->index('status');
            $table->index('contract_number');
            $table->index(['maintenance_fee_next_due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrator_contract_profiles');
    }
};

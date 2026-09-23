<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This table stores individual billing line items for integrator contracts.
     * Supports maintenance fees, terminal fees, and transaction commissions.
     */
    public function up(): void
    {
        if (Schema::hasTable('integrator_billing_line_items')) {
            return;
        }

        Schema::create('integrator_billing_line_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('integrator_contract_profile_id');
            $table->unsignedBigInteger('integrator_id');

            // Type of billing: maintenance, terminal, commission
            $table->enum('type', ['maintenance', 'terminal', 'commission']);

            // Reference to related entities
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('charging_point_id')->nullable();

            // Billing period
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();

            // Amount details
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');

            // Status
            $table->enum('status', ['pending', 'calculated', 'invoiced', 'paid', 'cancelled'])->default('pending');

            // Details for terminal fees
            $table->integer('terminal_count')->nullable();
            $table->integer('active_terminal_count')->nullable();

            // Details for commissions
            $table->string('commission_type')->nullable();
            $table->decimal('commission_percentage', 5, 2)->nullable();
            $table->decimal('commission_fixed_amount', 10, 2)->nullable();
            $table->decimal('transaction_amount', 12, 2)->nullable();

            // Notes
            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->foreign('integrator_contract_profile_id', 'ibli_icp_fk')
                ->references('id')
                ->on('integrator_contract_profiles')
                ->cascadeOnDelete();

            $table->foreign('integrator_id', 'ibli_integrator_fk')
                ->references('id')
                ->on('integrators')
                ->cascadeOnDelete();

            $table->foreign('transaction_id', 'ibli_tx_fk')
                ->references('id')
                ->on('transactions')
                ->nullOnDelete();

            $table->foreign('charging_point_id', 'ibli_cp_fk')
                ->references('id')
                ->on('charging_points')
                ->nullOnDelete();

            $table->index('type', 'ibli_type_idx');
            $table->index('status', 'ibli_status_idx');
            $table->index(['billing_period_start', 'billing_period_end'], 'ibli_period_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrator_billing_line_items');
    }
};

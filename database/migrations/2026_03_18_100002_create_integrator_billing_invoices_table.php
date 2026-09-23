<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This table stores generated invoices for integrator billing.
     */
    public function up(): void
    {
        if (Schema::hasTable('integrator_billing_invoices')) {
            return;
        }

        Schema::create('integrator_billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('integrator_contract_profile_id');
            $table->unsignedBigInteger('integrator_id');

            // Invoice details
            $table->string('invoice_number');
            $table->string('invoice_type');
            $table->enum('status', ['draft', 'pending', 'paid', 'overdue', 'cancelled', 'refunded'])->default('draft');

            // Period
            $table->date('billing_period_start');
            $table->date('billing_period_end');
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();

            // Financial details
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');

            // Payment details
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('payment_notes')->nullable();

            // Additional info
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('metadata')->nullable();

            // Related invoice
            $table->unsignedBigInteger('related_invoice_id')->nullable();

            // User tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('invoice_number', 'ibi_number_unique');

            $table->foreign('integrator_contract_profile_id', 'ibi_icp_fk')
                ->references('id')
                ->on('integrator_contract_profiles')
                ->cascadeOnDelete();

            $table->foreign('integrator_id', 'ibi_integrator_fk')
                ->references('id')
                ->on('integrators')
                ->cascadeOnDelete();

            $table->foreign('related_invoice_id', 'ibi_related_fk')
                ->references('id')
                ->on('integrator_billing_invoices')
                ->nullOnDelete();

            $table->foreign('created_by', 'ibi_created_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by', 'ibi_updated_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('approved_by', 'ibi_approved_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('paid_by', 'ibi_paid_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('status', 'ibi_status_idx');
            $table->index('billing_period_start', 'ibi_start_idx');
            $table->index('billing_period_end', 'ibi_end_idx');
            $table->index(['billing_period_start', 'billing_period_end'], 'ibi_period_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrator_billing_invoices');
    }
};

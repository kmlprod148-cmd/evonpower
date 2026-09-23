<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Pivot table linking invoices to their line items.
     */
    public function up(): void
    {
        if (Schema::hasTable('integrator_billing_invoice_line_item')) {
            return;
        }

        Schema::create('integrator_billing_invoice_line_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('line_item_id');

            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            $table->timestamps();

            $table->foreign('invoice_id', 'ibili_invoice_fk')
                ->references('id')
                ->on('integrator_billing_invoices')
                ->cascadeOnDelete();

            $table->foreign('line_item_id', 'ibili_line_item_fk')
                ->references('id')
                ->on('integrator_billing_line_items')
                ->cascadeOnDelete();

            $table->unique(['invoice_id', 'line_item_id'], 'ibili_invoice_line_unique');
            $table->index('line_item_id', 'ibili_line_item_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrator_billing_invoice_line_item');
    }
};

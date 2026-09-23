<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gain_distributions')) {
            Schema::create('gain_distributions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transaction_id')->index();
                $table->decimal('total_amount', 12, 2)->default(0);
                $table->decimal('admin_fees_total', 12, 2)->default(0);
                $table->decimal('revenue_after_admin', 12, 2)->default(0);

                $table->decimal('integrator_commission_amount', 12, 2)->default(0);
                $table->decimal('integrator_commission_percent', 5, 2)->default(0);
                $table->decimal('integrator_commission_fixed', 12, 2)->default(0);

                $table->decimal('partner_commission_amount', 12, 2)->default(0);
                $table->decimal('partner_commission_percent', 5, 2)->default(0);
                $table->decimal('partner_commission_fixed', 12, 2)->default(0);

                $table->decimal('operator_revenue', 12, 2)->default(0);
                $table->string('currency', 3)->default('EUR');

                $table->json('details')->nullable();
                $table->timestamps();

                $table->foreign('transaction_id')
                    ->references('id')->on('transactions')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gain_distributions');
    }
};



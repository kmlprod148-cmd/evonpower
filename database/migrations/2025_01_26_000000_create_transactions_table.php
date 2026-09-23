<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('transactions');
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('charging_point_id')->constrained()->onDelete('cascade');
           // $table->foreignId('connector_id')->nullable()->constrained('connectors')->onDelete('set null');
          //  $table->foreignId('commission_plan_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('reservation_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('pricing_plan_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('business_profile_id')->nullable()->constrained()->onDelete('set null');

            // Champs transaction
            $table->string('transaction_id')->nullable();
            $table->string('session_id')->nullable();
            $table->decimal('meter_start', 12, 4)->default(0)->nullable();
            $table->decimal('meter_stop', 12, 4)->nullable();
            $table->timestamp('start_timestamp')->nullable();
            $table->timestamp('stop_timestamp')->nullable();
            $table->string('reason')->nullable();
            $table->decimal('energy_delivered', 12, 4)->nullable();
            $table->integer('duration')->nullable();
            $table->decimal('amount', 8, 2)->nullable();
            $table->decimal('price_total', 12, 4)->nullable();
            $table->decimal('price_energy', 12, 4)->nullable();
            $table->decimal('price_time', 12, 4)->nullable();
            $table->decimal('price_service', 12, 4)->nullable();
            $table->decimal('price_tax', 12, 4)->nullable();
            $table->json('price_details')->nullable();
            $table->string('currency', 3)->nullable(false);

            // Statuts et authentification
            $table->string('status')->nullable();
            $table->string('auth_method')->nullable();
            $table->string('auth_id')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_id')->nullable();

            // Commissions
            $table->decimal('admin_commission', 12, 4)->nullable();
            $table->decimal('integrator_commission', 12, 4)->nullable();
            $table->decimal('partner_commission', 12, 4)->nullable();
            $table->boolean('admin_commission_paid')->default(false);
            $table->boolean('integrator_commission_paid')->default(false);
            $table->boolean('partner_commission_paid')->default(false);
            $table->timestamp('admin_commission_paid_at')->nullable();
            $table->timestamp('integrator_commission_paid_at')->nullable();
            $table->timestamp('partner_commission_paid_at')->nullable();
            $table->text('commission_notes')->nullable();

            // Breakdown et extra
            $table->json('repartition_breakdown')->nullable();

            // Timestamps et soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Index
            //$table->index('commission_plan_id');
            $table->index('created_at');
            $table->index('user_id', 'idx_transactions_user_id');
            $table->index('charging_point_id', 'idx_transactions_charging_point_id');
        });
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('transactions')) {
            Schema::dropIfExists('transactions');
        }
        Schema::enableForeignKeyConstraints();
    }
}; 
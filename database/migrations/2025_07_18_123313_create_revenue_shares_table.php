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
        Schema::create('revenue_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('business_profile_id')->constrained('business_profiles')->onDelete('cascade');
            $table->foreignId('integrator_id')->nullable()->constrained('integrators')->onDelete('set null');
            $table->foreignId('partner_id')->nullable()->constrained('partners')->onDelete('set null');
            $table->decimal('amount', 10, 4);
            $table->string('type'); // e.g., 'integrator_commission', 'partner_commission', 'business_profile_revenue'
            $table->foreignId('commission_plan_id')->nullable()->constrained('commission_plans')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revenue_shares');
    }
};

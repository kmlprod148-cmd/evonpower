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
        if (DB::getDriverName() === 'mysql') {
            Schema::table('business_profiles', function (Blueprint $table) {
                // Ajouter des valeurs par défaut pour tous les champs manquants
                $table->decimal('operator_commission', 5, 2)->default(0.00)->change();
                $table->decimal('integrator_commission', 5, 2)->default(0.00)->change();
                $table->decimal('owner_commission', 5, 2)->default(0.00)->change();
                $table->decimal('partner_commission', 5, 2)->default(0.00)->change();
                $table->decimal('admin_fee_fixed', 10, 4)->default(0.00)->change();
                $table->decimal('admin_fee_percentage', 5, 4)->default(0.00)->change();
                $table->decimal('integrator_fee_fixed', 10, 4)->default(0.00)->change();
                $table->decimal('integrator_fee_percentage', 5, 4)->default(0.00)->change();
                $table->decimal('partner_fee_fixed', 10, 4)->default(0.00)->change();
                $table->decimal('partner_fee_percentage', 5, 4)->default(0.00)->change();
                $table->decimal('base_fee_amount', 10, 4)->default(0.00)->change();
                $table->string('status')->default('active')->change();
                $table->boolean('is_active')->default(true)->change();
                $table->boolean('is_public')->default(false)->change();
            });
        } else {
            // Pour SQLite, on ne peut pas facilement modifier les colonnes
            echo "SQLite detected - skipping business_profiles column modifications\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('business_profiles', function (Blueprint $table) {
                // Supprimer les valeurs par défaut
                $table->decimal('operator_commission', 5, 2)->change();
                $table->decimal('integrator_commission', 5, 2)->change();
                $table->decimal('owner_commission', 5, 2)->change();
                $table->decimal('partner_commission', 5, 2)->change();
                $table->decimal('admin_fee_fixed', 10, 4)->change();
                $table->decimal('admin_fee_percentage', 5, 4)->change();
                $table->decimal('integrator_fee_fixed', 10, 4)->change();
                $table->decimal('integrator_fee_percentage', 5, 4)->change();
                $table->decimal('partner_fee_fixed', 10, 4)->change();
                $table->decimal('partner_fee_percentage', 5, 4)->change();
                $table->decimal('base_fee_amount', 10, 4)->change();
                $table->string('status')->change();
                $table->boolean('is_active')->change();
                $table->boolean('is_public')->change();
            });
        } else {
            // Pour SQLite, on ne peut pas facilement modifier les colonnes
            echo "SQLite detected - skipping business_profiles column modifications\n";
        }
    }
};

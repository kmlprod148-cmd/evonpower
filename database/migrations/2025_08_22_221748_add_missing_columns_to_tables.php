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
        // Ajouter les colonnes manquantes à pricing_plans
        Schema::table('pricing_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('pricing_plans', 'minimum_charge')) {
                $table->decimal('minimum_charge', 8, 2)->default(0.00)->after('activation_fee');
            }
            
            if (!Schema::hasColumn('pricing_plans', 'status')) {
                $table->string('status')->default('active')->after('is_active');
            }
        });

        // Ajouter les colonnes manquantes à stations
        Schema::table('stations', function (Blueprint $table) {
            if (!Schema::hasColumn('stations', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('group_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les colonnes ajoutées à pricing_plans
        Schema::table('pricing_plans', function (Blueprint $table) {
            if (Schema::hasColumn('pricing_plans', 'minimum_charge')) {
                $table->dropColumn('minimum_charge');
            }
            
            if (Schema::hasColumn('pricing_plans', 'status')) {
                $table->dropColumn('status');
            }
        });

        // Supprimer les colonnes ajoutées à stations
        Schema::table('stations', function (Blueprint $table) {
            if (Schema::hasColumn('stations', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};

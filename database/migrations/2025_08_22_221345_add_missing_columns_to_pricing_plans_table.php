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
        Schema::table('pricing_plans', function (Blueprint $table) {
            // Ajouter les colonnes manquantes
            if (!Schema::hasColumn('pricing_plans', 'minimum_charge')) {
                $table->decimal('minimum_charge', 8, 2)->default(0.00)->after('activation_fee');
            }
            
            if (!Schema::hasColumn('pricing_plans', 'status')) {
                $table->string('status')->default('active')->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            // Supprimer les colonnes ajoutées
            if (Schema::hasColumn('pricing_plans', 'minimum_charge')) {
                $table->dropColumn('minimum_charge');
            }
            
            if (Schema::hasColumn('pricing_plans', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

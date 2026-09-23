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
        Schema::table('transactions', function (Blueprint $table) {
            // Ajouter la colonne commission_plan_id pour permettre l'association avec les plans de commission
            $table->unsignedBigInteger('commission_plan_id')->nullable()->after('pricing_plan_id');
            
            // Ajouter la clé étrangère
            $table->foreign('commission_plan_id')->references('id')->on('commission_plans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['commission_plan_id']);
            $table->dropColumn('commission_plan_id');
        });
    }
};
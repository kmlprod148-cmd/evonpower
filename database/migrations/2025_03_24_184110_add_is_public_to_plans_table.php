<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPublicToPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('pricing_plans', function (Blueprint $table) {
        // Vérifier si la colonne is_active existe et l'ajouter si nécessaire
        if (!Schema::hasColumn('pricing_plans', 'is_active')) {
            $table->boolean('is_active')->default(true);
        }

        // Ensuite ajouter is_public après is_active
        if (!Schema::hasColumn('pricing_plans', 'is_public')) {
            $table->boolean('is_public')->default(false)->after('is_active');
        }
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            if (Schema::hasColumn('pricing_plans', 'is_public')) {
                $table->dropColumn('is_public');
            }
        });
    }
}
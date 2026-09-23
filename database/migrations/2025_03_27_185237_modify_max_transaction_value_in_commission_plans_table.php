<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyMaxTransactionValueInCommissionPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('commission_plans', function (Blueprint $table) {
            // Modifier la colonne pour utiliser un type de données plus grand
            // Si c'est actuellement un INT, passez à un BIGINT
            $table->bigInteger('max_transaction_value')->change();
            
            // Vous pourriez aussi avoir besoin de modifier min_transaction_value 
            // pour maintenir la cohérence
            $table->bigInteger('min_transaction_value')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('commission_plans', function (Blueprint $table) {
            // Retour aux types d'origine si nécessaire
            $table->integer('max_transaction_value')->change();
            $table->integer('min_transaction_value')->change();
        });
    }
}
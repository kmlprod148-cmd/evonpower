<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangePricingPlanAssociationToChargingPoints extends Migration
{
    public function up()
    {
        // Create the new pivot table
        Schema::create('charging_point_plan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('charging_point_id');
            $table->unsignedBigInteger('pricing_plan_id');
            $table->timestamps();

            $table->foreign('charging_point_id')->references('id')->on('charging_points')->onDelete('cascade');
            $table->foreign('pricing_plan_id')->references('id')->on('pricing_plans')->onDelete('cascade');
        });
    }

    public function down()
    {
        // Drop the pivot table
        Schema::dropIfExists('charging_point_plan');
    }
}
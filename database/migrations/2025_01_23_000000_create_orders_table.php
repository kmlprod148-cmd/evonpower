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
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id'); // utilisateur ayant passé la commande
                $table->unsignedBigInteger('plan_id'); // offre/plan choisi
                $table->unsignedBigInteger('charging_point_id'); // borne concernée
                $table->decimal('amount', 10, 2)->nullable(); // montant de la commande
                $table->string('status')->default('pending'); // statut de la commande
                $table->json('details')->nullable(); // détails additionnels (ex: infos borne, plan appliqué)
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users');
                $table->foreign('plan_id')->references('id')->on('pricing_plans');
                $table->foreign('charging_point_id')->references('id')->on('charging_points');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

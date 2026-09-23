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
        // Supprimer d'abord les contraintes de clés étrangères qui référencent la table orders
        $this->dropForeignKeysReferencingOrders();
        
        // Maintenant on peut supprimer la table
        Schema::dropIfExists('orders');
        
        // Recréer la table orders avec user_id nullable
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Rendu nullable
            $table->unsignedBigInteger('plan_id'); // offre/plan choisi
            $table->unsignedBigInteger('charging_point_id'); // borne concernée
            $table->decimal('amount', 10, 2)->nullable(); // montant de la commande
            $table->string('status')->default('pending'); // statut de la commande
            $table->json('details')->nullable(); // détails additionnels (ex: infos borne, plan appliqué)
            $table->timestamps();

            // Ajouter les clés étrangères seulement si les tables référencées existent
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users');
            }
            if (Schema::hasTable('pricing_plans')) {
                $table->foreign('plan_id')->references('id')->on('pricing_plans');
            }
            if (Schema::hasTable('charging_points')) {
                $table->foreign('charging_point_id')->references('id')->on('charging_points');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
        
        // Recréer la table orders avec user_id NOT NULL (retour à l'état original)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Retour à NOT NULL
            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('charging_point_id');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->json('details')->nullable();
            $table->timestamps();

            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users');
            }
            if (Schema::hasTable('pricing_plans')) {
                $table->foreign('plan_id')->references('id')->on('pricing_plans');
            }
            if (Schema::hasTable('charging_points')) {
                $table->foreign('charging_point_id')->references('id')->on('charging_points');
            }
        });
    }

    /**
     * Supprimer les contraintes de clés étrangères qui référencent la table orders
     */
    private function dropForeignKeysReferencingOrders(): void
    {
        // Supprimer les clés étrangères dans la table transactions
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'order_id')) {
            try {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->dropForeign(['order_id']);
                });
            } catch (\Exception $e) {
                // Ignorer l'erreur si la contrainte n'existe pas
            }
        }
        
        // Supprimer les clés étrangères dans la table reservations
        if (Schema::hasTable('reservations') && Schema::hasColumn('reservations', 'order_id')) {
            try {
                Schema::table('reservations', function (Blueprint $table) {
                    $table->dropForeign(['order_id']);
                });
            } catch (\Exception $e) {
                // Ignorer l'erreur si la contrainte n'existe pas
            }
        }
        
        // Supprimer les clés étrangères dans la table commission_transactions si elle existe
        if (Schema::hasTable('commission_transactions') && Schema::hasColumn('commission_transactions', 'order_id')) {
            try {
                Schema::table('commission_transactions', function (Blueprint $table) {
                    $table->dropForeign(['order_id']);
                });
            } catch (\Exception $e) {
                // Ignorer l'erreur si la contrainte n'existe pas
            }
        }
    }
};

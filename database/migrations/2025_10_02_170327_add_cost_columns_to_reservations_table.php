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
        Schema::table('reservations', function (Blueprint $table) {
            // Ajouter les colonnes de coût manquantes seulement si elles n'existent pas
            if (!Schema::hasColumn('reservations', 'estimated_cost')) {
                $table->decimal('estimated_cost', 10, 2)->nullable()->after('status');
            }
            if (!Schema::hasColumn('reservations', 'actual_cost')) {
                $table->decimal('actual_cost', 10, 2)->nullable()->after('estimated_cost');
            }
            if (!Schema::hasColumn('reservations', 'total_cost')) {
                $table->decimal('total_cost', 10, 2)->nullable()->after('actual_cost');
            }
            
            // Ajouter les colonnes de réservation manquantes
            if (!Schema::hasColumn('reservations', 'reservation_type')) {
                $table->string('reservation_type')->nullable()->after('total_cost');
            }
            if (!Schema::hasColumn('reservations', 'reservation_value')) {
                $table->decimal('reservation_value', 10, 2)->nullable()->after('reservation_type');
            }
            if (!Schema::hasColumn('reservations', 'estimated_energy')) {
                $table->decimal('estimated_energy', 10, 2)->nullable()->after('reservation_value');
            }
            if (!Schema::hasColumn('reservations', 'actual_energy')) {
                $table->decimal('actual_energy', 10, 2)->nullable()->after('estimated_energy');
            }
            if (!Schema::hasColumn('reservations', 'estimated_duration')) {
                $table->integer('estimated_duration')->nullable()->after('actual_energy');
            }
            if (!Schema::hasColumn('reservations', 'actual_duration')) {
                $table->integer('actual_duration')->nullable()->after('estimated_duration');
            }
            
            // Ajouter les colonnes de plan tarifaire
            if (!Schema::hasColumn('reservations', 'pricing_plan_id')) {
                $table->unsignedBigInteger('pricing_plan_id')->nullable()->after('actual_duration');
            }
            
            // Ajouter les colonnes de limites
            if (!Schema::hasColumn('reservations', 'max_duration')) {
                $table->integer('max_duration')->nullable()->after('pricing_plan_id');
            }
            if (!Schema::hasColumn('reservations', 'max_energy')) {
                $table->decimal('max_energy', 10, 2)->nullable()->after('max_duration');
            }
            
            // Ajouter les colonnes de paiement
            if (!Schema::hasColumn('reservations', 'payment_type')) {
                $table->string('payment_type')->nullable()->after('max_energy');
            }
            if (!Schema::hasColumn('reservations', 'order_id')) {
                $table->unsignedBigInteger('order_id')->nullable()->after('payment_type');
            }
            
            // Ajouter les colonnes d'énergie et durée
            if (!Schema::hasColumn('reservations', 'energy_kwh')) {
                $table->decimal('energy_kwh', 10, 2)->nullable()->after('order_id');
            }
            if (!Schema::hasColumn('reservations', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('energy_kwh');
            }
            
            // Ajouter les colonnes pour les invités
            if (!Schema::hasColumn('reservations', 'guest_email')) {
                $table->string('guest_email')->nullable()->after('duration_minutes');
            }
            if (!Schema::hasColumn('reservations', 'guest_phone')) {
                $table->string('guest_phone')->nullable()->after('guest_email');
            }
            
            // Ajouter les colonnes de confirmation
            if (!Schema::hasColumn('reservations', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('guest_phone');
            }
            if (!Schema::hasColumn('reservations', 'notes')) {
                $table->text('notes')->nullable()->after('confirmed_at');
            }
        });
        
        // Ajouter les clés étrangères séparément pour éviter les conflits
        if (Schema::hasColumn('reservations', 'pricing_plan_id') && Schema::hasTable('pricing_plans')) {
            try {
                Schema::table('reservations', function (Blueprint $table) {
                    $table->foreign('pricing_plan_id')->references('id')->on('pricing_plans')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Ignorer l'erreur si la clé étrangère existe déjà
            }
        }
        
        if (Schema::hasColumn('reservations', 'order_id') && Schema::hasTable('orders')) {
            try {
                Schema::table('reservations', function (Blueprint $table) {
                    $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Ignorer l'erreur si la clé étrangère existe déjà
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Supprimer les colonnes ajoutées
            $table->dropForeign(['pricing_plan_id']);
            $table->dropForeign(['order_id']);
            
            $table->dropColumn([
                'estimated_cost',
                'actual_cost', 
                'total_cost',
                'reservation_type',
                'reservation_value',
                'estimated_energy',
                'actual_energy',
                'estimated_duration',
                'actual_duration',
                'pricing_plan_id',
                'max_duration',
                'max_energy',
                'payment_type',
                'order_id',
                'energy_kwh',
                'duration_minutes',
                'guest_email',
                'guest_phone',
                'confirmed_at',
                'notes'
            ]);
        });
    }
};
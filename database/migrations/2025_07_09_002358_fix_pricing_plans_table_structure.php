<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vérifier si la table existe
        if (!Schema::hasTable('pricing_plans')) {
            Schema::create('pricing_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('rate_type')->default('fixed'); // 'fixed', 'time', 'energy'
                $table->decimal('base_rate', 8, 4)->nullable();
                $table->decimal('price_per_kwh', 8, 4)->nullable();
                $table->decimal('price_per_minute', 8, 4)->nullable();
                $table->decimal('activation_fee', 8, 2)->default(0.00);
                $table->unsignedBigInteger('vat_rate_id')->nullable();
                $table->integer('priority')->default(0);
                $table->integer('max_duration')->nullable(); // in minutes
                $table->boolean('is_active')->default(true);
                $table->string('currency', 3)->default('EUR');
                $table->string('billing_interval')->default('session'); // 'session', 'hourly', 'daily', 'monthly'
                $table->integer('min_charging_time')->default(0); // in minutes
                $table->integer('max_charging_time')->default(0); // in minutes
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            // Ajouter les colonnes manquantes si elles n'existent pas
            Schema::table('pricing_plans', function (Blueprint $table) {
                // Vérifier et ajouter integrator_id
                if (!Schema::hasColumn('pricing_plans', 'integrator_id')) {
                    $table->unsignedBigInteger('integrator_id')->nullable()->after('id');
                }
                // Vérifier et ajouter partner_id
                if (!Schema::hasColumn('pricing_plans', 'partner_id')) {
                    $table->unsignedBigInteger('partner_id')->nullable()->after('integrator_id');
                }
                // Vérifier et ajouter group_id
                if (!Schema::hasColumn('pricing_plans', 'group_id')) {
                    $table->unsignedBigInteger('group_id')->nullable()->after('partner_id');
                }
                // Vérifier et ajouter billing_interval
                if (!Schema::hasColumn('pricing_plans', 'billing_interval')) {
                    $table->string('billing_interval')->default('session')->after('currency');
                }
                
                // Vérifier et ajouter min_charging_time
                if (!Schema::hasColumn('pricing_plans', 'min_charging_time')) {
                    $table->integer('min_charging_time')->default(0)->after('billing_interval');
                }
                
                // Vérifier et ajouter max_charging_time
                if (!Schema::hasColumn('pricing_plans', 'max_charging_time')) {
                    $table->integer('max_charging_time')->default(0)->after('min_charging_time');
                }
                
                // Vérifier et ajouter softDeletes
                if (!Schema::hasColumn('pricing_plans', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // Ajouter les clés étrangères si elles n'existent pas
        $this->addForeignKeys();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les clés étrangères
        Schema::table('pricing_plans', function (Blueprint $table) {
            try {
                $table->dropForeign(['vat_rate_id']);
            } catch (\Exception $e) {
                // Ignorer l'erreur si la contrainte n'existe pas
            }
        });

        // Supprimer les colonnes ajoutées
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropColumn(['billing_interval', 'min_charging_time', 'max_charging_time', 'deleted_at']);
        });
    }

    /**
     * Ajouter les clés étrangères nécessaires
     */
    private function addForeignKeys()
    {
        // Foreign key pour vat_rate_id (déjà présent)
        if (Schema::hasTable('vat_rates')) {
            try {
                $constraintExists = \DB::select("
                    SELECT COUNT(*) as count
                    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                    WHERE CONSTRAINT_TYPE = 'FOREIGN KEY'
                    AND TABLE_NAME = 'pricing_plans'
                    AND CONSTRAINT_NAME = 'pricing_plans_vat_rate_id_foreign'
                ")[0]->count > 0;
                if (!$constraintExists) {
                    Schema::table('pricing_plans', function (Blueprint $table) {
                        $table->foreign('vat_rate_id')->references('id')->on('vat_rates')->onDelete('set null');
                    });
                }
            } catch (\Exception $e) {}
        }
        // Foreign key pour integrator_id
        if (Schema::hasTable('integrators') && Schema::hasColumn('pricing_plans', 'integrator_id')) {
            try {
                Schema::table('pricing_plans', function (Blueprint $table) {
                    $table->foreign('integrator_id')->references('id')->on('integrators')->onDelete('set null');
                });
            } catch (\Exception $e) {}
        }
        // Foreign key pour partner_id
        if (Schema::hasTable('partners') && Schema::hasColumn('pricing_plans', 'partner_id')) {
            try {
                Schema::table('pricing_plans', function (Blueprint $table) {
                    $table->foreign('partner_id')->references('id')->on('partners')->onDelete('set null');
                });
            } catch (\Exception $e) {}
        }
        // Foreign key pour group_id
        if (Schema::hasTable('groups') && Schema::hasColumn('pricing_plans', 'group_id')) {
            try {
                Schema::table('pricing_plans', function (Blueprint $table) {
                    $table->foreign('group_id')->references('id')->on('groups')->onDelete('set null');
                });
            } catch (\Exception $e) {}
        }
    }
};

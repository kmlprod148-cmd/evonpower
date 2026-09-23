<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable();
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable();
            }
            if (!Schema::hasColumn('users', 'postal_code')) {
                $table->string('postal_code')->nullable();
            }
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable();
            }
            if (!Schema::hasColumn('users', 'language')) {
                $table->string('language', 2)->default('fr');
            }
            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone')->default('Europe/Paris');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('users', 'integrator_id')) {
                $table->unsignedBigInteger('integrator_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'partner_id')) {
                $table->unsignedBigInteger('partner_id')->nullable();
            }

            // Add index only if columns exist
            if (Schema::hasColumn('users', 'integrator_id') && Schema::hasColumn('users', 'partner_id')) {
                 $table->index(['integrator_id', 'partner_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('users', 'address')) {
                $table->dropColumn('address');
            }
            if (Schema::hasColumn('users', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('users', 'postal_code')) {
                $table->dropColumn('postal_code');
            }
            if (Schema::hasColumn('users', 'country')) {
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('users', 'language')) {
                $table->dropColumn('language');
            }
            if (Schema::hasColumn('users', 'timezone')) {
                $table->dropColumn('timezone');
            }
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('users', 'integrator_id')) {
                // Sécurisation de la suppression de la clé étrangère et de l'index
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $doctrineTable = $sm->listTableDetails('users');
                if ($doctrineTable->hasForeignKey('users_integrator_id_foreign')) {
                    $table->dropForeign(['integrator_id']);
                }
                if ($doctrineTable->hasIndex('users_integrator_id_partner_id_index')) {
                    $table->dropIndex(['integrator_id', 'partner_id']);
                }
                $table->dropColumn('integrator_id');
            }
            if (Schema::hasColumn('users', 'partner_id')) {
                $table->dropColumn('partner_id');
            }
        });
    }
};

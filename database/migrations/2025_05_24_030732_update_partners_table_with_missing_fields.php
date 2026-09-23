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
        Schema::table('partners', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('partners', 'type')) {
                $table->enum('type', ['Exploitant', 'Propriétaire', 'Intégrateur'])->nullable()->after('contact_name');
            }
            if (!Schema::hasColumn('partners', 'integrator_id')) {
                $table->unsignedBigInteger('integrator_id')->nullable()->after('country');
                $table->foreign('integrator_id')->references('id')->on('integrators')->onDelete('set null');
            }
            if (!Schema::hasColumn('partners', 'business_profile_id')) {
                $table->unsignedBigInteger('business_profile_id')->nullable()->after('integrator_id');
                $table->foreign('business_profile_id')->references('id')->on('business_profiles')->onDelete('set null');
            }
            if (!Schema::hasColumn('partners', 'description')) {
                $table->text('description')->nullable()->after('logo');
            }
            if (!Schema::hasColumn('partners', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }

            // Remove status column if it exists
            if (Schema::hasColumn('partners', 'status')) {
                $table->dropColumn('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            // Drop added columns
            if (Schema::hasColumn('partners', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('partners', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('partners', 'business_profile_id')) {
                // Sécurisation de la suppression de la clé étrangère
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $doctrineTable = $sm->listTableDetails('partners');
                if ($doctrineTable->hasForeignKey('partners_business_profile_id_foreign')) {
                    $table->dropForeign(['business_profile_id']);
                }
                $table->dropColumn('business_profile_id');
            }
            if (Schema::hasColumn('partners', 'integrator_id')) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $doctrineTable = $sm->listTableDetails('partners');
                if ($doctrineTable->hasForeignKey('partners_integrator_id_foreign')) {
                    $table->dropForeign(['integrator_id']);
                }
                $table->dropColumn('integrator_id');
            }
            if (Schema::hasColumn('partners', 'type')) {
                $table->dropColumn('type');
            }

            // Add back status column if it was dropped
            if (!Schema::hasColumn('partners', 'status')) {
                 $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('vat_number'); // Assuming original position
            }
        });
    }
};

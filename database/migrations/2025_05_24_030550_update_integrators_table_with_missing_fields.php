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
        Schema::table('integrators', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('integrators', 'address')) {
                $table->string('address')->nullable()->after('city');
            }
            if (!Schema::hasColumn('integrators', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('address');
            }
            if (!Schema::hasColumn('integrators', 'country')) {
                $table->string('country')->nullable()->after('postal_code');
            }
            if (!Schema::hasColumn('integrators', 'business_profile_id')) {
                $table->unsignedBigInteger('business_profile_id')->nullable()->after('country');
                $table->foreign('business_profile_id')->references('id')->on('business_profiles')->onDelete('set null');
            }
            if (!Schema::hasColumn('integrators', 'contact_name')) {
                $table->string('contact_name')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('integrators', 'website')) {
                $table->string('website')->nullable()->after('contact_name');
            }
            if (!Schema::hasColumn('integrators', 'logo')) {
                $table->string('logo')->nullable()->after('website');
            }
            if (!Schema::hasColumn('integrators', 'description')) {
                $table->text('description')->nullable()->after('logo');
            }
            if (!Schema::hasColumn('integrators', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }

            // Remove admin_name column if it exists
            if (Schema::hasColumn('integrators', 'admin_name')) {
                $table->dropColumn('admin_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integrators', function (Blueprint $table) {
            // Drop added columns
            if (Schema::hasColumn('integrators', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('integrators', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('integrators', 'logo')) {
                $table->dropColumn('logo');
            }
            if (Schema::hasColumn('integrators', 'website')) {
                $table->dropColumn('website');
            }
            if (Schema::hasColumn('integrators', 'contact_name')) {
                $table->dropColumn('contact_name');
            }
            if (Schema::hasColumn('integrators', 'business_profile_id')) {
                 $table->dropColumn('business_profile_id');
            }
            if (Schema::hasColumn('integrators', 'country')) {
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('integrators', 'postal_code')) {
                $table->dropColumn('postal_code');
            }
            if (Schema::hasColumn('integrators', 'address')) {
                $table->dropColumn('address');
            }

            // Add back admin_name column
            if (!Schema::hasColumn('integrators', 'admin_name')) {
                 $table->string('admin_name')->nullable()->after('phone'); // Assuming original position
            }
        });
    }
};

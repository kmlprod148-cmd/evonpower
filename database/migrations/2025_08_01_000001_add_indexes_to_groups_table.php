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
        Schema::table('groups', function (Blueprint $table) {
            if (!Schema::hasIndex('groups', 'groups_name_index')) {
                $table->index('name');
            }
            if (!Schema::hasIndex('groups', 'groups_city_index')) {
                $table->index('city');
            }
            if (Schema::hasColumn('groups', 'business_profile_id') && !Schema::hasIndex('groups', 'groups_business_profile_id_index')) {
                $table->index('business_profile_id');
            }
            if (!Schema::hasIndex('groups', 'groups_is_active_index')) {
                $table->index('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['city']);
            if (Schema::hasColumn('groups', 'business_profile_id')) {
                $table->dropIndex(['business_profile_id']);
            }
            $table->dropIndex(['is_active']);
        });
    }
};
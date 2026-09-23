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
            if (Schema::hasIndex('groups', 'groups_name_index')) {
                $table->dropIndex('groups_name_index');
            }
            $table->index('name');

            if (Schema::hasIndex('groups', 'groups_city_index')) {
                $table->dropIndex('groups_city_index');
            }
            $table->index('city');

            if (Schema::hasIndex('groups', 'groups_is_active_index')) {
                $table->dropIndex('groups_is_active_index');
            }
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasIndex('groups', 'groups_name_index')) {
                $table->dropIndex('groups_name_index');
            }
            if (Schema::hasIndex('groups', 'groups_city_index')) {
                $table->dropIndex('groups_city_index');
            }
            if (Schema::hasIndex('groups', 'groups_is_active_index')) {
                $table->dropIndex('groups_is_active_index');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('business_profiles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_public');
            }
        });
    }

    public function down()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('business_profiles', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
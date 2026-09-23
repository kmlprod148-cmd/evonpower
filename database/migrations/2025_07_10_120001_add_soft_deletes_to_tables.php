<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToTables extends Migration
{
    public function up()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('business_profiles', 'deleted_at')) {
                $table->softDeletes();
            }
        });
        Schema::table('charging_points', function (Blueprint $table) {
            if (!Schema::hasColumn('charging_points', 'deleted_at')) {
                $table->softDeletes();
            }
        });
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }
    public function down()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('charging_points', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
} 
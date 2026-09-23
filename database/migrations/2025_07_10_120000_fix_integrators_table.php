<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixIntegratorsTable extends Migration
{
    public function up()
    {
        Schema::table('integrators', function (Blueprint $table) {
            if (!Schema::hasColumn('integrators', 'type')) {
                $table->string('type')->default('Intégrateur')->after('name');
            }
        });
    }

    public function down()
    {
        Schema::table('integrators', function (Blueprint $table) {
            if (Schema::hasColumn('integrators', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
} 
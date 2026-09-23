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
    Schema::table('integrators', function (Blueprint $table) {
        $table->string('city')->nullable()->change();
    });
}

public function down()
{
    Schema::table('integrators', function (Blueprint $table) {
        $table->string('city')->nullable(false)->change();
    });
}
};

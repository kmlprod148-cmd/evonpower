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
        $table->string('email')->nullable()->change();
        $table->string('phone')->nullable()->change();
        $table->string('city')->nullable()->change();
        $table->string('admin_name')->nullable()->change();
        $table->string('admin_name')->nullable()->change();
        // Add any other required fields here
    });
}

public function down()
{
    Schema::table('integrators', function (Blueprint $table) {
        $table->string('email')->nullable(false)->change();
        $table->string('phone')->nullable(false)->change();
        $table->string('city')->nullable(false)->change();
        $table->string('admin_name')->nullable(false)->change();
        $table->string('admin_name')->nullable(false)->change();
        // Revert any other fields changed
    });
}
};

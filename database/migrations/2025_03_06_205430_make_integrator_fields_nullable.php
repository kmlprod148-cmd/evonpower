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
        // Include any other fields that might cause similar errors
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

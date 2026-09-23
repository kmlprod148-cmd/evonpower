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
        Schema::table('users', function (Blueprint $table) {
            $table->string('language')->default('en')->after('email_verified_at');
            $table->string('timezone')->default('UTC')->after('language');
            $table->string('theme')->default('light')->after('timezone');
            $table->boolean('two_factor_enabled')->default(false)->after('theme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['language', 'timezone', 'theme', 'two_factor_enabled']);
        });
    }
};

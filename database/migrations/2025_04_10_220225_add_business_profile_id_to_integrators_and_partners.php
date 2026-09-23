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
            $table->unsignedBigInteger('business_profile_id')->nullable()->after('id');
            $table->foreign('business_profile_id')->references('id')->on('business_profiles')->onDelete('set null');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->unsignedBigInteger('business_profile_id')->nullable()->after('id');
            $table->foreign('business_profile_id')->references('id')->on('business_profiles')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integrators', function (Blueprint $table) {
            if (Schema::hasColumn('integrators', 'business_profile_id')) {
                $table->dropForeign(['business_profile_id']);
                $table->dropColumn('business_profile_id');
            }
        });

        Schema::table('partners', function (Blueprint $table) {
            if (Schema::hasColumn('partners', 'business_profile_id')) {
                $table->dropForeign(['business_profile_id']);
                $table->dropColumn('business_profile_id');
            }
        });
    }
};

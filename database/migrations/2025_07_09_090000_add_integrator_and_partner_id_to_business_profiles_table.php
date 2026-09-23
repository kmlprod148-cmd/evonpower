<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('business_profiles', 'integrator_id')) {
                $table->unsignedBigInteger('integrator_id')->nullable()->after('owner_commission');
                $table->foreign('integrator_id')->references('id')->on('integrators')->onDelete('set null');
            }
            if (!Schema::hasColumn('business_profiles', 'partner_id')) {
                $table->unsignedBigInteger('partner_id')->nullable()->after('integrator_id');
                $table->foreign('partner_id')->references('id')->on('partners')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('business_profiles', 'integrator_id')) {
                try {
                    $table->dropForeign(['integrator_id']);
                } catch (\Exception $e) {}
                $table->dropColumn('integrator_id');
            }
            if (Schema::hasColumn('business_profiles', 'partner_id')) {
                $table->dropColumn('partner_id');
            }
        });
    }
}; 
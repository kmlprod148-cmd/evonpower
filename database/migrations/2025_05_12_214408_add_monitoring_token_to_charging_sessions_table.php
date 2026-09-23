<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMonitoringTokenToChargingSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('charging_sessions', 'monitoring_token')) {
                $table->string('monitoring_token')->nullable();
            }
            
            if (!Schema::hasColumn('charging_sessions', 'is_public')) {
                $table->boolean('is_public')->default(false);
            }
            
            if (!Schema::hasColumn('charging_sessions', 'email')) {
                $table->string('email')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropColumn(['monitoring_token', 'is_public', 'email']);
        });
    }
}

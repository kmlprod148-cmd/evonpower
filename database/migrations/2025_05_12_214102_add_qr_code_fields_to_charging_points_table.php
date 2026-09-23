<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQrCodeFieldsToChargingPointsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('charging_points', function (Blueprint $table) {
            if (!Schema::hasColumn('charging_points', 'qr_code')) {
                $table->string('qr_code')->nullable();
            }
            
            if (!Schema::hasColumn('charging_points', 'qr_code_generated_at')) {
                $table->timestamp('qr_code_generated_at')->nullable();
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
        Schema::table('charging_points', function (Blueprint $table) {
            $table->dropColumn(['qr_code', 'qr_code_generated_at']);
        });
    }
}

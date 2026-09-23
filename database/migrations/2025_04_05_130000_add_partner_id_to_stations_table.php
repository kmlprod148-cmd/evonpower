<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPartnerIdToStationsTable extends Migration
{
    public function up()
    {
        Schema::table('stations', function (Blueprint $table) {
            if (!Schema::hasColumn('stations', 'partner_id')) {
                $table->unsignedBigInteger('partner_id')->nullable()
                    ->comment('References partners table');
                $table->foreign('partner_id')
                    ->references('id')
                    ->on('partners')
                    ->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropForeign(['partner_id']);
            $table->dropColumn('partner_id');
        });
    }
}
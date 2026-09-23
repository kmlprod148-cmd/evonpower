<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLabelToStationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    // D'abord, ajoutez la colonne label si elle n'existe pas
    if (!Schema::hasColumn('stations', 'label')) {
        Schema::table('stations', function (Blueprint $table) {
            $table->string('label')->nullable()->after('name');
        });
    }

    // Ensuite, mettez à jour les valeurs uniquement si station_name existe
    if (Schema::hasColumn('stations', 'station_name')) {
        DB::statement('UPDATE stations SET label = station_name WHERE label IS NULL');
    } else if (Schema::hasColumn('stations', 'name')) {
        // Si station_name n'existe pas mais name existe, utilisez name à la place
        DB::statement('UPDATE stations SET label = name WHERE label IS NULL');
    }
}
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stations', function (Blueprint $table) {
            // If label exists, create station_name and copy data back
            if (Schema::hasColumn('stations', 'label')) {
                $table->string('label')->nullable()->after('id');
                
                // Copy label back to station_name
                \DB::statement('UPDATE stations SET station_name = label WHERE station_name IS NULL');
                
                // Drop label column
                $table->dropColumn('label');
            }
        });
    }
}
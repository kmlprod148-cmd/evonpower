<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateChargingPointsStationIdColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('charging_points', function (Blueprint $table) {
            // If the column doesn't exist, add it
            if (!Schema::hasColumn('charging_points', 'station_id')) {
                $table->string('station_id')->nullable()->after('id');
            }
        });

        // Optionally, update existing records with a generated station_id
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                UPDATE charging_points 
                SET station_id = CONCAT(
                    'ST_', 
                    UPPER(SUBSTRING(manufacturer, 1, 3)), 
                    '_', 
                    serial_number, 
                    '_', 
                    DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')
                ) 
                WHERE station_id IS NULL
            ");
        } else {
            // Pour SQLite, utiliser une approche différente
            $chargingPoints = DB::table('charging_points')
                ->whereNull('station_id')
                ->get();
            
            foreach ($chargingPoints as $point) {
                $manufacturer = strtoupper(substr($point->manufacturer ?? 'UNK', 0, 3));
                $timestamp = date('YmdHis');
                $stationId = "ST_{$manufacturer}_{$point->serial_number}_{$timestamp}";
                
                DB::table('charging_points')
                    ->where('id', $point->id)
                    ->update(['station_id' => $stationId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('charging_points', function (Blueprint $table) {
            // Remove the column if you want to rollback
            $table->dropColumn('station_id');
        });
    }
}
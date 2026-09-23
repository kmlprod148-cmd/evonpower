<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mettre à jour les plans existants qui ont max_duration = NULL
        DB::table('pricing_plans')
            ->whereNull('max_duration')
            ->update(['max_duration' => 120]);

        // Mettre à jour les plans avec max_duration = 0
        DB::table('pricing_plans')
            ->where('max_duration', 0)
            ->update(['max_duration' => 120]);

        // Mettre à jour les plans avec max_duration < 20
        DB::table('pricing_plans')
            ->where('max_duration', '<', 20)
            ->update(['max_duration' => 20]);

        // Mettre à jour les plans avec max_duration > 120
        DB::table('pricing_plans')
            ->where('max_duration', '>', 120)
            ->update(['max_duration' => 120]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cette migration ne peut pas être annulée car elle corrige des données
        // qui étaient déjà incorrectes
    }
};

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
        Schema::table('pricing_plans', function (Blueprint $table) {
            // Prix supplémentaires prédéfinis
            $table->decimal('weekend_price', 8, 4)->default(0.0000)->after('activation_fee')->comment('Prix supplémentaire pour les week-ends');
            $table->decimal('night_price', 8, 4)->default(0.0000)->after('weekend_price')->comment('Prix supplémentaire pour les heures de nuit');
            $table->boolean('has_weekend_pricing')->default(false)->after('night_price')->comment('Activer les prix week-end');
            $table->boolean('has_night_pricing')->default(false)->after('has_weekend_pricing')->comment('Activer les prix de nuit');
            $table->time('night_start_time')->default('22:00:00')->after('has_night_pricing')->comment('Heure de début des tarifs de nuit');
            $table->time('night_end_time')->default('06:00:00')->after('night_start_time')->comment('Heure de fin des tarifs de nuit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropColumn([
                'weekend_price',
                'night_price',
                'has_weekend_pricing',
                'has_night_pricing',
                'night_start_time',
                'night_end_time'
            ]);
        });
    }
};

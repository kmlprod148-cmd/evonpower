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
        if (DB::getDriverName() === 'mysql') {
            Schema::table('reservations', function (Blueprint $table) {
                if (!Schema::hasColumn('reservations', 'charging_point_id')) {
                    $table->foreignId('charging_point_id')->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('reservations', 'estimated_duration')) {
                    $table->integer('estimated_duration')->nullable()->comment('Durée estimée en minutes');
                }
                if (!Schema::hasColumn('reservations', 'pricing_plan_id')) {
                    $table->foreignId('pricing_plan_id')->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('reservations', 'reservation_value')) {
                    $table->decimal('reservation_value', 8, 2)->nullable();
                }
                if (!Schema::hasColumn('reservations', 'order_id')) {
                    $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
                }
                if (!Schema::hasColumn('reservations', 'guest_email')) {
                    $table->string('guest_email')->nullable();
                }
                if (!Schema::hasColumn('reservations', 'guest_phone')) {
                    $table->string('guest_phone')->nullable();
                }
            });
        } else {
            // Pour SQLite, on ne peut pas facilement ajouter des clés étrangères après création
            echo "SQLite detected - skipping foreign key additions\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('reservations', function (Blueprint $table) {
                if (Schema::hasColumn('reservations', 'charging_point_id')) {
                    $table->dropForeign(['charging_point_id']);
                    $table->dropColumn('charging_point_id');
                }
                if (Schema::hasColumn('reservations', 'estimated_duration')) {
                    $table->dropColumn('estimated_duration');
                }
                if (Schema::hasColumn('reservations', 'pricing_plan_id')) {
                    $table->dropForeign(['pricing_plan_id']);
                    $table->dropColumn('pricing_plan_id');
                }
                if (Schema::hasColumn('reservations', 'reservation_value')) {
                    $table->dropColumn('reservation_value');
                }
                if (Schema::hasColumn('reservations', 'order_id')) {
                    $table->dropForeign(['order_id']);
                    $table->dropColumn('order_id');
                }
                if (Schema::hasColumn('reservations', 'guest_email')) {
                    $table->dropColumn('guest_email');
                }
                if (Schema::hasColumn('reservations', 'guest_phone')) {
                    $table->dropColumn('guest_phone');
                }
            });
        } else {
            // Pour SQLite, on ne peut pas facilement supprimer les colonnes
            echo "SQLite detected - skipping column removal\n";
        }
    }
};

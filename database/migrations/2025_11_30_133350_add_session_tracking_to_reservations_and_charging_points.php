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
        // Ajouter les colonnes de tracking à reservations
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'charging_session_id')) {
                $table->unsignedBigInteger('charging_session_id')
                    ->nullable()
                    ->after('id')
                    ->comment('ID de la session de recharge active');
            }

            if (!Schema::hasColumn('reservations', 'actual_start_time')) {
                $table->timestamp('actual_start_time')
                    ->nullable()
                    ->after('start_time')
                    ->comment('Heure réelle de début de la charge');
            }

            if (!Schema::hasColumn('reservations', 'actual_end_time')) {
                $table->timestamp('actual_end_time')
                    ->nullable()
                    ->after('end_time')
                    ->comment('Heure réelle de fin de la charge');
            }
        });

        // Ajouter les colonnes de tracking à charging_points
        Schema::table('charging_points', function (Blueprint $table) {
            if (!Schema::hasColumn('charging_points', 'current_session_id')) {
                $table->unsignedBigInteger('current_session_id')
                    ->nullable()
                    ->after('status')
                    ->comment('ID de la session de recharge en cours');
            }

            if (!Schema::hasColumn('charging_points', 'last_session_start')) {
                $table->timestamp('last_session_start')
                    ->nullable()
                    ->after('current_session_id')
                    ->comment('Date/heure du dernier démarrage de session');
            }

            if (!Schema::hasColumn('charging_points', 'last_session_end')) {
                $table->timestamp('last_session_end')
                    ->nullable()
                    ->after('last_session_start')
                    ->comment('Date/heure de la dernière fin de session');
            }

            if (!Schema::hasColumn('charging_points', 'connector_id')) {
                $table->integer('connector_id')
                    ->default(1)
                    ->after('last_session_end')
                    ->comment('ID du connecteur (par défaut 1)');
            }

            if (!Schema::hasColumn('charging_points', 'ocpp_tag')) {
                $table->string('ocpp_tag')
                    ->nullable()
                    ->after('connector_id')
                    ->comment('Tag OCPP par défaut pour cette borne');
            }
        });

        // Ajouter les colonnes OCPP à users si elles n'existent pas
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'ocpp_tag')) {
                $table->string('ocpp_tag')
                    ->nullable()
                    ->unique()
                    ->after('email')
                    ->comment('Tag OCPP unique pour l\'utilisateur');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'charging_session_id')) {
                $table->dropColumn('charging_session_id');
            }
            if (Schema::hasColumn('reservations', 'actual_start_time')) {
                $table->dropColumn('actual_start_time');
            }
            if (Schema::hasColumn('reservations', 'actual_end_time')) {
                $table->dropColumn('actual_end_time');
            }
        });

        Schema::table('charging_points', function (Blueprint $table) {
            $columns = ['current_session_id', 'last_session_start', 'last_session_end', 'connector_id', 'ocpp_tag'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('charging_points', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'ocpp_tag')) {
                $table->dropColumn('ocpp_tag');
            }
        });
    }
};

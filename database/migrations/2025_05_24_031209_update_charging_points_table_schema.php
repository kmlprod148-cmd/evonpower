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
        Schema::table('charging_points', function (Blueprint $table) {
            // Add missing columns with existence checks
            if (!Schema::hasColumn('charging_points', 'power_output')) {
                $table->decimal('power_output', 10, 2)->nullable()->after('country');
            }
            if (!Schema::hasColumn('charging_points', 'connector_type')) {
                $table->string('connector_type')->nullable()->after('power_output');
            }
            if (!Schema::hasColumn('charging_points', 'connection_type')) {
                $table->string('connection_type')->nullable()->after('connector_type');
            }
            if (!Schema::hasColumn('charging_points', 'qr_code_path')) {
                $table->string('qr_code_path')->nullable()->after('qr_code');
            }
            if (!Schema::hasColumn('charging_points', 'qr_code_generated_at')) {
                $table->timestamp('qr_code_generated_at')->nullable()->after('qr_code_path');
            }

            // Update existing columns with existence checks
            if (Schema::hasColumn('charging_points', 'longitude')) {
                if (DB::getDriverName() === 'mysql') {
                    // Change longitude precision for MySQL
                    $table->decimal('longitude', 12, 8)->change(); // Increased precision for longitude
                }
                // Pour SQLite, on ne peut pas facilement modifier la précision des colonnes
            }

            // Modify status column - Drop and re-add with new enum values
            if (Schema::hasColumn('charging_points', 'status')) {
                if (DB::getDriverName() === 'sqlite') {
                    // Pour SQLite, on ne peut pas facilement supprimer une colonne avec un index
                    // On va simplement ignorer cette modification
                    echo "SQLite detected - skipping status column modification\n";
                } else {
                    $table->dropColumn('status');
                }
            }
            // Add the new status column. Position it logiquement
            if (!Schema::hasColumn('charging_points', 'status')) {
                if (Schema::hasColumn('charging_points', 'firmware_version')) {
                    if (DB::getDriverName() === 'sqlite') {
                        $table->string('status')->default('offline')->after('firmware_version');
                    } else {
                        $table->enum('status', ['online', 'offline', 'maintenance', 'error'])->default('offline')->after('firmware_version');
                    }
                } else {
                    if (DB::getDriverName() === 'sqlite') {
                        $table->string('status')->default('offline');
                    } else {
                        $table->enum('status', ['online', 'offline', 'maintenance', 'error'])->default('offline');
                    }
                }
            }


            // Remove extra columns if they exist (MySQL only)
            if (DB::getDriverName() === 'mysql') {
                $extraColumns = [
                    'external_id', 'timezone', 'installation_notes', 'access_code',
                    'access_control', 'ip_address', 'mac_address', 'sim_card_number',
                    'communication_protocol_version', 'commission_rate', 'contract_reference',
                    'capabilities', 'smart_charging_profile',
                    'load_balancing_settings', 'total_energy_delivered', 'total_charging_sessions',
                    'last_connection', 'last_status_update', 'last_used_at', 'notes', 'metadata', 'evse_id'
                ];
                foreach ($extraColumns as $column) {
                    if (Schema::hasColumn('charging_points', $column)) {
                        $table->dropColumn($column);
                    }
                }

                // Remove softDeletes if it exists
                if (Schema::hasColumn('charging_points', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            } else {
                echo "SQLite detected - skipping column removal to avoid index conflicts\n";
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            // Drop added columns with existence checks
            if (Schema::hasColumn('charging_points', 'qr_code_generated_at')) {
                $table->dropColumn('qr_code_generated_at');
            }
            if (Schema::hasColumn('charging_points', 'qr_code_path')) {
                $table->dropColumn('qr_code_path');
            }
            if (Schema::hasColumn('charging_points', 'connection_type')) {
                $table->dropColumn('connection_type');
            }
            if (Schema::hasColumn('charging_points', 'connector_type')) {
                $table->dropColumn('connector_type');
            }
            if (Schema::hasColumn('charging_points', 'power_output')) {
                $table->dropColumn('power_output');
            }

            // Revert status column - Drop and re-add with original enum values
            if (Schema::hasColumn('charging_points', 'status')) {
                 $table->dropColumn('status');
            }
            // Add the old status column. Position it logiquement
            if (!Schema::hasColumn('charging_points', 'status')) {
                if (Schema::hasColumn('charging_points', 'firmware_version')) {
                    $table->enum('status', ['online', 'offline', 'charging', 'error', 'maintenance', 'reserved'])->default('offline')->after('firmware_version');
                } else {
                    $table->enum('status', ['online', 'offline', 'charging', 'error', 'maintenance', 'reserved'])->default('offline');
                }
            }

            if (Schema::hasColumn('charging_points', 'longitude')) {
                // Revert longitude precision (assuming original was 11, 8)
                $table->decimal('longitude', 11, 8)->change();
            }


            // Add back removed columns if needed (based on previous schema)
             $removedColumns = [
                'external_id', 'timezone', 'installation_notes', 'access_code',
                'access_control', 'ip_address', 'mac_address', 'sim_card_number',
                'communication_protocol_version', 'commission_rate', 'contract_reference',
                'configuration', 'capabilities', 'smart_charging_profile',
                'load_balancing_settings', 'total_energy_delivered', 'total_charging_sessions',
                'last_connection', 'last_status_update', 'last_used_at', 'notes', 'metadata'
            ];
            foreach ($removedColumns as $column) {
                if (!Schema::hasColumn('charging_points', $column)) {
                    // This is a simplified approach; actual column types and positions would be needed
                    // For now, just add them back as nullable strings.
                    $table->string($column)->nullable();
                }
            }

            // Add back softDeletes if it was dropped
            if (!Schema::hasColumn('charging_points', 'deleted_at')) {
                 $table->softDeletes();
            }
        });
    }
};

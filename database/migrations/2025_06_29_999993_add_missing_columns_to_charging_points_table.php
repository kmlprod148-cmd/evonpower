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
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('charging_points', 'status')) {
                $table->enum('status', ['online', 'offline', 'maintenance', 'error'])
                      ->default('offline')
                      ->after('model'); // Adjust the position as needed
            }

            // Add public_access column if it doesn't exist
            if (!Schema::hasColumn('charging_points', 'public_access')) {
                $table->boolean('public_access')->default(false)->after('status');
            }

            // Add other commonly needed columns that might be missing
            if (!Schema::hasColumn('charging_points', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('public_access');
            }

            if (!Schema::hasColumn('charging_points', 'access_type')) {
                $table->enum('access_type', ['public', 'private', 'restricted'])
                      ->default('public')
                      ->after('is_active');
            }

            // Add soft deletes if not already present
            if (!Schema::hasColumn('charging_points', 'deleted_at')) {
                $table->softDeletes();
            }

            // Add commonly used location fields if missing
            if (!Schema::hasColumn('charging_points', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('country');
            }

            if (!Schema::hasColumn('charging_points', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }

            // Add power output if missing
            if (!Schema::hasColumn('charging_points', 'power_output')) {
                $table->decimal('power_output', 8, 2)->nullable()->after('longitude');
            }

            // Add QR code fields if missing
            if (!Schema::hasColumn('charging_points', 'qr_code')) {
                $table->text('qr_code')->nullable()->after('power_output');
            }

            if (!Schema::hasColumn('charging_points', 'qr_code_path')) {
                $table->string('qr_code_path')->nullable()->after('qr_code');
            }

            if (!Schema::hasColumn('charging_points', 'qr_code_generated_at')) {
                $table->timestamp('qr_code_generated_at')->nullable()->after('qr_code_path');
            }

            // Add maintenance dates if missing
            if (!Schema::hasColumn('charging_points', 'last_maintenance_date')) {
                $table->date('last_maintenance_date')->nullable()->after('qr_code_generated_at');
            }

            if (!Schema::hasColumn('charging_points', 'next_maintenance_date')) {
                $table->date('next_maintenance_date')->nullable()->after('last_maintenance_date');
            }

            // Add installation date if missing
            if (!Schema::hasColumn('charging_points', 'installation_date')) {
                $table->date('installation_date')->nullable()->after('next_maintenance_date');
            }

            // Add technical fields if missing
            if (!Schema::hasColumn('charging_points', 'firmware_version')) {
                $table->string('firmware_version', 100)->nullable()->after('installation_date');
            }

            if (!Schema::hasColumn('charging_points', 'communication_protocol')) {
                $table->string('communication_protocol', 100)->nullable()->after('firmware_version');
            }

            if (!Schema::hasColumn('charging_points', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('communication_protocol');
            }

            if (!Schema::hasColumn('charging_points', 'mac_address')) {
                $table->string('mac_address', 17)->nullable()->after('ip_address');
            }

            // Add access code if missing
            if (!Schema::hasColumn('charging_points', 'access_code')) {
                $table->string('access_code', 100)->nullable()->after('mac_address');
            }

            // Add description if missing
            if (!Schema::hasColumn('charging_points', 'description')) {
                $table->text('description')->nullable()->after('access_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charging_points', function (Blueprint $table) {
            // Remove columns added in this migration
            $columnsToRemove = [
                'status',
                'public_access',
                'is_active',
                'access_type',
                'deleted_at',
                'latitude',
                'longitude',
                'power_output',
                'qr_code',
                'qr_code_path',
                'qr_code_generated_at',
                'last_maintenance_date',
                'next_maintenance_date',
                'installation_date',
                'firmware_version',
                'communication_protocol',
                'ip_address',
                'mac_address',
                'access_code',
                'description'
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('charging_points', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
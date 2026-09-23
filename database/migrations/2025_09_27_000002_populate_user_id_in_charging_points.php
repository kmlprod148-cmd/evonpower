<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Populate user_id for existing charging points based on integrator/partner relationships
     */
    public function up(): void
    {
        // First, try to populate user_id based on integrator_id
        $deletedAtCondition = Schema::hasColumn('users', 'deleted_at') ? 'AND users.deleted_at IS NULL' : '';
        DB::statement("
            UPDATE charging_points
            SET user_id = (
                SELECT users.id
                FROM users
                WHERE users.integrator_id = charging_points.integrator_id
                {$deletedAtCondition}
                ORDER BY users.created_at ASC
                LIMIT 1
            )
            WHERE charging_points.user_id IS NULL
            AND charging_points.integrator_id IS NOT NULL
        ");

        // Then, try to populate user_id based on partner_id for remaining null values
        $deletedAtCondition = Schema::hasColumn('users', 'deleted_at') ? 'AND users.deleted_at IS NULL' : '';
        DB::statement("
            UPDATE charging_points
            SET user_id = (
                SELECT users.id
                FROM users
                WHERE users.partner_id = charging_points.partner_id
                {$deletedAtCondition}
                ORDER BY users.created_at ASC
                LIMIT 1
            )
            WHERE charging_points.user_id IS NULL
            AND charging_points.partner_id IS NOT NULL
        ");

        // For remaining charging points without user_id, assign to the first admin user
        $firstAdminQuery = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'admin')
            ->where('model_has_roles.model_type', 'App\\Models\\User');

        if (Schema::hasColumn('users', 'deleted_at')) {
            $firstAdminQuery->whereNull('users.deleted_at');
        }

        $firstAdmin = $firstAdminQuery->orderBy('users.created_at', 'ASC')->first();

        if ($firstAdmin) {
            DB::table('charging_points')
                ->whereNull('user_id')
                ->update(['user_id' => $firstAdmin->id]);
        }

        // Log the migration results
        $totalChargingPoints = DB::table('charging_points')->count();
        $populatedChargingPoints = DB::table('charging_points')->whereNotNull('user_id')->count();
        
        \Log::info('Charging points user_id population completed', [
            'total_charging_points' => $totalChargingPoints,
            'populated_charging_points' => $populatedChargingPoints,
            'unpopulated_charging_points' => $totalChargingPoints - $populatedChargingPoints
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all user_id values to null
        DB::table('charging_points')->update(['user_id' => null]);
    }
};

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
        if (DB::getDriverName() === 'mysql') {
            // Fix charging points with missing integrator_id
            $chargingPoints = DB::table('charging_points')
                ->whereNull('integrator_id')
                ->orWhere('integrator_id', '')
                ->get();

            $integrator = DB::table('integrators')->first();
            
            if ($integrator && $chargingPoints->count() > 0) {
                echo "Fixing " . $chargingPoints->count() . " charging points with missing integrator_id\n";
                
                foreach ($chargingPoints as $cp) {
                    DB::table('charging_points')
                        ->where('id', $cp->id)
                        ->update(['integrator_id' => $integrator->id]);
                }
            }
        } else {
            echo "SQLite detected - skipping charging points fix to avoid foreign key conflicts\n";
        }

        // Create test transactions if none exist
        $transactionCount = DB::table('transactions')->count();
        
        if ($transactionCount === 0) {
            echo "Creating test transactions...\n";
            
            // Get required data
            $user = DB::table('users')->first();
            $integrator = DB::table('integrators')->first();
            $chargingPoint = DB::table('charging_points')
                ->whereNotNull('integrator_id')
                ->where('integrator_id', '>', 0)
                ->first();
            $businessProfile = DB::table('business_profiles')->first();
            
            if ($user && $integrator && $chargingPoint && $businessProfile) {
                // Transaction 1: Client transaction
                DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'charging_point_id' => $chargingPoint->id,
                    'integrator_id' => $integrator->id,
                    'business_profile_id' => $businessProfile->id,
                    'transaction_type' => 'client',
                    'transaction_category' => 'charge',
                    'status' => 'completed',
                    'amount' => 25.50,
                    'price_total' => 25.50,
                    'currency' => 'EUR',
                    'admin_commission' => 2.55, // 10%
                    'integrator_commission' => 1.28, // 5%
                    'partner_commission' => 0.64, // 2.5%
                    'description' => 'Test client transaction',
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2),
                ]);
                
                // Transaction 2: Another client transaction
                DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'charging_point_id' => $chargingPoint->id,
                    'integrator_id' => $integrator->id,
                    'business_profile_id' => $businessProfile->id,
                    'transaction_type' => 'client',
                    'transaction_category' => 'charge',
                    'status' => 'completed',
                    'amount' => 18.75,
                    'price_total' => 18.75,
                    'currency' => 'EUR',
                    'admin_commission' => 1.88, // 10%
                    'integrator_commission' => 0.94, // 5%
                    'partner_commission' => 0.47, // 2.5%
                    'description' => 'Test client transaction 2',
                    'created_at' => now()->subDays(1),
                    'updated_at' => now()->subDays(1),
                ]);
                
                // Transaction 3: Admin transaction
                DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'charging_point_id' => $chargingPoint->id,
                    'integrator_id' => $integrator->id,
                    'business_profile_id' => $businessProfile->id,
                    'transaction_type' => 'admin',
                    'transaction_category' => 'debit',
                    'status' => 'completed',
                    'amount' => 5.00,
                    'price_total' => 5.00,
                    'currency' => 'EUR',
                    'admin_commission' => 0,
                    'integrator_commission' => 0,
                    'partner_commission' => 0,
                    'description' => 'Test admin transaction',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                echo "Created 3 test transactions\n";
            } else {
                echo "Missing required data for creating test transactions\n";
            }
        } else {
            echo "Transactions already exist: " . $transactionCount . "\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove test transactions
        DB::table('transactions')->where('description', 'LIKE', 'Test%')->delete();
    }
};

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use App\Models\ChargingPoint;
use App\Models\Group;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\Transaction;
use Carbon\Carbon;

class HierarchicalDemoDataSeeder_2025_09_27 extends Seeder
{
    public function run()
    {
        $this->command->info('🚀 Génération des données de démonstration hiérarchiques - ' . date('Y-m-d'));
        
        DB::beginTransaction();
        
        try {
            $today = date('Y-m-d');
            
            // 1. Create hierarchical users
            $users = $this->createUsers($today);
            
            // 2. Create business profiles with hierarchical fees
            $businessProfiles = $this->createBusinessProfiles($users, $today);
            
            // 3. Create pricing plans
            $pricingPlans = $this->createPricingPlans($users, $today);
            
            // 4. Create infrastructure
            $infrastructure = $this->createInfrastructure($users, $today);
            
            // 5. Create charging points
            $chargingPoints = $this->createChargingPoints($users, $businessProfiles, $pricingPlans, $infrastructure, $today);
            
            // 6. Generate reservations
            $this->generateReservations($chargingPoints, $users, $businessProfiles, $today);
            
            DB::commit();
            
            $this->command->info('✅ Données générées avec succès !');
            $this->displaySummary($users, $businessProfiles, $chargingPoints);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Erreur : ' . $e->getMessage());
            throw $e;
        }
    }

    private function createUsers($today)
    {
        // Admin user
        $admin = User::create([
            'name' => "Admin Demo {$today}",
            'email' => "admin.demo.{$today}@evon.ma",
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'balance' => 0.00,
            'currency' => 'EUR',
        ]);
        $admin->assignRole('admin');
        
        // Integrator entity
        $integrator = Integrator::create([
            'name' => "Integrator Demo {$today}",
            'description' => 'Intégrateur de démonstration',
            'contact_email' => "integrator.demo.{$today}@evon.ma",
            'status' => 'active',
        ]);
        
        // Integrator user
        $integratorUser = User::create([
            'name' => "Integrator User Demo {$today}",
            'email' => "integrator.user.demo.{$today}@evon.ma",
            'password' => Hash::make('password123'),
            'role' => 'integrator',
            'integrator_id' => $integrator->id,
            'manager_admin_id' => $admin->id,
            'balance' => 0.00,
            'currency' => 'EUR',
        ]);
        $integratorUser->assignRole('integrator');
        
        // Partner
        $partner = Partner::create([
            'name' => "Partner Demo {$today}",
            'integrator_id' => $integrator->id,
            'description' => 'Partenaire de démonstration',
            'contact_email' => "partner.demo.{$today}@evon.ma",
            'status' => 'active',
        ]);
        
        // Operator user
        $operatorUser = User::create([
            'name' => "Operator Demo {$today}",
            'email' => "operator.demo.{$today}@evon.ma",
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'integrator_id' => $integrator->id,
            'partner_id' => $partner->id,
            'manager_integrator_id' => $integratorUser->id,
            'moderator_admin_id' => $admin->id,
            'balance' => 0.00,
            'currency' => 'EUR',
        ]);
        $operatorUser->assignRole('operator');
        
        return [
            'admin' => $admin,
            'integrator_entity' => $integrator,
            'integrator_user' => $integratorUser,
            'partner' => $partner,
            'operator' => $operatorUser,
        ];
    }

    private function createBusinessProfiles($users, $today)
    {
        // Admin Business Profile
        $adminProfile = BusinessProfile::create([
            'name' => "Admin Profile Demo {$today}",
            'description' => 'Profil administrateur avec frais de base',
            'user_id' => $users['admin']->id,
            'is_active' => true,
            'currency' => 'EUR',
            'admin_fixed_fee' => 0.50,
            'admin_percentage_fee' => 2.0,
            'admin_commission_rate' => 1.5,
            'fee_config' => json_encode([
                'admin_fees' => ['fixed_fee' => 0.50, 'percentage_fee' => 2.0],
                'fee_structure' => 'hierarchical_root',
            ]),
        ]);

        // Integrator Business Profile
        $integratorProfile = BusinessProfile::create([
            'name' => "Integrator Profile Demo {$today}",
            'description' => 'Profil intégrateur avec frais intermédiaires',
            'user_id' => $users['integrator_user']->id,
            'integrator_id' => $users['integrator_entity']->id,
            'is_active' => true,
            'currency' => 'EUR',
            'integrator_fixed_fee' => 0.30,
            'integrator_percentage_fee' => 1.5,
            'partner_commission_rate' => 1.0,
            'fee_config' => json_encode([
                'integrator_fees' => ['fixed_fee' => 0.30, 'percentage_fee' => 1.5],
                'fee_structure' => 'hierarchical_intermediate',
            ]),
        ]);

        // Operator Business Profile
        $operatorProfile = BusinessProfile::create([
            'name' => "Operator Profile Demo {$today}",
            'description' => 'Profil opérateur avec héritage hiérarchique',
            'user_id' => $users['operator']->id,
            'integrator_id' => $users['integrator_entity']->id,
            'partner_id' => $users['partner']->id,
            'is_active' => true,
            'currency' => 'EUR',
            'operator_percentage_fee' => 95.0,
            'service_fee' => 0.10,
            'fee_config' => json_encode([
                'hierarchical_fees' => [
                    'admin_fixed_fee' => 0.50,
                    'admin_percentage_fee' => 2.0,
                    'integrator_fixed_fee' => 0.30,
                    'integrator_percentage_fee' => 1.5,
                    'operator_percentage_fee' => 95.0,
                ],
                'fee_structure' => 'hierarchical_leaf',
            ]),
        ]);

        return [
            'admin' => $adminProfile,
            'integrator' => $integratorProfile,
            'operator' => $operatorProfile,
        ];
    }

    private function createPricingPlans($users, $today)
    {
        $energyPlan = PricingPlan::create([
            'name' => "Plan Énergie Demo {$today}",
            'description' => 'Tarification basée sur l\'énergie (kWh)',
            'rate_type' => 'kwh',
            'price_per_kwh' => 0.25,
            'activation_fee' => 0.50,
            'currency' => 'EUR',
            'max_duration' => 480,
            'is_active' => true,
            'user_id' => $users['integrator_user']->id,
            'integrator_id' => $users['integrator_entity']->id,
        ]);

        $timePlan = PricingPlan::create([
            'name' => "Plan Temps Demo {$today}",
            'description' => 'Tarification basée sur le temps (minutes)',
            'rate_type' => 'minute',
            'price_per_minute' => 0.15,
            'base_rate' => 1.00,
            'activation_fee' => 0.30,
            'currency' => 'EUR',
            'max_duration' => 240,
            'is_active' => true,
            'user_id' => $users['integrator_user']->id,
            'integrator_id' => $users['integrator_entity']->id,
        ]);

        return ['energy' => $energyPlan, 'time' => $timePlan];
    }

    private function createInfrastructure($users, $today)
    {
        $group = Group::create([
            'name' => "Groupe Demo {$today}",
            'description' => 'Groupe de démonstration',
            'partner_id' => $users['partner']->id,
            'city' => 'Casablanca',
            'address' => 'Avenue Mohammed V',
            'postal_code' => '20000',
            'country' => 'Maroc',
            'is_active' => true,
        ]);

        return ['group' => $group];
    }

    private function createChargingPoints($users, $businessProfiles, $pricingPlans, $infrastructure, $today)
    {
        $fastChargingPoint = ChargingPoint::create([
            'name' => "Borne Rapide Demo {$today}",
            'location' => 'Station Casablanca Centre',
            'address' => 'Boulevard Zerktouni',
            'city' => 'Casablanca',
            'status' => 'online',
            'manufacturer' => 'ABB',
            'model' => 'Terra 54 CJG',
            'power_output' => 50.0,
            'serial_number' => "ABB-DEMO-{$today}-001",
            'connection_type' => 'ethernet',
            'communication_protocol' => 'ocpp16',
            'authentication_required' => true,
            'access_type' => 'public',
            'user_id' => $users['operator']->id,
            'integrator_id' => $users['integrator_entity']->id,
            'partner_id' => $users['partner']->id,
            'group_id' => $infrastructure['group']->id,
            'pricing_plan_id' => $pricingPlans['energy']->id,
            'business_profile_id' => $businessProfiles['operator']->id,
        ]);

        $standardChargingPoint = ChargingPoint::create([
            'name' => "Borne Standard Demo {$today}",
            'location' => 'Parking Mall Casablanca',
            'address' => 'Avenue des FAR',
            'city' => 'Casablanca',
            'status' => 'online',
            'manufacturer' => 'Schneider Electric',
            'model' => 'EVlink Pro AC',
            'power_output' => 22.0,
            'serial_number' => "SE-DEMO-{$today}-002",
            'connection_type' => 'wifi',
            'communication_protocol' => 'ocpp16',
            'authentication_required' => true,
            'access_type' => 'public',
            'user_id' => $users['operator']->id,
            'integrator_id' => $users['integrator_entity']->id,
            'partner_id' => $users['partner']->id,
            'group_id' => $infrastructure['group']->id,
            'pricing_plan_id' => $pricingPlans['time']->id,
            'business_profile_id' => $businessProfiles['operator']->id,
        ]);

        return ['fast' => $fastChargingPoint, 'standard' => $standardChargingPoint];
    }

    private function generateReservations($chargingPoints, $users, $businessProfiles, $today)
    {
        // Energy reservation (25 kWh)
        $energyAmount = 25.0;
        $pricePerKwh = 0.25;
        $baseAmount = $energyAmount * $pricePerKwh; // 6.25€
        $totalAmount = $baseAmount + 0.50; // + activation fee = 6.75€
        
        $adminFee = 0.50 + ($totalAmount * 0.02); // Fixed + 2%
        $integratorFee = 0.30 + ($totalAmount * 0.015); // Fixed + 1.5%
        $operatorAmount = $totalAmount - $adminFee - $integratorFee;
        
        $energyTransaction = Transaction::create([
            'user_id' => $users['operator']->id,
            'charging_point_id' => $chargingPoints['fast']->id,
            'type' => 'reservation',
            'amount' => $totalAmount,
            'currency' => 'EUR',
            'status' => 'completed',
            'description' => "Réservation énergie 25kWh - Demo {$today}",
            'fee_breakdown' => json_encode([
                'base_amount' => $baseAmount,
                'total_amount' => $totalAmount,
                'admin_fee' => $adminFee,
                'integrator_fee' => $integratorFee,
                'operator_amount' => $operatorAmount,
            ]),
            'business_profile_id' => $businessProfiles['operator']->id,
        ]);

        // Time reservation (120 minutes)
        $timeMinutes = 120;
        $pricePerMinute = 0.15;
        $baseAmount = $timeMinutes * $pricePerMinute; // 18.00€
        $totalAmount = $baseAmount + 1.00 + 0.30; // + base fee + activation = 19.30€
        
        $adminFee = 0.50 + ($totalAmount * 0.02);
        $integratorFee = 0.30 + ($totalAmount * 0.015);
        $operatorAmount = $totalAmount - $adminFee - $integratorFee;
        
        $timeTransaction = Transaction::create([
            'user_id' => $users['operator']->id,
            'charging_point_id' => $chargingPoints['standard']->id,
            'type' => 'reservation',
            'amount' => $totalAmount,
            'currency' => 'EUR',
            'status' => 'completed',
            'description' => "Réservation temps 120min - Demo {$today}",
            'fee_breakdown' => json_encode([
                'base_amount' => $baseAmount,
                'total_amount' => $totalAmount,
                'admin_fee' => $adminFee,
                'integrator_fee' => $integratorFee,
                'operator_amount' => $operatorAmount,
            ]),
            'business_profile_id' => $businessProfiles['operator']->id,
        ]);

        // Create individual transactions for hierarchy
        $this->createHierarchicalTransactions($energyTransaction, $users, $adminFee, $integratorFee, $operatorAmount, 'energy');
        $this->createHierarchicalTransactions($timeTransaction, $users, $adminFee, $integratorFee, $operatorAmount, 'time');

        // Update balances
        $users['admin']->increment('balance', $adminFee * 2);
        $users['integrator_user']->increment('balance', $integratorFee * 2);
        $users['operator']->increment('balance', $operatorAmount * 2);
    }

    private function createHierarchicalTransactions($mainTransaction, $users, $adminFee, $integratorFee, $operatorAmount, $type)
    {
        Transaction::create([
            'user_id' => $users['admin']->id,
            'charging_point_id' => $mainTransaction->charging_point_id,
            'parent_transaction_id' => $mainTransaction->id,
            'type' => 'admin_revenue',
            'amount' => $adminFee,
            'currency' => 'EUR',
            'status' => 'completed',
            'description' => "Commission Admin - {$type}",
        ]);

        Transaction::create([
            'user_id' => $users['integrator_user']->id,
            'charging_point_id' => $mainTransaction->charging_point_id,
            'parent_transaction_id' => $mainTransaction->id,
            'type' => 'integrator_revenue',
            'amount' => $integratorFee,
            'currency' => 'EUR',
            'status' => 'completed',
            'description' => "Commission Intégrateur - {$type}",
        ]);

        Transaction::create([
            'user_id' => $users['operator']->id,
            'charging_point_id' => $mainTransaction->charging_point_id,
            'parent_transaction_id' => $mainTransaction->id,
            'type' => 'operator_revenue',
            'amount' => $operatorAmount,
            'currency' => 'EUR',
            'status' => 'completed',
            'description' => "Revenus Opérateur - {$type}",
        ]);
    }

    private function displaySummary($users, $businessProfiles, $chargingPoints)
    {
        $this->command->info("\n📊 RÉSUMÉ DES DONNÉES GÉNÉRÉES:");
        $this->command->info("👥 Utilisateurs: " . count($users));
        $this->command->info("💼 Profils d'affaires: " . count($businessProfiles));
        $this->command->info("⚡ Bornes de recharge: " . count($chargingPoints));
        $this->command->info("💰 Réservations: 2 (énergie + temps)");
        $this->command->info("\n🔗 HIÉRARCHIE:");
        $this->command->info("Admin → Intégrateur → Opérateur");
        $this->command->info("Frais distribués selon la hiérarchie");
    }
}

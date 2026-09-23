<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Group;
use App\Models\Integrator;
use App\Models\Operator;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use App\Models\ChargingPoint;
use App\Models\Station;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Désactiver les contraintes de clés étrangères temporairement (database-agnostic)
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        // Nettoyer les données existantes
        $this->cleanupExistingData();

        // Créer les données de base
        $this->createGroups();
        $this->createIntegrators();
        $this->createOperators();
        $this->createBusinessProfiles();
        $this->createPricingPlans();
        $this->createChargingPoints();
        $this->createDemoTransactions();

        // Réactiver les contraintes (database-agnostic)
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->command->info('✅ Données de démonstration créées avec succès !');
        $this->command->info('📊 Résumé :');
        $this->command->info('   - 2 Groupes créés');
        $this->command->info('   - 2 Intégrateurs créés');
        $this->command->info('   - 2 Opérateurs créés');
        $this->command->info('   - 4 Profils Business créés');
        $this->command->info('   - 4 Plans Tarifaires créés');
        $this->command->info('   - 4 Bornes de Recharge créées');
        $this->command->info('   - 10 Transactions de démonstration créées');
    }

    private function cleanupExistingData(): void
    {
        $this->command->info('🧹 Nettoyage des données existantes...');
        
        $driver = DB::getDriverName();
        
        // Supprimer dans l'ordre pour éviter les erreurs de clés étrangères (database-agnostic)
        $tables = [
            'transactions',
            'charging_points',
            'stations',
            'pricing_plans',
            'business_profiles',
            'operators',
            'integrators',
            'groups',
            'accounts'
        ];
        
        foreach ($tables as $table) {
            if ($driver === 'sqlite') {
                DB::table($table)->delete();
            } else {
                DB::table($table)->truncate();
            }
        }
        
        // Supprimer les utilisateurs de démo (garder les admins)
        User::where('email', 'like', '%demo%')->delete();
    }

    private function createGroups(): void
    {
        $this->command->info('🏢 Création des groupes...');
        
        $groups = [
            [
                'name' => 'Groupe Premium',
                'description' => 'Groupe pour les clients premium avec des services exclusifs',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Groupe Standard',
                'description' => 'Groupe pour les clients standard avec services de base',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($groups as $groupData) {
            Group::create($groupData);
        }
    }

    private function createIntegrators(): void
    {
        $this->command->info('🔧 Création des intégrateurs...');
        
        $integrators = [
            [
                'name' => 'TechIntegrateur Pro',
                'email' => 'contact@techintegrator-pro.com',
                'phone' => '+33 1 23 45 67 89',
                'address' => '123 Rue de la Tech, 75001 Paris',
                'status' => 'active',
                'commission_rate' => 15.0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'SmartCharge Solutions',
                'email' => 'info@smartcharge-solutions.com',
                'phone' => '+33 1 98 76 54 32',
                'address' => '456 Avenue des Solutions, 69000 Lyon',
                'status' => 'active',
                'commission_rate' => 12.5,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($integrators as $integratorData) {
            Integrator::create($integratorData);
        }
    }

    private function createOperators(): void
    {
        $this->command->info('⚡ Création des opérateurs...');
        
        $operators = [
            [
                'name' => 'PowerGrid France',
                'email' => 'contact@powergrid-france.com',
                'phone' => '+33 1 11 22 33 44',
                'address' => '789 Boulevard de l\'Énergie, 13000 Marseille',
                'status' => 'active',
                'commission_rate' => 8.0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'EcoCharge Network',
                'email' => 'info@ecocharge-network.com',
                'phone' => '+33 1 55 66 77 88',
                'address' => '321 Rue Écologique, 44000 Nantes',
                'status' => 'active',
                'commission_rate' => 10.0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($operators as $operatorData) {
            Operator::create($operatorData);
        }
    }

    private function createBusinessProfiles(): void
    {
        $this->command->info('💼 Création des profils business...');
        
        $businessProfiles = [
            [
                'name' => 'Premium Business Profile',
                'description' => 'Profil premium avec frais élevés et services exclusifs',
                'base_fee_amount' => 15.00,
                'charge_fee_config' => json_encode([
                    'fixed_amount' => 2.50,
                    'percentage' => 5.0
                ]),
                'admin_fee_fixed' => 1.50,
                'admin_fee_percentage' => 2.0,
                'integrator_fee_fixed' => 2.00,
                'integrator_fee_percentage' => 3.0,
                'partner_fee_fixed' => 1.00,
                'partner_fee_percentage' => 1.5,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Standard Business Profile',
                'description' => 'Profil standard avec frais modérés',
                'base_fee_amount' => 8.00,
                'charge_fee_config' => json_encode([
                    'fixed_amount' => 1.50,
                    'percentage' => 3.5
                ]),
                'admin_fee_fixed' => 0.75,
                'admin_fee_percentage' => 1.5,
                'integrator_fee_fixed' => 1.25,
                'integrator_fee_percentage' => 2.5,
                'partner_fee_fixed' => 0.50,
                'partner_fee_percentage' => 1.0,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Economy Business Profile',
                'description' => 'Profil économique avec frais réduits',
                'base_fee_amount' => 5.00,
                'charge_fee_config' => json_encode([
                    'fixed_amount' => 1.00,
                    'percentage' => 2.5
                ]),
                'admin_fee_fixed' => 0.50,
                'admin_fee_percentage' => 1.0,
                'integrator_fee_fixed' => 1.00,
                'integrator_fee_percentage' => 2.0,
                'partner_fee_fixed' => 0.25,
                'partner_fee_percentage' => 0.5,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Enterprise Business Profile',
                'description' => 'Profil entreprise avec frais personnalisés',
                'base_fee_amount' => 25.00,
                'charge_fee_config' => json_encode([
                    'fixed_amount' => 3.00,
                    'percentage' => 4.0
                ]),
                'admin_fee_fixed' => 2.00,
                'admin_fee_percentage' => 2.5,
                'integrator_fee_fixed' => 2.50,
                'integrator_fee_percentage' => 3.5,
                'partner_fee_fixed' => 1.50,
                'partner_fee_percentage' => 2.0,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($businessProfiles as $profileData) {
            BusinessProfile::create($profileData);
        }
    }

    private function createPricingPlans(): void
    {
        $this->command->info('💰 Création des plans tarifaires...');
        
        $pricingPlans = [
            [
                'name' => 'Plan Premium',
                'description' => 'Plan premium avec tarifs élevés et services exclusifs',
                'price_per_kwh' => 0.45,
                'activation_fee' => 5.00,
                'minimum_charge' => 2.00,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Plan Standard',
                'description' => 'Plan standard avec tarifs compétitifs',
                'price_per_kwh' => 0.35,
                'activation_fee' => 3.00,
                'minimum_charge' => 1.50,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Plan Économique',
                'description' => 'Plan économique avec tarifs réduits',
                'price_per_kwh' => 0.25,
                'activation_fee' => 2.00,
                'minimum_charge' => 1.00,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Plan Entreprise',
                'description' => 'Plan entreprise avec tarifs personnalisés',
                'price_per_kwh' => 0.40,
                'activation_fee' => 8.00,
                'minimum_charge' => 3.00,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($pricingPlans as $planData) {
            PricingPlan::create($planData);
        }
    }

    private function createChargingPoints(): void
    {
        $this->command->info('🔌 Création des bornes de recharge...');
        
        // Créer d'abord les stations
        $stations = [
            [
                'name' => 'Station Premium Paris',
                'address' => '1 Avenue des Champs-Élysées, 75008 Paris',
                'latitude' => 48.8698,
                'longitude' => 2.3077,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Station Standard Lyon',
                'address' => '10 Place Bellecour, 69002 Lyon',
                'latitude' => 45.7578,
                'longitude' => 4.8320,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Station Économique Marseille',
                'address' => '5 Quai des Belges, 13001 Marseille',
                'latitude' => 43.2965,
                'longitude' => 5.3698,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Station Entreprise Nantes',
                'address' => '15 Place du Commerce, 44000 Nantes',
                'latitude' => 47.2184,
                'longitude' => -1.5536,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($stations as $stationData) {
            Station::create($stationData);
        }

        // Créer les bornes de recharge
        $chargingPoints = [
            [
                'name' => 'Borne Premium Paris',
                'station_id' => 1,
                'business_profile_id' => 1, // Premium Business Profile
                'pricing_plan_id' => 1, // Plan Premium
                'integrator_id' => 1, // TechIntegrateur Pro
                'operator_id' => 1, // PowerGrid France
                'group_id' => 1, // Groupe Premium
                'status' => 'active',
                'power_output' => 50.0,
                'connector_type' => 'Type 2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Borne Standard Lyon',
                'station_id' => 2,
                'business_profile_id' => 2, // Standard Business Profile
                'pricing_plan_id' => 2, // Plan Standard
                'integrator_id' => 2, // SmartCharge Solutions
                'operator_id' => 1, // PowerGrid France
                'group_id' => 2, // Groupe Standard
                'status' => 'active',
                'power_output' => 22.0,
                'connector_type' => 'Type 2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Borne Économique Marseille',
                'station_id' => 3,
                'business_profile_id' => 3, // Economy Business Profile
                'pricing_plan_id' => 3, // Plan Économique
                'integrator_id' => 1, // TechIntegrateur Pro
                'operator_id' => 2, // EcoCharge Network
                'group_id' => 2, // Groupe Standard
                'status' => 'active',
                'power_output' => 11.0,
                'connector_type' => 'Type 2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Borne Entreprise Nantes',
                'station_id' => 4,
                'business_profile_id' => 4, // Enterprise Business Profile
                'pricing_plan_id' => 4, // Plan Entreprise
                'integrator_id' => 2, // SmartCharge Solutions
                'operator_id' => 2, // EcoCharge Network
                'group_id' => 1, // Groupe Premium
                'status' => 'active',
                'power_output' => 150.0,
                'connector_type' => 'CCS',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($chargingPoints as $pointData) {
            ChargingPoint::create($pointData);
        }
    }

    private function createDemoTransactions(): void
    {
        $this->command->info('💳 Création des transactions de démonstration...');
        
        // Créer un utilisateur de démo
        $demoUser = User::create([
            'name' => 'Utilisateur Démo',
            'email' => 'demo@evonpower.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Créer un compte pour l'utilisateur
        $account = Account::create([
            'user_id' => $demoUser->id,
            'balance' => 1000.00,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Créer des transactions de démonstration
        $transactions = [
            [
                'user_id' => $demoUser->id,
                'charging_point_id' => 1, // Borne Premium Paris
                'pricing_plan_id' => 1,
                'amount' => 25.00,
                'price_total' => 25.00,
                'kwh_consumed' => 50.0,
                'duration_minutes' => 60,
                'status' => 'completed',
                'activation_fee' => 5.00,
                'price_service' => 20.00,
                'repartition_breakdown' => json_encode([
                    'activation_fee' => 5.00,
                    'recharge_fee' => 3.25, // 2.50 + (25 * 5% / 100)
                    'admin_fee' => 2.00, // 1.50 + (25 * 2% / 100)
                    'integrator_fee' => 2.75, // 2.00 + (25 * 3% / 100)
                    'partner_fee' => 1.375, // 1.00 + (25 * 1.5% / 100)
                    'total_fees' => 14.375,
                    'revenue_to_distribute' => 10.625
                ]),
                'start_time' => now()->subHours(2),
                'end_time' => now()->subHour(),
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHour(),
            ],
            [
                'user_id' => $demoUser->id,
                'charging_point_id' => 2, // Borne Standard Lyon
                'pricing_plan_id' => 2,
                'amount' => 15.00,
                'price_total' => 15.00,
                'kwh_consumed' => 30.0,
                'duration_minutes' => 45,
                'status' => 'completed',
                'activation_fee' => 3.00,
                'price_service' => 12.00,
                'repartition_breakdown' => json_encode([
                    'activation_fee' => 3.00,
                    'recharge_fee' => 2.025, // 1.50 + (15 * 3.5% / 100)
                    'admin_fee' => 0.975, // 0.75 + (15 * 1.5% / 100)
                    'integrator_fee' => 1.625, // 1.25 + (15 * 2.5% / 100)
                    'partner_fee' => 0.65, // 0.50 + (15 * 1% / 100)
                    'total_fees' => 8.275,
                    'revenue_to_distribute' => 6.725
                ]),
                'start_time' => now()->subHours(3),
                'end_time' => now()->subHours(2),
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(2),
            ],
            [
                'user_id' => $demoUser->id,
                'charging_point_id' => 3, // Borne Économique Marseille
                'pricing_plan_id' => 3,
                'amount' => 10.00,
                'price_total' => 10.00,
                'kwh_consumed' => 20.0,
                'duration_minutes' => 30,
                'status' => 'completed',
                'activation_fee' => 2.00,
                'price_service' => 8.00,
                'repartition_breakdown' => json_encode([
                    'activation_fee' => 2.00,
                    'recharge_fee' => 1.25, // 1.00 + (10 * 2.5% / 100)
                    'admin_fee' => 0.60, // 0.50 + (10 * 1% / 100)
                    'integrator_fee' => 1.20, // 1.00 + (10 * 2% / 100)
                    'partner_fee' => 0.30, // 0.25 + (10 * 0.5% / 100)
                    'total_fees' => 5.35,
                    'revenue_to_distribute' => 4.65
                ]),
                'start_time' => now()->subHours(4),
                'end_time' => now()->subHours(3),
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subHours(3),
            ],
            [
                'user_id' => $demoUser->id,
                'charging_point_id' => 4, // Borne Entreprise Nantes
                'pricing_plan_id' => 4,
                'amount' => 40.00,
                'price_total' => 40.00,
                'kwh_consumed' => 80.0,
                'duration_minutes' => 90,
                'status' => 'completed',
                'activation_fee' => 8.00,
                'price_service' => 32.00,
                'repartition_breakdown' => json_encode([
                    'activation_fee' => 8.00,
                    'recharge_fee' => 4.60, // 3.00 + (40 * 4% / 100)
                    'admin_fee' => 3.00, // 2.00 + (40 * 2.5% / 100)
                    'integrator_fee' => 3.90, // 2.50 + (40 * 3.5% / 100)
                    'partner_fee' => 2.30, // 1.50 + (40 * 2% / 100)
                    'total_fees' => 21.80,
                    'revenue_to_distribute' => 18.20
                ]),
                'start_time' => now()->subHours(5),
                'end_time' => now()->subHours(4),
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(4),
            ]
        ];

        foreach ($transactions as $transactionData) {
            DB::table('transactions')->insert($transactionData);
        }
    }
}

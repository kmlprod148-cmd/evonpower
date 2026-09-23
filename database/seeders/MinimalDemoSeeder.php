<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use App\Models\ChargingPoint;
use App\Models\Station;
use App\Models\Transaction;
use App\Enums\TransactionStatus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MinimalDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Démarrage de la création minimale des données de démonstration...');

        // Créer les données de base
        $this->createBusinessProfiles();
        $this->createPricingPlans();
        $this->createStations();
        $this->createChargingPoints();
        $this->createDemoUsers();
        $this->createDemoTransactions();

        $this->command->info('✅ Données de démonstration minimales créées avec succès !');
    }

    private function createBusinessProfiles(): void
    {
        if (!Schema::hasTable('business_profiles')) {
            $this->command->warn('⚠️ Table business_profiles n\'existe pas, ignorée');
            return;
        }

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
                'operator_commission' => 0.00,
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
                'operator_commission' => 0.00,
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
                'operator_commission' => 0.00,
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
                'operator_commission' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($businessProfiles as $profileData) {
            try {
                BusinessProfile::create($profileData);
                $this->command->info("✅ Profil business créé: {$profileData['name']}");
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur lors de la création du profil business: " . $e->getMessage());
            }
        }
    }

    private function createPricingPlans(): void
    {
        if (!Schema::hasTable('pricing_plans')) {
            $this->command->warn('⚠️ Table pricing_plans n\'existe pas, ignorée');
            return;
        }

        $this->command->info('💰 Création des plans tarifaires...');
        
        $pricingPlans = [
            [
                'name' => 'Plan Premium',
                'description' => 'Plan premium avec tarifs élevés et services exclusifs',
                'price_per_kwh' => 0.45,
                'activation_fee' => 5.00,
                'minimum_charge' => 2.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Plan Standard',
                'description' => 'Plan standard avec tarifs compétitifs',
                'price_per_kwh' => 0.35,
                'activation_fee' => 3.00,
                'minimum_charge' => 1.50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Plan Économique',
                'description' => 'Plan économique avec tarifs réduits',
                'price_per_kwh' => 0.25,
                'activation_fee' => 2.00,
                'minimum_charge' => 1.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Plan Entreprise',
                'description' => 'Plan entreprise avec tarifs personnalisés',
                'price_per_kwh' => 0.40,
                'activation_fee' => 8.00,
                'minimum_charge' => 3.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($pricingPlans as $planData) {
            try {
                PricingPlan::create($planData);
                $this->command->info("✅ Plan tarifaire créé: {$planData['name']}");
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur lors de la création du plan tarifaire: " . $e->getMessage());
            }
        }
    }

    private function createStations(): void
    {
        if (!Schema::hasTable('stations')) {
            $this->command->warn('⚠️ Table stations n\'existe pas, ignorée');
            return;
        }

        $this->command->info('🏭 Création des stations...');
        
        // Vérifier la structure de la table
        try {
            $columns = DB::select("SHOW COLUMNS FROM stations");
            $this->command->info("📋 Colonnes de la table stations: " . implode(', ', array_column($columns, 'Field')));
        } catch (\Exception $e) {
            $this->command->error("❌ Erreur lors de la vérification de la structure: " . $e->getMessage());
            return;
        }
        
        $stations = [
            [
                'name' => 'Station Premium Paris',
                'address' => '1 Avenue des Champs-Élysées, 75008 Paris',
                'latitude' => 48.8698,
                'longitude' => 2.3077,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Station Standard Lyon',
                'address' => '10 Place Bellecour, 69002 Lyon',
                'latitude' => 45.7578,
                'longitude' => 4.8320,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Station Économique Marseille',
                'address' => '5 Quai des Belges, 13001 Marseille',
                'latitude' => 43.2965,
                'longitude' => 5.3698,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Station Entreprise Nantes',
                'address' => '15 Place du Commerce, 44000 Nantes',
                'latitude' => 47.2184,
                'longitude' => -1.5536,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        $stationsCreated = 0;
        
        foreach ($stations as $stationData) {
            try {
                $this->command->info("🔍 Tentative de création de la station: {$stationData['name']}");
                
                $station = Station::create($stationData);
                $stationsCreated++;
                $this->command->info("✅ Station créée: {$station->name} (ID: {$station->id})");
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur lors de la création de la station {$stationData['name']}: " . $e->getMessage());
                
                // Afficher plus de détails sur l'erreur
                if (strpos($e->getMessage(), 'Column not found') !== false) {
                    $this->command->error("   💡 Il semble qu'une colonne soit manquante dans la table stations");
                }
                if (strpos($e->getMessage(), 'doesn\'t have a default value') !== false) {
                    $this->command->error("   💡 Il semble qu'une colonne obligatoire n'ait pas de valeur par défaut");
                }
            }
        }
        
        $this->command->info("🎯 Total des stations créées: {$stationsCreated}/" . count($stations));
        
        // Vérifier le résultat final
        $totalStations = Station::count();
        $this->command->info("📊 Total des stations dans la base: {$totalStations}");
    }

    private function createChargingPoints(): void
    {
        if (!Schema::hasTable('charging_points')) {
            $this->command->warn('⚠️ Table charging_points n\'existe pas, ignorée');
            return;
        }

        $this->command->info('🔌 Création des bornes de recharge...');
        
        // Vérifier que les stations et profils business existent
        $stations = Station::all();
        $businessProfiles = BusinessProfile::all();
        $pricingPlans = PricingPlan::all();
        
        $this->command->info("📊 Stations disponibles: " . $stations->count());
        $this->command->info("📊 Profils Business disponibles: " . $businessProfiles->count());
        $this->command->info("📊 Plans Tarifaires disponibles: " . $pricingPlans->count());
        
        if ($stations->isEmpty()) {
            $this->command->error("❌ Aucune station trouvée. Créez d'abord les stations.");
            return;
        }
        
        if ($businessProfiles->isEmpty()) {
            $this->command->error("❌ Aucun profil business trouvé. Créez d'abord les profils business.");
            return;
        }
        
        if ($pricingPlans->isEmpty()) {
            $this->command->error("❌ Aucun plan tarifaire trouvé. Créez d'abord les plans tarifaires.");
            return;
        }
        
        $chargingPoints = [
            [
                'name' => 'Borne Premium Paris',
                'station_id' => 1,
                'business_profile_id' => 1, // Premium Business Profile
                'pricing_plan_id' => 1, // Plan Premium
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
                'power_output' => 150.0,
                'connector_type' => 'CCS',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        $bornesCreated = 0;
        
        foreach ($chargingPoints as $pointData) {
            try {
                // Vérifier que les références existent
                $station = Station::find($pointData['station_id']);
                $businessProfile = BusinessProfile::find($pointData['business_profile_id']);
                $pricingPlan = PricingPlan::find($pointData['pricing_plan_id']);
                
                if (!$station) {
                    $this->command->error("❌ Station ID {$pointData['station_id']} non trouvée pour {$pointData['name']}");
                    continue;
                }
                
                if (!$businessProfile) {
                    $this->command->error("❌ Business Profile ID {$pointData['business_profile_id']} non trouvé pour {$pointData['name']}");
                    continue;
                }
                
                if (!$pricingPlan) {
                    $this->command->error("❌ Pricing Plan ID {$pointData['pricing_plan_id']} non trouvé pour {$pointData['name']}");
                    continue;
                }
                
                $this->command->info("🔍 Création de {$pointData['name']} - Station: {$station->name}, Business Profile: {$businessProfile->name}, Plan: {$pricingPlan->name}");
                
                ChargingPoint::create($pointData);
                $bornesCreated++;
                $this->command->info("✅ Borne créée: {$pointData['name']}");
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur lors de la création de la borne {$pointData['name']}: " . $e->getMessage());
            }
        }
        
        $this->command->info("🎯 Total des bornes créées: {$bornesCreated}/" . count($chargingPoints));
    }

    private function createDemoUsers(): void
    {
        if (!Schema::hasTable('users')) {
            $this->command->warn('⚠️ Table users n\'existe pas, ignorée');
            return;
        }

        $this->command->info('👥 Création des utilisateurs de démonstration...');
        
        $users = [
            [
                'name' => 'Admin Démo',
                'email' => 'admin@demo.evonpower.com',
                'password' => 'password',
            ],
            [
                'name' => 'Client Premium',
                'email' => 'premium@demo.evonpower.com',
                'password' => 'password',
            ],
            [
                'name' => 'Client Standard',
                'email' => 'standard@demo.evonpower.com',
                'password' => 'password',
            ],
            [
                'name' => 'Client Économique',
                'email' => 'economy@demo.evonpower.com',
                'password' => 'password',
            ],
            [
                'name' => 'Client Entreprise',
                'email' => 'enterprise@demo.evonpower.com',
                'password' => 'password',
            ]
        ];

        foreach ($users as $userData) {
            try {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $this->command->info("✅ Utilisateur créé: {$userData['name']}");
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur lors de la création de l'utilisateur: " . $e->getMessage());
            }
        }
    }

    private function createDemoTransactions(): void
    {
        if (!Schema::hasTable('transactions')) {
            $this->command->warn('⚠️ Table transactions n\'existe pas, ignorée');
            return;
        }

        $this->command->info('💳 Création des transactions de démonstration...');
        
        // Récupérer les utilisateurs et bornes avec plus de détails
        $users = User::where('email', 'like', '%@demo.evonpower.com')->get();
        $chargingPoints = ChargingPoint::all();
        
        $this->command->info("📊 Utilisateurs trouvés: " . $users->count());
        $this->command->info("📊 Bornes trouvées: " . $chargingPoints->count());
        
        if ($users->isEmpty()) {
            $this->command->error("❌ Aucun utilisateur trouvé. Vérifiez la création des utilisateurs.");
            return;
        }
        
        if ($chargingPoints->isEmpty()) {
            $this->command->error("❌ Aucune borne trouvée. Vérifiez la création des bornes.");
            return;
        }

        // Afficher les détails pour le débogage
        foreach ($users as $user) {
            $this->command->info("   - Utilisateur: {$user->email} (ID: {$user->id})");
        }
        
        foreach ($chargingPoints as $cp) {
            $this->command->info("   - Borne: {$cp->name} (ID: {$cp->id}, Business Profile: {$cp->business_profile_id}, Pricing Plan: {$cp->pricing_plan_id})");
        }

        $transactions = [
            [
                'user_email' => 'premium@demo.evonpower.com',
                'charging_point_name' => 'Borne Premium Paris',
                'amount' => 25.00,
                'kwh_consumed' => 50.0,
                'duration_minutes' => 60,
                'status' => TransactionStatus::COMPLETED,
                'start_time' => Carbon::now()->subHours(2),
                'end_time' => Carbon::now()->subHour(),
            ],
            [
                'user_email' => 'standard@demo.evonpower.com',
                'charging_point_name' => 'Borne Standard Lyon',
                'amount' => 15.00,
                'kwh_consumed' => 30.0,
                'duration_minutes' => 45,
                'status' => TransactionStatus::COMPLETED,
                'start_time' => Carbon::now()->subHours(3),
                'end_time' => Carbon::now()->subHours(2),
            ],
            [
                'user_email' => 'economy@demo.evonpower.com',
                'charging_point_name' => 'Borne Économique Marseille',
                'amount' => 10.00,
                'kwh_consumed' => 20.0,
                'duration_minutes' => 30,
                'status' => TransactionStatus::COMPLETED,
                'start_time' => Carbon::now()->subHours(4),
                'end_time' => Carbon::now()->subHours(3),
            ],
            [
                'user_email' => 'enterprise@demo.evonpower.com',
                'charging_point_name' => 'Borne Entreprise Nantes',
                'amount' => 40.00,
                'kwh_consumed' => 80.0,
                'duration_minutes' => 90,
                'status' => TransactionStatus::COMPLETED,
                'start_time' => Carbon::now()->subHours(5),
                'end_time' => Carbon::now()->subHours(4),
            ]
        ];

        $transactionsCreated = 0;
        
        foreach ($transactions as $transactionData) {
            $user = $users->where('email', $transactionData['user_email'])->first();
            $chargingPoint = $chargingPoints->where('name', $transactionData['charging_point_name'])->first();

            if (!$user) {
                $this->command->error("❌ Utilisateur non trouvé: {$transactionData['user_email']}");
                continue;
            }
            
            if (!$chargingPoint) {
                $this->command->error("❌ Borne non trouvée: {$transactionData['charging_point_name']}");
                continue;
            }

            try {
                // Créer la transaction de base
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'charging_point_id' => $chargingPoint->id,
                    'pricing_plan_id' => $chargingPoint->pricing_plan_id,
                    'amount' => $transactionData['amount'],
                    'price_total' => $transactionData['amount'],
                    'kwh_consumed' => $transactionData['kwh_consumed'],
                    'duration_minutes' => $transactionData['duration_minutes'],
                    'status' => $transactionData['status'],
                    'start_time' => $transactionData['start_time'],
                    'end_time' => $transactionData['end_time'],
                    'created_at' => $transactionData['start_time'],
                    'updated_at' => $transactionData['end_time'],
                ]);
                
                $transactionsCreated++;
                $this->command->info("✅ Transaction créée: {$transactionData['amount']}€ pour {$transactionData['user_email']} sur {$transactionData['charging_point_name']}");
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur lors de la création de la transaction: " . $e->getMessage());
            }
        }
        
        $this->command->info("🎯 Total des transactions créées: {$transactionsCreated}/" . count($transactions));
    }
}

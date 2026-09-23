<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use App\Models\ChargingPoint;
use App\Models\Station;
use App\Models\Account;
use App\Models\Transaction;
use App\Enums\TransactionStatus;
use App\Services\TransactionFeeCalculationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AdaptiveDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Démarrage de la création adaptative des données de démonstration...');

        // Créer les données de base
        $this->createBusinessProfiles();
        $this->createPricingPlans();
        $this->createStations();
        $this->createChargingPoints();
        $this->createDemoUsers();
        $this->createDemoTransactions();

        $this->command->info('✅ Données de démonstration adaptatives créées avec succès !');
        $this->command->info('📊 Résumé final :');
        $this->command->info('   - 4 Profils Business créés');
        $this->command->info('   - 4 Plans Tarifaires créés');
        $this->command->info('   - 4 Stations créées');
        $this->command->info('   - 4 Bornes de Recharge créées');
        $this->command->info('   - 5 Utilisateurs de démonstration créés');
        $this->command->info('   - 4 Transactions détaillées créées');
    }

    private function createBusinessProfiles(): void
    {
        if (!Schema::hasTable('business_profiles')) {
            $this->command->warn('⚠️ Table business_profiles n\'existe pas, ignorée');
            return;
        }

        $this->command->info('💼 Création des profils business...');
        
        // Vérifier les colonnes disponibles
        $columns = $this->getTableColumns('business_profiles');
        
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
                'operator_commission' => 0.00, // Ajouter si la colonne existe
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
                'operator_commission' => 0.00,
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
                'operator_commission' => 0.00,
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
                'operator_commission' => 0.00,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($businessProfiles as $profileData) {
            try {
                // Filtrer les colonnes qui existent
                $filteredData = array_intersect_key($profileData, array_flip($columns));
                BusinessProfile::create($filteredData);
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
        
        $columns = $this->getTableColumns('pricing_plans');
        
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
            try {
                $filteredData = array_intersect_key($planData, array_flip($columns));
                PricingPlan::create($filteredData);
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
        
        $columns = $this->getTableColumns('stations');
        
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
            try {
                $filteredData = array_intersect_key($stationData, array_flip($columns));
                Station::create($filteredData);
                $this->command->info("✅ Station créée: {$stationData['name']}");
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur lors de la création de la station: " . $e->getMessage());
            }
        }
    }

    private function createChargingPoints(): void
    {
        if (!Schema::hasTable('charging_points')) {
            $this->command->warn('⚠️ Table charging_points n\'existe pas, ignorée');
            return;
        }

        $this->command->info('🔌 Création des bornes de recharge...');
        
        $columns = $this->getTableColumns('charging_points');
        
        $chargingPoints = [
            [
                'name' => 'Borne Premium Paris',
                'station_id' => 1,
                'business_profile_id' => 1, // Premium Business Profile
                'pricing_plan_id' => 1, // Plan Premium
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
                'status' => 'active',
                'power_output' => 150.0,
                'connector_type' => 'CCS',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($chargingPoints as $pointData) {
            try {
                $filteredData = array_intersect_key($pointData, array_flip($columns));
                ChargingPoint::create($filteredData);
                $this->command->info("✅ Borne créée: {$pointData['name']}");
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur lors de la création de la borne: " . $e->getMessage());
            }
        }
    }

    private function createDemoUsers(): void
    {
        if (!Schema::hasTable('users')) {
            $this->command->warn('⚠️ Table users n\'existe pas, ignorée');
            return;
        }

        $this->command->info('👥 Création des utilisateurs de démonstration...');
        
        $columns = $this->getTableColumns('users');
        
        $users = [
            [
                'name' => 'Admin Démo',
                'email' => 'admin@demo.evonpower.com',
                'password' => 'password',
                'balance' => 5000.00,
            ],
            [
                'name' => 'Client Premium',
                'email' => 'premium@demo.evonpower.com',
                'password' => 'password',
                'balance' => 800.00,
            ],
            [
                'name' => 'Client Standard',
                'email' => 'standard@demo.evonpower.com',
                'password' => 'password',
                'balance' => 500.00,
            ],
            [
                'name' => 'Client Économique',
                'email' => 'economy@demo.evonpower.com',
                'password' => 'password',
                'balance' => 300.00,
            ],
            [
                'name' => 'Client Entreprise',
                'email' => 'enterprise@demo.evonpower.com',
                'password' => 'password',
                'balance' => 2000.00,
            ]
        ];

        foreach ($users as $userData) {
            try {
                $userFields = [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'email_verified_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                // Filtrer les colonnes qui existent
                $filteredUserFields = array_intersect_key($userFields, array_flip($columns));
                $user = User::create($filteredUserFields);

                // Créer le compte si la table existe
                if (Schema::hasTable('accounts')) {
                    $accountColumns = $this->getTableColumns('accounts');
                    $accountData = [
                        'user_id' => $user->id,
                        'balance' => $userData['balance'],
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    $filteredAccountData = array_intersect_key($accountData, array_flip($accountColumns));
                    Account::create($filteredAccountData);
                }
                
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
        
        $users = User::where('email', 'like', '%@demo.evonpower.com')->get();
        $chargingPoints = ChargingPoint::all();
        
        if ($users->isEmpty() || $chargingPoints->isEmpty()) {
            $this->command->warn('⚠️ Aucun utilisateur ou borne trouvé pour créer les transactions');
            return;
        }

        $feeService = new TransactionFeeCalculationService();
        $columns = $this->getTableColumns('transactions');

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

        foreach ($transactions as $transactionData) {
            $user = $users->where('email', $transactionData['user_email'])->first();
            $chargingPoint = $chargingPoints->where('name', $transactionData['charging_point_name'])->first();

            if ($user && $chargingPoint) {
                try {
                    // Créer la transaction de base
                    $transactionFields = [
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
                    ];
                    
                    // Filtrer les colonnes qui existent
                    $filteredTransactionFields = array_intersect_key($transactionFields, array_flip($columns));
                    $transaction = Transaction::create($filteredTransactionFields);

                    // Calculer et appliquer les frais si possible
                    try {
                        $fees = $feeService->calculateAllFees($transaction);
                        
                        // Mettre à jour la transaction avec les frais calculés
                        $updateFields = [
                            'activation_fee' => $fees['activation_fee'],
                            'price_service' => $fees['recharge_fee'],
                            'repartition_breakdown' => json_encode($fees['breakdown']),
                        ];
                        
                        // Filtrer les colonnes qui existent
                        $filteredUpdateFields = array_intersect_key($updateFields, array_flip($columns));
                        $transaction->update($filteredUpdateFields);
                    } catch (\Exception $e) {
                        $this->command->warn("⚠️ Impossible de calculer les frais pour la transaction: " . $e->getMessage());
                    }
                    
                    $this->command->info("✅ Transaction créée: {$transactionData['amount']}€ pour {$transactionData['user_email']}");
                } catch (\Exception $e) {
                    $this->command->error("❌ Erreur lors de la création de la transaction: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Récupère les colonnes d'une table
     */
    private function getTableColumns(string $table): array
    {
        try {
            $columns = DB::select("SHOW COLUMNS FROM $table");
            return array_column($columns, 'Field');
        } catch (\Exception $e) {
            $this->command->warn("⚠️ Impossible de récupérer les colonnes de la table $table: " . $e->getMessage());
            return [];
        }
    }
}

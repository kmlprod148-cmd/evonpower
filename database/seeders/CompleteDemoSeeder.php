<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use App\Models\Station;
use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\Group;
use App\Models\CommissionPlan;
use App\Models\VatRate;
use App\Models\Transaction;
use App\Models\Report;
use App\Models\Reservation;
use App\Enums\ReservationStatus;
use App\Enums\TransactionStatus;
use App\Enums\PaymentType;
use Carbon\Carbon;

class CompleteDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Démarrage du seeder de démonstration complet...');

        // Désactiver les contraintes de clés étrangères
        Schema::disableForeignKeyConstraints();

        try {
            // 1. Créer les rôles et permissions
            $this->command->info('📋 Étape 1: Création des rôles et permissions...');
            $this->createRolesAndPermissions();

            // 2. Créer les utilisateurs de démonstration
            $this->command->info('📋 Étape 2: Création des utilisateurs...');
            $this->createDemoUsers();

            // 3. Créer les taux de TVA
            $this->command->info('📋 Étape 3: Création des taux de TVA...');
            $this->createVatRates();

            // 4. Créer les intégrateurs
            $this->command->info('📋 Étape 4: Création des intégrateurs...');
            $this->createIntegrators();

            // 5. Créer les partenaires
            $this->command->info('📋 Étape 5: Création des partenaires...');
            $this->createPartners();

            // 6. Créer les groupes
            $this->command->info('📋 Étape 6: Création des groupes...');
            $this->createGroups();

            // 7. Créer les profils business
            $this->command->info('📋 Étape 7: Création des profils business...');
            $this->createBusinessProfiles();

            // 8. Créer les plans tarifaires
            $this->command->info('📋 Étape 8: Création des plans tarifaires...');
            $this->createPricingPlans();

            // 9. Créer les stations
            $this->command->info('📋 Étape 9: Création des stations...');
            $this->createStations();

            // 10. Créer les bornes de recharge
            $this->command->info('📋 Étape 10: Création des bornes de recharge...');
            $this->createChargingPoints();

            // 11. Créer les plans de commission
            $this->command->info('📋 Étape 11: Création des plans de commission...');
            $this->createCommissionPlans();

            // 12. Créer les transactions de démonstration
            $this->command->info('📋 Étape 12: Création des transactions...');
            $this->createDemoTransactions();

            // 13. Créer les réservations de démonstration
            $this->command->info('📋 Étape 13: Création des réservations...');
            $this->createDemoReservations();

            // 14. Créer les rapports de démonstration
            $this->command->info('📋 Étape 14: Création des rapports...');
            $this->createDemoReports();

            $this->command->info('✅ Seeder de démonstration complet terminé avec succès !');

        } catch (\Exception $e) {
            $this->command->error('❌ Erreur lors de l\'exécution du seeder: ' . $e->getMessage());
            $this->command->error('📋 Stack trace: ' . $e->getTraceAsString());
        } finally {
            // Réactiver les contraintes de clés étrangères
            Schema::enableForeignKeyConstraints();
        }
    }

    private function createRolesAndPermissions(): void
    {
        // Créer les rôles
        $roles = ['admin', 'integrator', 'partner', 'user'];
        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName);
        }

        // Créer les permissions de base
        $permissions = [
            'view_dashboard',
            'manage_users',
            'manage_business_profiles',
            'manage_charging_points',
            'manage_transactions',
            'view_reports',
            'manage_pricing_plans',
            'manage_commission_plans',
            'manage_stations',
            'manage_integrators',
            'manage_partners',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName);
        }

        // Assigner toutes les permissions à l'admin
        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo($permissions);

        // Permissions spécifiques pour les intégrateurs
        $integratorRole = Role::findByName('integrator');
        $integratorRole->givePermissionTo([
            'view_dashboard',
            'manage_business_profiles',
            'manage_charging_points',
            'view_reports',
            'manage_stations',
        ]);

        // Permissions spécifiques pour les partenaires
        $partnerRole = Role::findByName('partner');
        $partnerRole->givePermissionTo([
            'view_dashboard',
            'view_reports',
            'manage_charging_points',
        ]);

        $this->command->info('✅ Rôles et permissions créés');
    }

    private function createDemoUsers(): void
    {
        $users = [
            [
                'name' => 'Admin EVON',
                'email' => 'admin@evon.com',
                'password' => 'password',
                'role' => 'admin',
            ],
            [
                'name' => 'Intégrateur Demo',
                'email' => 'integrator@demo.evonpower.com',
                'password' => 'password',
                'role' => 'integrator',
            ],
            [
                'name' => 'Partenaire Demo',
                'email' => 'partner@demo.evonpower.com',
                'password' => 'password',
                'role' => 'partner',
            ],
            [
                'name' => 'Utilisateur Demo',
                'email' => 'user@demo.evonpower.com',
                'password' => 'password',
                'role' => 'user',
            ],
        ];

        foreach ($users as $userData) {
            // Vérifier si l'utilisateur existe déjà
            $existingUser = User::where('email', $userData['email'])->first();
            
            if ($existingUser) {
                // Mettre à jour l'utilisateur existant
                $existingUser->update([
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'email_verified_at' => now(),
                ]);
                
                // S'assurer que le rôle est assigné
                if (!$existingUser->hasRole($userData['role'])) {
                    $existingUser->assignRole($userData['role']);
                }
                
                $this->command->info("✅ Utilisateur mis à jour : {$userData['email']}");
            } else {
                // Créer un nouvel utilisateur
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'email_verified_at' => now(),
                ]);

                $user->assignRole($userData['role']);
                $this->command->info("✅ Nouvel utilisateur créé : {$userData['email']}");
            }
        }

        $this->command->info('✅ Utilisateurs de démonstration configurés');
    }

    private function createVatRates(): void
    {
        $vatRates = [
            ['name' => 'TVA Standard', 'rate' => 20.0, 'is_active' => true],
            ['name' => 'TVA Réduite', 'rate' => 10.0, 'is_active' => true],
            ['name' => 'TVA Super Réduite', 'rate' => 5.5, 'is_active' => true],
            ['name' => 'Sans TVA', 'rate' => 0.0, 'is_active' => true],
        ];

        foreach ($vatRates as $vatData) {
            VatRate::updateOrCreate(
                ['name' => $vatData['name']],
                $vatData
            );
        }

        $this->command->info('✅ Taux de TVA configurés');
    }

    private function createIntegrators(): void
    {
        $integrators = [
            [
                'name' => 'EVON Power Solutions',
                'description' => 'Intégrateur principal EVON',
                'email' => 'contact@evonpower.com',
                'phone' => '+33 1 23 45 67 89',
                'address' => '123 Avenue de la République, 75011 Paris',
                'is_active' => true,
            ],
            [
                'name' => 'Green Energy Integrator',
                'description' => 'Spécialiste en énergies vertes',
                'email' => 'contact@greenenergy.com',
                'phone' => '+33 1 98 76 54 32',
                'address' => '456 Rue de la Paix, 69000 Lyon',
                'is_active' => true,
            ],
        ];

        foreach ($integrators as $integratorData) {
            Integrator::updateOrCreate(
                ['email' => $integratorData['email']],
                $integratorData
            );
        }

        $this->command->info('✅ Intégrateurs configurés');
    }

    private function createPartners(): void
    {
        $partners = [
            [
                'name' => 'Station Service Premium',
                'description' => 'Réseau de stations-service premium',
                'email' => 'contact@stationpremium.com',
                'phone' => '+33 1 11 22 33 44',
                'address' => '789 Boulevard Saint-Germain, 75006 Paris',
                'is_active' => true,
            ],
            [
                'name' => 'Parking Urbain Plus',
                'description' => 'Gestionnaire de parkings urbains',
                'email' => 'contact@parkingurbain.com',
                'phone' => '+33 1 55 66 77 88',
                'address' => '321 Rue de Rivoli, 75001 Paris',
                'is_active' => true,
            ],
        ];

        foreach ($partners as $partnerData) {
            Partner::updateOrCreate(
                ['email' => $partnerData['email']],
                $partnerData
            );
        }

        $this->command->info('✅ Partenaires configurés');
    }

    private function createGroups(): void
    {
        $groups = [
            [
                'name' => 'Groupe Paris Centre',
                'description' => 'Stations du centre de Paris',
                'is_active' => true,
            ],
            [
                'name' => 'Groupe Banlieue',
                'description' => 'Stations de la banlieue parisienne',
                'is_active' => true,
            ],
        ];

        foreach ($groups as $groupData) {
            Group::updateOrCreate(
                ['name' => $groupData['name']],
                $groupData
            );
        }

        $this->command->info('✅ Groupes configurés');
    }

    private function createBusinessProfiles(): void
    {
        $integrator = Integrator::first();
        $partner = Partner::first();

        if (!$integrator || !$partner) {
            $this->command->error('❌ Intégrateur ou partenaire non trouvé');
            return;
        }

        $profiles = [
            [
                'name' => 'Profil Premium',
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
                'integrator_commission' => 5.0,
                'owner_commission' => 3.0,
                'partner_id' => $partner->id,
                'integrator_id' => $integrator->id,
                'is_active' => true,
            ],
            [
                'name' => 'Profil Standard',
                'description' => 'Profil standard avec frais modérés',
                'base_fee_amount' => 10.00,
                'charge_fee_config' => json_encode([
                    'fixed_amount' => 1.00,
                    'percentage' => 2.0
                ]),
                'admin_fee_fixed' => 0.50,
                'admin_fee_percentage' => 1.0,
                'integrator_fee_fixed' => 1.00,
                'integrator_fee_percentage' => 2.0,
                'partner_fee_fixed' => 0.25,
                'partner_fee_percentage' => 0.5,
                'operator_commission' => 0.00,
                'integrator_commission' => 3.0,
                'owner_commission' => 2.0,
                'partner_id' => $partner->id,
                'integrator_id' => $integrator->id,
                'is_active' => true,
            ],
        ];

        foreach ($profiles as $profileData) {
            BusinessProfile::create($profileData);
        }

        $this->command->info('✅ Profils business créés');
    }

    private function createPricingPlans(): void
    {
        $plans = [
            [
                'name' => 'Plan Premium',
                'description' => 'Plan tarifaire premium avec services exclusifs',
                'price_per_kwh' => 0.45,
                'activation_fee' => 5.00,
                'minimum_charge' => 2.00,
                'is_active' => true,
            ],
            [
                'name' => 'Plan Standard',
                'description' => 'Plan tarifaire standard',
                'price_per_kwh' => 0.30,
                'activation_fee' => 3.00,
                'minimum_charge' => 1.50,
                'is_active' => true,
            ],
            [
                'name' => 'Plan Économique',
                'description' => 'Plan tarifaire économique',
                'price_per_kwh' => 0.20,
                'activation_fee' => 1.00,
                'minimum_charge' => 1.00,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            PricingPlan::updateOrCreate(
                ['name' => $planData['name']],
                $planData
            );
        }

        $this->command->info('✅ Plans tarifaires configurés');
    }

    private function createStations(): void
    {
        $group = Group::first();

        $stations = [
            [
                'name' => 'Station Champs-Élysées',
                'address' => '123 Avenue des Champs-Élysées, 75008 Paris',
                'latitude' => 48.8698,
                'longitude' => 2.3077,
                'group_id' => $group ? $group->id : null,
                'is_active' => true,
            ],
            [
                'name' => 'Station Tour Eiffel',
                'address' => '456 Avenue Gustave Eiffel, 75007 Paris',
                'latitude' => 48.8584,
                'longitude' => 2.2945,
                'group_id' => $group ? $group->id : null,
                'is_active' => true,
            ],
            [
                'name' => 'Station Montmartre',
                'address' => '789 Rue de la Butte, 75018 Paris',
                'latitude' => 48.8867,
                'longitude' => 2.3431,
                'group_id' => $group ? $group->id : null,
                'is_active' => true,
            ],
        ];

        foreach ($stations as $stationData) {
            Station::updateOrCreate(
                ['name' => $stationData['name']],
                $stationData
            );
        }

        $this->command->info('✅ Stations configurées');
    }

    private function createChargingPoints(): void
    {
        $stations = Station::all();
        $businessProfiles = BusinessProfile::all();
        $pricingPlans = PricingPlan::all();

        if ($stations->isEmpty() || $businessProfiles->isEmpty() || $pricingPlans->isEmpty()) {
            $this->command->error('❌ Stations, profils business ou plans tarifaires non trouvés');
            return;
        }

        $chargingPoints = [
            // Station 1 - Champs-Élysées
            [
                'name' => 'Borne Type 2 - 22kW',
                'serial_number' => 'CP001-T2-22KW',
                'manufacturer' => 'Schneider Electric',
                'model' => 'EVlink Wallbox',
                'station_id' => $stations[0]->id,
                'business_profile_id' => $businessProfiles[0]->id,
                'pricing_plan_id' => $pricingPlans[0]->id,
                'power_output' => 22.0,
                'connector_type' => 'Type 2',
                'status' => 'available',
                'address' => '123 Avenue des Champs-Élysées, 75008 Paris',
                'latitude' => 48.8698,
                'longitude' => 2.3077,
                'public_access' => true,
                'access_type' => 'public',
                'max_duration' => 240, // 4 heures max
                'total_energy_delivered' => 1250.5,
                'total_charging_sessions' => 45,
                'last_used_at' => now()->subHours(2),
                'is_active' => true,
            ],
            [
                'name' => 'Borne CCS - 50kW',
                'serial_number' => 'CP002-CCS-50KW',
                'manufacturer' => 'ABB',
                'model' => 'Terra 184',
                'station_id' => $stations[0]->id,
                'business_profile_id' => $businessProfiles[0]->id,
                'pricing_plan_id' => $pricingPlans[0]->id,
                'power_output' => 50.0,
                'connector_type' => 'CCS',
                'status' => 'charging',
                'address' => '123 Avenue des Champs-Élysées, 75008 Paris',
                'latitude' => 48.8698,
                'longitude' => 2.3077,
                'public_access' => true,
                'access_type' => 'public',
                'max_duration' => 120, // 2 heures max
                'total_energy_delivered' => 890.3,
                'total_charging_sessions' => 32,
                'last_used_at' => now()->subMinutes(15),
                'is_active' => true,
            ],
            [
                'name' => 'Borne CHAdeMO - 50kW',
                'serial_number' => 'CP003-CHADEMO-50KW',
                'manufacturer' => 'Nissan',
                'model' => 'Quick Charger',
                'station_id' => $stations[0]->id,
                'business_profile_id' => $businessProfiles[1]->id,
                'pricing_plan_id' => $pricingPlans[1]->id,
                'power_output' => 50.0,
                'connector_type' => 'CHAdeMO',
                'status' => 'maintenance',
                'address' => '123 Avenue des Champs-Élysées, 75008 Paris',
                'latitude' => 48.8698,
                'longitude' => 2.3077,
                'public_access' => false,
                'access_type' => 'private',
                'max_duration' => 120,
                'total_energy_delivered' => 567.8,
                'total_charging_sessions' => 18,
                'last_used_at' => now()->subDays(1),
                'is_active' => false,
            ],
            
            // Station 2 - Tour Eiffel
            [
                'name' => 'Borne Type 2 - 11kW',
                'serial_number' => 'CP004-T2-11KW',
                'manufacturer' => 'Bosch',
                'model' => 'Power Max',
                'station_id' => $stations[1]->id,
                'business_profile_id' => $businessProfiles[1]->id,
                'pricing_plan_id' => $pricingPlans[2]->id,
                'power_output' => 11.0,
                'connector_type' => 'Type 2',
                'status' => 'available',
                'address' => '456 Avenue Gustave Eiffel, 75007 Paris',
                'latitude' => 48.8584,
                'longitude' => 2.2945,
                'public_access' => true,
                'access_type' => 'public',
                'max_duration' => 480, // 8 heures max
                'total_energy_delivered' => 2340.7,
                'total_charging_sessions' => 89,
                'last_used_at' => now()->subHours(4),
                'is_active' => true,
            ],
            [
                'name' => 'Borne Tesla Supercharger - 150kW',
                'serial_number' => 'CP005-TESLA-150KW',
                'manufacturer' => 'Tesla',
                'model' => 'Supercharger V3',
                'station_id' => $stations[1]->id,
                'business_profile_id' => $businessProfiles[0]->id,
                'pricing_plan_id' => $pricingPlans[0]->id,
                'power_output' => 150.0,
                'connector_type' => 'Tesla',
                'status' => 'charging',
                'address' => '456 Avenue Gustave Eiffel, 75007 Paris',
                'latitude' => 48.8584,
                'longitude' => 2.2945,
                'public_access' => true,
                'access_type' => 'public',
                'max_duration' => 60, // 1 heure max
                'total_energy_delivered' => 4567.2,
                'total_charging_sessions' => 156,
                'last_used_at' => now()->subMinutes(8),
                'is_active' => true,
            ],
            
            // Station 3 - Montmartre
            [
                'name' => 'Borne Type 2 - 7.4kW',
                'serial_number' => 'CP006-T2-7.4KW',
                'manufacturer' => 'Wallbox',
                'model' => 'Pulsar Plus',
                'station_id' => $stations[2]->id,
                'business_profile_id' => $businessProfiles[1]->id,
                'pricing_plan_id' => $pricingPlans[2]->id,
                'power_output' => 7.4,
                'connector_type' => 'Type 2',
                'status' => 'available',
                'address' => '789 Rue de la Butte, 75018 Paris',
                'latitude' => 48.8867,
                'longitude' => 2.3431,
                'public_access' => true,
                'access_type' => 'public',
                'max_duration' => 720, // 12 heures max
                'total_energy_delivered' => 890.1,
                'total_charging_sessions' => 67,
                'last_used_at' => now()->subHours(6),
                'is_active' => true,
            ],
            [
                'name' => 'Borne Type 2 - 22kW (Privée)',
                'serial_number' => 'CP007-T2-22KW-PRIV',
                'manufacturer' => 'Schneider Electric',
                'model' => 'EVlink Parking',
                'station_id' => $stations[2]->id,
                'business_profile_id' => $businessProfiles[0]->id,
                'pricing_plan_id' => $pricingPlans[1]->id,
                'power_output' => 22.0,
                'connector_type' => 'Type 2',
                'status' => 'available',
                'address' => '789 Rue de la Butte, 75018 Paris',
                'latitude' => 48.8867,
                'longitude' => 2.3431,
                'public_access' => false,
                'access_type' => 'private',
                'access_code' => 'MONT123',
                'max_duration' => 240,
                'total_energy_delivered' => 1234.5,
                'total_charging_sessions' => 42,
                'last_used_at' => now()->subDays(2),
                'is_active' => true,
            ],
        ];

        foreach ($chargingPoints as $pointData) {
            ChargingPoint::updateOrCreate(
                ['serial_number' => $pointData['serial_number']],
                $pointData
            );
        }

        $this->command->info('✅ Bornes de recharge configurées');
    }

    private function createCommissionPlans(): void
    {
        $plans = [
            [
                'name' => 'Plan Commission Standard',
                'description' => 'Plan de commission standard pour les partenaires',
                'admin_commission' => 5.0,
                'integrator_commission' => 10.0,
                'partner_commission' => 15.0,
                'is_active' => true,
            ],
            [
                'name' => 'Plan Commission Premium',
                'description' => 'Plan de commission premium avec taux élevés',
                'admin_commission' => 3.0,
                'integrator_commission' => 12.0,
                'partner_commission' => 20.0,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            CommissionPlan::updateOrCreate(
                ['name' => $planData['name']],
                $planData
            );
        }

        $this->command->info('✅ Plans de commission configurés');
    }

    private function createDemoTransactions(): void
    {
        $chargingPoints = ChargingPoint::where('is_active', true)->get();
        $users = User::where('email', 'like', '%@demo.evonpower.com')->get();
        $businessProfiles = BusinessProfile::all();
        $pricingPlans = PricingPlan::all();

        if ($chargingPoints->isEmpty() || $users->isEmpty()) {
            $this->command->error('❌ Bornes de recharge ou utilisateurs non trouvés');
            return;
        }

        $transactions = [
            // Transaction récente - en cours
            [
                'user_id' => $users->first()->id,
                'charging_point_id' => $chargingPoints[0]->id,
                'business_profile_id' => $chargingPoints[0]->business_profile_id,
                'pricing_plan_id' => $chargingPoints[0]->pricing_plan_id,
                'transaction_type' => 'client',
                'transaction_category' => 'charging',
                'meter_start' => 1234.5678,
                'meter_stop' => 1256.7890,
                'energy_delivered' => 22.2212,
                'duration' => 45,
                'amount' => 15.50,
                'price_total' => 15.50,
                'price_energy' => 12.40,
                'price_time' => 2.25,
                'price_service' => 0.50,
                'price_tax' => 0.35,
                'currency' => 'EUR',
                'status' => TransactionStatus::ACTIVE,
                'payment_status' => 'pending',
                'payment_method' => 'cmi',
                'start_timestamp' => now()->subMinutes(45),
                'stop_timestamp' => null,
                'auth_method' => 'rfid',
                'auth_id' => 'RFID123456',
            ],
            
            // Transaction terminée récemment
            [
                'user_id' => $users->first()->id,
                'charging_point_id' => $chargingPoints[1]->id,
                'business_profile_id' => $chargingPoints[1]->business_profile_id,
                'pricing_plan_id' => $chargingPoints[1]->pricing_plan_id,
                'transaction_type' => 'client',
                'transaction_category' => 'charging',
                'meter_start' => 890.1234,
                'meter_stop' => 902.3456,
                'energy_delivered' => 12.2222,
                'duration' => 25,
                'amount' => 8.75,
                'price_total' => 8.75,
                'price_energy' => 7.00,
                'price_time' => 1.25,
                'price_service' => 0.30,
                'price_tax' => 0.20,
                'currency' => 'EUR',
                'status' => TransactionStatus::COMPLETED,
                'payment_status' => 'paid',
                'payment_method' => 'cmi',
                'start_timestamp' => now()->subHours(2),
                'stop_timestamp' => now()->subHour()->subMinutes(35),
                'auth_method' => 'app',
                'auth_id' => 'APP789012',
            ],
            
            // Transaction plus ancienne
            [
                'user_id' => $users->first()->id,
                'charging_point_id' => $chargingPoints[2]->id,
                'business_profile_id' => $chargingPoints[2]->business_profile_id,
                'pricing_plan_id' => $chargingPoints[2]->pricing_plan_id,
                'transaction_type' => 'client',
                'transaction_category' => 'charging',
                'meter_start' => 567.8901,
                'meter_stop' => 589.0123,
                'energy_delivered' => 21.1222,
                'duration' => 60,
                'amount' => 12.30,
                'price_total' => 12.30,
                'price_energy' => 9.84,
                'price_time' => 1.80,
                'price_service' => 0.40,
                'price_tax' => 0.26,
                'currency' => 'EUR',
                'status' => TransactionStatus::COMPLETED,
                'payment_status' => 'paid',
                'payment_method' => 'offline',
                'start_timestamp' => now()->subDays(1),
                'stop_timestamp' => now()->subDays(1)->addMinutes(60),
                'auth_method' => 'manual',
                'auth_id' => 'MANUAL345678',
            ],
            
            // Transaction avec commission
            [
                'user_id' => $users->first()->id,
                'charging_point_id' => $chargingPoints[3]->id,
                'business_profile_id' => $chargingPoints[3]->business_profile_id,
                'pricing_plan_id' => $chargingPoints[3]->pricing_plan_id,
                'transaction_type' => 'client',
                'transaction_category' => 'charging',
                'meter_start' => 2340.7890,
                'meter_stop' => 2356.1234,
                'energy_delivered' => 15.3344,
                'duration' => 35,
                'amount' => 9.20,
                'price_total' => 9.20,
                'price_energy' => 7.36,
                'price_time' => 1.40,
                'price_service' => 0.25,
                'price_tax' => 0.19,
                'currency' => 'EUR',
                'status' => TransactionStatus::COMPLETED,
                'payment_status' => 'paid',
                'payment_method' => 'cmi',
                'start_timestamp' => now()->subDays(2),
                'stop_timestamp' => now()->subDays(2)->addMinutes(35),
                'auth_method' => 'rfid',
                'auth_id' => 'RFID901234',
                'admin_commission' => 0.46,
                'integrator_commission' => 0.92,
                'partner_commission' => 0.69,
            ],
            
            // Transaction longue durée
            [
                'user_id' => $users->first()->id,
                'charging_point_id' => $chargingPoints[4]->id,
                'business_profile_id' => $chargingPoints[4]->business_profile_id,
                'pricing_plan_id' => $chargingPoints[4]->pricing_plan_id,
                'transaction_type' => 'client',
                'transaction_category' => 'charging',
                'meter_start' => 4567.2345,
                'meter_stop' => 4623.4567,
                'energy_delivered' => 56.2222,
                'duration' => 120,
                'amount' => 28.50,
                'price_total' => 28.50,
                'price_energy' => 22.80,
                'price_time' => 4.50,
                'price_service' => 0.80,
                'price_tax' => 0.40,
                'currency' => 'EUR',
                'status' => TransactionStatus::COMPLETED,
                'payment_status' => 'paid',
                'payment_method' => 'cmi',
                'start_timestamp' => now()->subDays(3),
                'stop_timestamp' => now()->subDays(3)->addMinutes(120),
                'auth_method' => 'app',
                'auth_id' => 'APP567890',
            ],
            
            // Transaction échouée
            [
                'user_id' => $users->first()->id,
                'charging_point_id' => $chargingPoints[5]->id,
                'business_profile_id' => $chargingPoints[5]->business_profile_id,
                'pricing_plan_id' => $chargingPoints[5]->pricing_plan_id,
                'transaction_type' => 'client',
                'transaction_category' => 'charging',
                'meter_start' => 890.5678,
                'meter_stop' => 890.5678,
                'energy_delivered' => 0.0000,
                'duration' => 5,
                'amount' => 0.00,
                'price_total' => 0.00,
                'price_energy' => 0.00,
                'price_time' => 0.00,
                'price_service' => 0.00,
                'price_tax' => 0.00,
                'currency' => 'EUR',
                'status' => TransactionStatus::FAILED,
                'payment_status' => 'cancelled',
                'payment_method' => 'cmi',
                'start_timestamp' => now()->subDays(4),
                'stop_timestamp' => now()->subDays(4)->addMinutes(5),
                'auth_method' => 'rfid',
                'auth_id' => 'RFID678901',
                'reason' => 'Erreur de communication avec la borne',
            ],
        ];

        foreach ($transactions as $transactionData) {
            Transaction::create($transactionData);
        }

        $this->command->info('✅ Transactions de démonstration créées');
    }

    private function createDemoReservations(): void
    {
        $chargingPoints = ChargingPoint::where('is_active', true)->get();
        $users = User::where('email', 'like', '%@demo.evonpower.com')->get();
        $pricingPlans = PricingPlan::all();

        if ($chargingPoints->isEmpty() || $users->isEmpty() || $pricingPlans->isEmpty()) {
            $this->command->error('❌ Bornes de recharge, utilisateurs ou plans tarifaires non trouvés');
            return;
        }

        $reservations = [
            // Réservation confirmée pour plus tard
            [
                'user_id' => $users->first()->id,
                'charging_point_id' => $chargingPoints[0]->id,
                'pricing_plan_id' => $pricingPlans[0]->id,
                'reservation_type' => 'kwh',
                'reservation_value' => 25.0,
                'estimated_duration' => 60,
                'estimated_energy' => 25.0,
                'estimated_cost' => 18.75,
                'max_duration' => 120,
                'max_energy' => 30.0,
                'status' => ReservationStatus::CONFIRMED,
                'payment_type' => PaymentType::CMI,
                'start_time' => now()->addHours(2),
                'end_time' => now()->addHours(3),
                'notes' => 'Réservation pour recharge complète',
            ],
            
            // Réservation en attente de confirmation
            [
                'user_id' => $users->first()->id,
                'charging_point_id' => $chargingPoints[1]->id,
                'pricing_plan_id' => $pricingPlans[1]->id,
                'reservation_type' => 'minute',
                'reservation_value' => 45.0,
                'estimated_duration' => 45,
                'estimated_energy' => 15.0,
                'estimated_cost' => 12.50,
                'max_duration' => 60,
                'max_energy' => 20.0,
                'status' => ReservationStatus::PENDING_CONFIRMATION,
                'payment_type' => PaymentType::OFFLINE,
                'start_time' => now()->addHours(4),
                'end_time' => now()->addHours(4)->addMinutes(45),
                'notes' => 'Réservation en attente de validation admin',
            ],
            
            // Réservation active (en cours)
            [
                'user_id' => $users->first()->id,
                'charging_point_id' => $chargingPoints[2]->id,
                'pricing_plan_id' => $pricingPlans[2]->id,
                'reservation_type' => 'kwh',
                'reservation_value' => 20.0,
                'estimated_duration' => 40,
                'estimated_energy' => 20.0,
                'estimated_cost' => 12.00,
                'max_duration' => 60,
                'max_energy' => 25.0,
                'status' => ReservationStatus::ACTIVE,
                'payment_type' => PaymentType::CMI,
                'start_time' => now()->subMinutes(15),
                'end_time' => now()->addMinutes(25),
                'actual_duration' => 15,
                'actual_energy' => 7.5,
                'actual_cost' => 4.50,
                'notes' => 'Recharge en cours',
            ],
            
            // Réservation terminée
            [
                'user_id' => $users->first()->id,
                'charging_point_id' => $chargingPoints[3]->id,
                'pricing_plan_id' => $pricingPlans[0]->id,
                'reservation_type' => 'minute',
                'reservation_value' => 30.0,
                'estimated_duration' => 30,
                'estimated_energy' => 10.0,
                'estimated_cost' => 8.50,
                'max_duration' => 45,
                'max_energy' => 15.0,
                'status' => ReservationStatus::COMPLETED,
                'payment_type' => PaymentType::CMI,
                'start_time' => now()->subHours(2),
                'end_time' => now()->subHour()->subMinutes(30),
                'actual_duration' => 28,
                'actual_energy' => 9.2,
                'actual_cost' => 7.82,
                'notes' => 'Recharge terminée avec succès',
            ],
            
            // Réservation annulée
            [
                'user_id' => $users->first()->id,
                'charging_point_id' => $chargingPoints[4]->id,
                'pricing_plan_id' => $pricingPlans[1]->id,
                'reservation_type' => 'kwh',
                'reservation_value' => 30.0,
                'estimated_duration' => 90,
                'estimated_energy' => 30.0,
                'estimated_cost' => 22.50,
                'max_duration' => 120,
                'max_energy' => 35.0,
                'status' => ReservationStatus::CANCELLED,
                'payment_type' => PaymentType::CMI,
                'start_time' => now()->subDays(1),
                'end_time' => now()->subDays(1)->addMinutes(90),
                'notes' => 'Réservation annulée par l\'utilisateur',
            ],
            
            // Réservation en attente
            [
                'user_id' => $users->first()->id,
                'charging_point_id' => $chargingPoints[5]->id,
                'pricing_plan_id' => $pricingPlans[2]->id,
                'reservation_type' => 'minute',
                'reservation_value' => 60.0,
                'estimated_duration' => 60,
                'estimated_energy' => 18.0,
                'estimated_cost' => 14.40,
                'max_duration' => 90,
                'max_energy' => 25.0,
                'status' => ReservationStatus::PENDING,
                'payment_type' => PaymentType::CMI,
                'start_time' => now()->addHours(6),
                'end_time' => now()->addHours(7),
                'notes' => 'Réservation en attente de paiement',
            ],
            
            // Réservation pour invité
            [
                'user_id' => null,
                'charging_point_id' => $chargingPoints[6]->id,
                'pricing_plan_id' => $pricingPlans[0]->id,
                'reservation_type' => 'kwh',
                'reservation_value' => 15.0,
                'estimated_duration' => 30,
                'estimated_energy' => 15.0,
                'estimated_cost' => 11.25,
                'max_duration' => 45,
                'max_energy' => 20.0,
                'status' => ReservationStatus::CONFIRMED,
                'payment_type' => PaymentType::OFFLINE,
                'start_time' => now()->addDays(1),
                'end_time' => now()->addDays(1)->addMinutes(30),
                'guest_email' => 'invite@example.com',
                'guest_phone' => '+33 6 12 34 56 78',
                'notes' => 'Réservation pour invité sans compte',
            ],
        ];

        foreach ($reservations as $reservationData) {
            Reservation::create($reservationData);
        }

        $this->command->info('✅ Réservations de démonstration créées');
    }

    private function createDemoReports(): void
    {
        $reports = [
            [
                'name' => 'Rapport Mensuel - Janvier 2024',
                'description' => 'Rapport mensuel des performances',
                'report_type' => 'monthly',
                'generated_at' => now(),
                'data' => json_encode([
                    'total_transactions' => 150,
                    'total_revenue' => 2500.00,
                    'average_session_duration' => 35,
                ]),
            ],
            [
                'name' => 'Rapport Trimestriel - Q1 2024',
                'description' => 'Rapport trimestriel des performances',
                'report_type' => 'quarterly',
                'generated_at' => now(),
                'data' => json_encode([
                    'total_transactions' => 450,
                    'total_revenue' => 7500.00,
                    'average_session_duration' => 32,
                ]),
            ],
        ];

        foreach ($reports as $reportData) {
            Report::create($reportData);
        }

        $this->command->info('✅ Rapports de démonstration créés');
    }
}

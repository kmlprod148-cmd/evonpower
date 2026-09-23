<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use App\Models\Station;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestStepByStepSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧪 Test étape par étape de la création des données...');

        // Étape 1: Créer un seul profil business
        $this->command->info('📋 Étape 1: Création d\'un profil business...');
        $this->createOneBusinessProfile();
        
        // Étape 2: Créer un seul plan tarifaire
        $this->command->info('📋 Étape 2: Création d\'un plan tarifaire...');
        $this->createOnePricingPlan();
        
        // Étape 3: Créer une seule station
        $this->command->info('📋 Étape 3: Création d\'une station...');
        $this->createOneStation();
        
        // Étape 4: Créer une seule borne
        $this->command->info('📋 Étape 4: Création d\'une borne...');
        $this->createOneChargingPoint();
        
        // Étape 5: Créer un seul utilisateur
        $this->command->info('📋 Étape 5: Création d\'un utilisateur...');
        $this->createOneUser();
        
        $this->command->info('✅ Test terminé !');
    }

    private function createOneBusinessProfile(): void
    {
        try {
            $profile = BusinessProfile::create([
                'name' => 'Test Business Profile',
                'description' => 'Profil de test simple',
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
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info("✅ Profil business créé: {$profile->name} (ID: {$profile->id})");
        } catch (\Exception $e) {
            $this->command->error("❌ Erreur création profil business: " . $e->getMessage());
        }
    }

    private function createOnePricingPlan(): void
    {
        try {
            $plan = PricingPlan::create([
                'name' => 'Test Plan',
                'description' => 'Plan de test simple',
                'price_per_kwh' => 0.30,
                'activation_fee' => 3.00,
                'minimum_charge' => 1.50,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info("✅ Plan tarifaire créé: {$plan->name} (ID: {$plan->id})");
        } catch (\Exception $e) {
            $this->command->error("❌ Erreur création plan tarifaire: " . $e->getMessage());
        }
    }

    private function createOneStation(): void
    {
        try {
            $station = Station::create([
                'name' => 'Test Station',
                'address' => '123 Rue de Test, 75000 Paris',
                'latitude' => 48.8566,
                'longitude' => 2.3522,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info("✅ Station créée: {$station->name} (ID: {$station->id})");
        } catch (\Exception $e) {
            $this->command->error("❌ Erreur création station: " . $e->getMessage());
        }
    }

    private function createOneChargingPoint(): void
    {
        try {
            // Vérifier les références
            $station = Station::first();
            $businessProfile = BusinessProfile::first();
            $pricingPlan = PricingPlan::first();
            
            if (!$station) {
                $this->command->error("❌ Aucune station trouvée");
                return;
            }
            
            if (!$businessProfile) {
                $this->command->error("❌ Aucun profil business trouvé");
                return;
            }
            
            if (!$pricingPlan) {
                $this->command->error("❌ Aucun plan tarifaire trouvé");
                return;
            }
            
            $this->command->info("🔍 Références trouvées - Station: {$station->id}, Business Profile: {$businessProfile->id}, Plan: {$pricingPlan->id}");
            
            $chargingPoint = ChargingPoint::create([
                'name' => 'Test Charging Point',
                'station_id' => $station->id,
                'business_profile_id' => $businessProfile->id,
                'pricing_plan_id' => $pricingPlan->id,
                'power_output' => 22.0,
                'connector_type' => 'Type 2',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info("✅ Borne créée: {$chargingPoint->name} (ID: {$chargingPoint->id})");
        } catch (\Exception $e) {
            $this->command->error("❌ Erreur création borne: " . $e->getMessage());
        }
    }

    private function createOneUser(): void
    {
        try {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@demo.evonpower.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info("✅ Utilisateur créé: {$user->name} (ID: {$user->id})");
        } catch (\Exception $e) {
            $this->command->error("❌ Erreur création utilisateur: " . $e->getMessage());
        }
    }
}

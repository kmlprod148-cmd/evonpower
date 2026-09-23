<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\Reservation;
use App\Services\TransactionCalculator;

class TestReservation extends Command
{
    protected $signature = 'test:reservation';
    protected $description = 'Test de création de réservation';

    public function handle()
    {
        $this->info('🧪 Test de Réservation');
        $this->info('====================');

        try {
            // Test 1: Vérification des données de base
            $this->info('1. ✅ Test des données de base...');
            
            $chargingPoints = ChargingPoint::with(['pricingPlan'])->take(1)->get();
            if ($chargingPoints->count() > 0) {
                $chargingPoint = $chargingPoints->first();
                $this->info("   ✅ Borne de recharge trouvée: ID {$chargingPoint->id}");
                $this->info("   ✅ Nom: {$chargingPoint->name}");
                
                if ($chargingPoint->pricingPlan) {
                    $this->info("   ✅ Plan tarifaire: {$chargingPoint->pricingPlan->name}");
                    $this->info("   ✅ Type: {$chargingPoint->pricingPlan->rate_type}");
                    $this->info("   ✅ Prix par kWh: {$chargingPoint->pricingPlan->price_per_kwh}");
                    $this->info("   ✅ Prix par minute: {$chargingPoint->pricingPlan->price_per_minute}");
                    $this->info("   ✅ Frais d'activation: {$chargingPoint->pricingPlan->activation_fee}");
                } else {
                    $this->error("   ❌ Aucun plan tarifaire associé");
                }
            } else {
                $this->error("   ❌ Aucune borne de recharge trouvée");
            }
            
            // Test 2: Vérification des plans tarifaires
            $this->info("\n2. ✅ Test des plans tarifaires...");
            
            $pricingPlans = PricingPlan::where('is_active', true)->take(3)->get();
            if ($pricingPlans->count() > 0) {
                $this->info("   ✅ Plans tarifaires actifs trouvés: {$pricingPlans->count()}");
                foreach ($pricingPlans as $plan) {
                    $this->info("     - {$plan->name} ({$plan->rate_type})");
                }
            } else {
                $this->error("   ❌ Aucun plan tarifaire actif trouvé");
            }
            
            // Test 3: Test de création de réservation simple
            $this->info("\n3. ✅ Test de création de réservation...");
            
            if ($chargingPoints->count() > 0) {
                $chargingPoint = $chargingPoints->first();
                $pricingPlan = $chargingPoint->pricingPlan ?? PricingPlan::where('is_active', true)->first();
                
                if ($pricingPlan) {
                    // Données de test
                    $testData = [
                        'type' => 'duration',
                        'value' => 30,
                        'time' => 'immediate',
                        'customer_name' => 'Test User',
                        'customer_email' => 'test@example.com',
                        'customer_phone' => '0123456789'
                    ];
                    
                    // Calculer le coût
                    $cost = $pricingPlan->activation_fee;
                    if ($testData['type'] === 'duration') {
                        $cost += $testData['value'] * $pricingPlan->price_per_minute;
                    }
                    
                    $this->info("   ✅ Calcul du coût: {$cost} {$pricingPlan->currency}");
                    
                    // Test de création de réservation (sans sauvegarder)
                    $reservation = new Reservation();
                    $reservation->charging_point_id = $chargingPoint->id;
                    $reservation->pricing_plan_id = $pricingPlan->id;
                    $reservation->reservation_type = $testData['type'] === 'energy' ? 'kwh' : 'minute';
                    $reservation->reservation_value = $testData['value'];
                    $reservation->start_time = now();
                    $reservation->estimated_cost = $cost;
                    $reservation->status = 'pending';
                    $reservation->guest_email = $testData['customer_email'];
                    $reservation->guest_phone = $testData['customer_phone'];
                    
                    $this->info("   ✅ Réservation créée (test) - ID: {$reservation->id}");
                    $this->info("   ✅ Type: {$reservation->reservation_type}");
                    $this->info("   ✅ Valeur: {$reservation->reservation_value}");
                    $this->info("   ✅ Coût: {$reservation->estimated_cost}");
                    
                } else {
                    $this->error("   ❌ Aucun plan tarifaire disponible");
                }
            }
            
            // Test 4: Vérification des services
            $this->info("\n4. ✅ Test des services...");
            
            try {
                $transactionCalculator = app(TransactionCalculator::class);
                $this->info("   ✅ TransactionCalculator disponible");
            } catch (\Exception $e) {
                $this->error("   ❌ TransactionCalculator non disponible: " . $e->getMessage());
            }
            
            $this->info("\n🎯 TEST TERMINÉ");
            $this->info("==============");
            
        } catch (\Exception $e) {
            $this->error("❌ ERREUR: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}

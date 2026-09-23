<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\TransactionRepartition;
use App\Services\TransactionCalculator;
use App\Services\PricingPlanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestControllerStepByStep extends Command
{
    protected $signature = 'test:controller-step-by-step';
    protected $description = 'Test du contrôleur étape par étape';

    public function handle()
    {
        $this->info('🔍 Test du Contrôleur Étape par Étape');
        $this->info('====================================');

        try {
            // Récupérer les données de base
            $chargingPoint = ChargingPoint::with(['pricingPlan'])->first();
            $pricingPlan = $chargingPoint->pricingPlan ?? PricingPlan::where('is_active', true)->first();

            $this->info("✅ ChargingPoint: {$chargingPoint->name} (ID: {$chargingPoint->id})");
            $this->info("✅ PricingPlan: {$pricingPlan->name} (ID: {$pricingPlan->id})");

            // Données de test
            $requestData = [
                'type' => 'duration',
                'value' => 30,
                'time' => 'immediate',
                'customer_name' => 'Test User',
                'customer_email' => 'test@example.com',
                'customer_phone' => '0123456789'
            ];

            $this->info("\n1. ✅ Test de validation...");
            
            $validator = \Validator::make($requestData, [
                'type' => 'required|in:energy,duration',
                'value' => 'required|numeric|min:1',
                'time' => 'required|string',
                'customer_name' => 'nullable|string|max:255',
                'customer_email' => 'nullable|email|max:255',
                'customer_phone' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                $this->error("❌ Validation échouée: " . json_encode($validator->errors()));
                return;
            }
            
            $this->info("   ✅ Validation réussie");

            $this->info("\n2. ✅ Test de calcul du coût...");
            
            $cost = $pricingPlan->activation_fee;
            if ($requestData['type'] === 'duration') {
                $cost += $requestData['value'] * $pricingPlan->price_per_minute;
            }
            
            $this->info("   ✅ Coût calculé: {$cost} {$pricingPlan->currency}");

            $this->info("\n3. ✅ Test de création de réservation...");
            
            DB::beginTransaction();
            
            try {
                // Créer la réservation
                $reservation = new Reservation();
                $reservation->charging_point_id = $chargingPoint->id;
                $reservation->pricing_plan_id = $pricingPlan->id;
                $reservation->reservation_type = $requestData['type'] === 'energy' ? 'kwh' : 'minute';
                $reservation->reservation_value = $requestData['value'];
                $reservation->start_time = now();
                $reservation->estimated_cost = $cost;
                $reservation->status = 'pending';
                $reservation->guest_email = $requestData['customer_email'];
                $reservation->guest_phone = $requestData['customer_phone'];
                
                $this->info("   📝 Sauvegarde de la réservation...");
                $reservation->save();
                $this->info("   ✅ Réservation créée - ID: {$reservation->id}");

                $this->info("\n4. ✅ Test de création de transaction...");
                
                $transactionId = 'TEST-' . strtoupper(uniqid());
                $transaction = new Transaction();
                $transaction->transaction_id = $transactionId;
                $transaction->charging_point_id = $chargingPoint->id;
                $transaction->user_id = null;
                $transaction->start_timestamp = $reservation->start_time;
                $transaction->status = 'pending';
                $transaction->auth_method = 'public_reservation';
                $transaction->pricing_plan_id = $pricingPlan->id;
                $transaction->meter_start = 0;
                $transaction->price_total = $cost;
                $transaction->currency = $pricingPlan->currency ?? 'EUR';
                
                $this->info("   📝 Sauvegarde de la transaction...");
                $transaction->save();
                $this->info("   ✅ Transaction créée - ID: {$transaction->id}");

                $this->info("\n5. ✅ Test de création de répartition...");
                
                $repartition = [
                    'admin_amount' => $cost * 0.1,
                    'integrator_amount' => $cost * 0.3,
                    'operator_amount' => $cost * 0.6,
                ];

                $transactionRepartition = TransactionRepartition::createFromCalculation($transaction, $repartition);
                $this->info("   ✅ Répartition créée - ID: {$transactionRepartition->id}");

                // Valider la transaction
                DB::commit();
                $this->info("   ✅ Transaction validée");

                $this->info("\n🎉 TOUS LES TESTS RÉUSSIS !");
                $this->info("==========================");
                $this->info("Réservation ID: {$reservation->id}");
                $this->info("Transaction ID: {$transaction->id}");
                $this->info("Répartition ID: {$transactionRepartition->id}");
                $this->info("Coût total: {$cost} {$pricingPlan->currency}");

            } catch (\Exception $e) {
                DB::rollback();
                $this->error("❌ Erreur lors de la création: " . $e->getMessage());
                $this->error("Fichier: " . $e->getFile() . ":" . $e->getLine());
                $this->error("Stack trace: " . $e->getTraceAsString());
                throw $e;
            }

        } catch (\Exception $e) {
            $this->error("❌ ERREUR GLOBALE: " . $e->getMessage());
            $this->error("Fichier: " . $e->getFile() . ":" . $e->getLine());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}

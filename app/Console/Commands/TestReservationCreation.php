<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\TransactionRepartition;
use App\Services\TransactionCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestReservationCreation extends Command
{
    protected $signature = 'test:reservation-creation';
    protected $description = 'Test de création complète de réservation';

    public function handle()
    {
        $this->info('🧪 Test de Création de Réservation');
        $this->info('==================================');

        try {
            // Récupérer une borne de recharge
            $chargingPoint = ChargingPoint::with(['pricingPlan'])->first();
            if (!$chargingPoint) {
                $this->error("❌ Aucune borne de recharge trouvée");
                return;
            }

            $this->info("✅ Borne trouvée: {$chargingPoint->name} (ID: {$chargingPoint->id})");

            // Récupérer le plan tarifaire
            $pricingPlan = $chargingPoint->pricingPlan ?? PricingPlan::where('is_active', true)->first();
            if (!$pricingPlan) {
                $this->error("❌ Aucun plan tarifaire trouvé");
                return;
            }

            $this->info("✅ Plan tarifaire: {$pricingPlan->name}");

            // Données de test
            $testData = [
                'type' => 'duration',
                'value' => 30,
                'time' => 'immediate',
                'customer_name' => 'Test User',
                'customer_email' => 'test@example.com',
                'customer_phone' => '0123456789'
            ];

            $this->info("✅ Données de test: " . json_encode($testData));

            // Calculer le coût
            $cost = $pricingPlan->activation_fee;
            if ($testData['type'] === 'duration') {
                $cost += $testData['value'] * $pricingPlan->price_per_minute;
            }

            $this->info("✅ Coût calculé: {$cost} {$pricingPlan->currency}");

            // Démarrer une transaction de base de données
            DB::beginTransaction();

            try {
                // Créer la réservation
                $this->info("\n1. ✅ Création de la réservation...");
                
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
                $reservation->save();

                $this->info("   ✅ Réservation créée - ID: {$reservation->id}");

                // Créer la transaction
                $this->info("\n2. ✅ Création de la transaction...");
                
                $transactionId = 'TEST-' . strtoupper(uniqid());
                $transaction = new Transaction();
                $transaction->transaction_id = $transactionId;
                $transaction->charging_point_id = $chargingPoint->id;
                $transaction->user_id = null; // Réservation publique
                $transaction->start_timestamp = $reservation->start_time;
                $transaction->status = 'pending';
                $transaction->auth_method = 'public_reservation';
                $transaction->pricing_plan_id = $pricingPlan->id;
                $transaction->meter_start = 0;
                $transaction->price_total = $cost;
                $transaction->currency = $pricingPlan->currency ?? 'EUR';
                $transaction->save();

                $this->info("   ✅ Transaction créée - ID: {$transaction->id}");

                // Lier la réservation à la transaction
                $reservation->order_id = $transaction->id;
                $reservation->save();

                $this->info("   ✅ Réservation liée à la transaction");

                // Créer la répartition
                $this->info("\n3. ✅ Création de la répartition...");
                
                $repartition = [
                    'admin_amount' => $cost * 0.1, // 10%
                    'integrator_amount' => $cost * 0.3, // 30%
                    'operator_amount' => $cost * 0.6, // 60%
                ];

                $transactionRepartition = TransactionRepartition::createFromCalculation($transaction, $repartition);
                $this->info("   ✅ Répartition créée - ID: {$transactionRepartition->id}");
                $this->info("   ✅ Admin: {$transactionRepartition->admin_amount}");
                $this->info("   ✅ Integrator: {$transactionRepartition->integrator_amount}");
                $this->info("   ✅ Operator: {$transactionRepartition->operator_amount}");

                // Valider la transaction
                DB::commit();

                $this->info("\n🎉 RÉSERVATION CRÉÉE AVEC SUCCÈS !");
                $this->info("=================================");
                $this->info("Réservation ID: {$reservation->id}");
                $this->info("Transaction ID: {$transaction->id}");
                $this->info("Répartition ID: {$transactionRepartition->id}");
                $this->info("Coût total: {$cost} {$pricingPlan->currency}");

            } catch (\Exception $e) {
                DB::rollback();
                $this->error("❌ Erreur lors de la création: " . $e->getMessage());
                throw $e;
            }

        } catch (\Exception $e) {
            $this->error("❌ ERREUR: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
        }
    }
}

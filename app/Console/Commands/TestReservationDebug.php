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

class TestReservationDebug extends Command
{
    protected $signature = 'test:reservation-debug';
    protected $description = 'Test de débogage de réservation avec logs détaillés';

    public function handle()
    {
        $this->info('🔍 Test de Débogage de Réservation');
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

            // Test de validation des données
            $this->info("\n1. ✅ Test de validation des données...");
            
            $validator = \Validator::make($testData, [
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

            // Test de création de réservation avec gestion d'erreurs détaillée
            $this->info("\n2. ✅ Test de création de réservation...");
            
            DB::beginTransaction();
            
            try {
                // Créer la réservation
                $this->info("   📝 Création de la réservation...");
                
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
                
                $this->info("   📝 Sauvegarde de la réservation...");
                $reservation->save();
                $this->info("   ✅ Réservation créée - ID: {$reservation->id}");

                // Créer la transaction
                $this->info("   📝 Création de la transaction...");
                
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

                // Pour les réservations publiques, on ne lie pas à order_id
                // car cela nécessite une table orders qui n'est pas utilisée pour les réservations publiques
                $this->info("   📝 Réservation publique (pas de liaison order_id)...");
                // $reservation->order_id = $transaction->id; // Commenté pour éviter l'erreur de contrainte
                $reservation->save();
                $this->info("   ✅ Réservation sauvegardée");

                // Créer la répartition
                $this->info("   📝 Création de la répartition...");
                
                $repartition = [
                    'admin_amount' => $cost * 0.1,
                    'integrator_amount' => $cost * 0.3,
                    'operator_amount' => $cost * 0.6,
                ];

                $this->info("   📝 Calcul de répartition: " . json_encode($repartition));
                
                $transactionRepartition = TransactionRepartition::createFromCalculation($transaction, $repartition);
                $this->info("   ✅ Répartition créée - ID: {$transactionRepartition->id}");

                // Valider la transaction
                DB::commit();
                $this->info("   ✅ Transaction validée");

                $this->info("\n🎉 RÉSERVATION CRÉÉE AVEC SUCCÈS !");
                $this->info("=================================");
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

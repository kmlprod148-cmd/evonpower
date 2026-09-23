<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * Service centralisé pour calculer les montants HT et TTC de manière cohérente
 * 
 * Logique:
 * - TTC = Montant total payé par le client (avec TVA) = price_total ou estimated_cost
 * - HT = Montant sans TVA = TTC / (1 + taux TVA)
 */
class TransactionHTTTCService
{
    /**
     * Calcule les montants HT et TTC pour une transaction
     * 
     * @param Transaction|array $transaction Transaction ou tableau normalisé
     * @return array ['ht' => float, 'ttc' => float, 'vat_rate' => float|null, 'vat_amount' => float]
     */
    public function calculateHTTTC($transaction): array
    {
        // Extraire le modèle Transaction si c'est un tableau normalisé
        $transactionModel = null;
        $reservation = null;
        
        if (is_array($transaction)) {
            $transactionModel = $transaction['transaction'] ?? null;
            $type = $transaction['type'] ?? 'wallet';
            
            // Pour les transactions wallet, HT = TTC = amount
            if ($type === 'wallet') {
                $amount = abs($transaction['amount'] ?? 0);
                return [
                    'ht' => round($amount, 2),
                    'ttc' => round($amount, 2),
                    'vat_rate' => null,
                    'vat_amount' => 0,
                ];
            }
        } else {
            $transactionModel = $transaction;
        }
        
        if (!$transactionModel) {
            Log::warning('TransactionHTTTCService: Transaction model not found', [
                'transaction' => is_array($transaction) ? ($transaction['id'] ?? 'array') : ($transaction->id ?? null)
            ]);
            $amount = is_array($transaction) ? abs($transaction['amount'] ?? 0) : ($transaction->amount ?? 0);
            return [
                'ht' => round($amount, 2),
                'ttc' => round($amount, 2),
                'vat_rate' => null,
                'vat_amount' => 0,
            ];
        }
        
        // Charger la réservation si nécessaire
        if (!$transactionModel->relationLoaded('reservation')) {
            $transactionModel->load('reservation.pricingPlan.vatRate');
        }
        $reservation = $transactionModel->reservation;
        
        // 1. Déterminer le montant TTC (montant total payé par le client avec TVA)
        $amountTTC = $this->getAmountTTC($transactionModel, $reservation);
        
        // 2. CORRECTION: Si price_tax est déjà stocké, l'utiliser directement
        // Cela garantit la cohérence avec les calculs effectués lors de la création de la transaction
        if ($transactionModel->price_tax && $transactionModel->price_tax > 0 && $transactionModel->price_total && $transactionModel->price_total > 0) {
            // Utiliser les valeurs stockées directement
            $amountTTC = (float) $transactionModel->price_total;
            $vatAmount = (float) $transactionModel->price_tax;
            $amountHT = round($amountTTC - $vatAmount, 2);
            
            // Calculer le taux de TVA à partir des valeurs stockées pour vérification
            if ($amountHT > 0) {
                $vatRate = ($vatAmount / $amountHT);
            } else {
                $vatRate = null;
            }
            
            Log::info('TransactionHTTTCService: Utilisation des valeurs price_tax et price_total stockées', [
                'transaction_id' => $transactionModel->id,
                'price_total' => $amountTTC,
                'price_tax' => $vatAmount,
                'amount_ht' => $amountHT,
                'vat_rate_calculated' => $vatRate ? ($vatRate * 100) . '%' : 'N/A'
            ]);
        } else {
            // 3. Récupérer le taux de TVA si price_tax n'est pas disponible
            $vatRate = $this->getVATRate($transactionModel, $reservation);
            
            // 4. Calculer HT à partir du TTC
            $amountHT = $this->calculateHTFromTTC($amountTTC, $vatRate);
            
            // 5. Calculer le montant de TVA
            $vatAmount = round($amountTTC - $amountHT, 2);
        }
        
        return [
            'ht' => round($amountHT, 2),
            'ttc' => round($amountTTC, 2),
            'vat_rate' => $vatRate,
            'vat_amount' => round($vatAmount, 2),
        ];
    }
    
    /**
     * Récupère le montant TTC (montant total payé par le client)
     *
     * Priorité (du plus précis au plus large) :
     * 1. price_total de la transaction
     * 2. total_amount de la transaction  (alias parfois utilisé)
     * 3. estimated_cost de la réservation
     * 4. actual_cost de la réservation   (coût réel après session)
     * 5. amount de la transaction
     * 6. Somme des composants prix (price_energy + price_time + price_service)
     */
    protected function getAmountTTC($transactionModel, $reservation): float
    {
        // Priorité 1: price_total
        if (!empty($transactionModel->price_total) && (float) $transactionModel->price_total > 0) {
            return (float) $transactionModel->price_total;
        }

        // Priorité 2: total_amount (alias)
        if (!empty($transactionModel->total_amount) && (float) $transactionModel->total_amount > 0) {
            return (float) $transactionModel->total_amount;
        }

        // Priorité 3: estimated_cost de la réservation
        if ($reservation && !empty($reservation->estimated_cost) && (float) $reservation->estimated_cost > 0) {
            return (float) $reservation->estimated_cost;
        }

        // Priorité 4: actual_cost de la réservation (coût final)
        if ($reservation && !empty($reservation->actual_cost) && (float) $reservation->actual_cost > 0) {
            return (float) $reservation->actual_cost;
        }

        // Priorité 5: amount
        if (!empty($transactionModel->amount) && abs((float) $transactionModel->amount) > 0) {
            return abs((float) $transactionModel->amount);
        }

        // Priorité 6: Somme des composants prix
        $componentSum = (float) ($transactionModel->price_energy ?? 0)
            + (float) ($transactionModel->price_time ?? 0)
            + (float) ($transactionModel->price_service ?? 0);

        if ($componentSum > 0) {
            return $componentSum;
        }

        return 0.0;
    }
    
    /**
     * Récupère le taux de TVA
     * 
     * Priorité:
     * 1. vatRate depuis reservation->pricingPlan->vatRate
     * 2. vat_rate depuis price_details de la transaction
     * 3. null (pas de TVA)
     */
    protected function getVATRate($transactionModel, $reservation): ?float
    {
        // Priorité 1: vatRate depuis pricingPlan
        if ($reservation) {
            if (!$reservation->relationLoaded('pricingPlan')) {
                $reservation->load('pricingPlan.vatRate');
            }
            
            if ($reservation->pricingPlan && $reservation->pricingPlan->vatRate) {
                $rate = (float) $reservation->pricingPlan->vatRate->rate;
                if ($rate > 0) {
                    return $rate / 100; // Convertir en décimal (ex: 20% -> 0.20)
                }
            }
        }
        
        // Priorité 2: vat_rate depuis price_details
        if ($transactionModel->price_details) {
            $priceDetails = is_string($transactionModel->price_details) 
                ? json_decode($transactionModel->price_details, true) 
                : $transactionModel->price_details;
            
            if (isset($priceDetails['vat_rate']) && $priceDetails['vat_rate'] > 0) {
                $rate = (float) $priceDetails['vat_rate'];
                return $rate / 100; // Convertir en décimal si nécessaire
            }
        }
        
        // Priorité 3: price_tax peut indiquer la présence de TVA
        // Si price_tax existe et price_total existe, on peut calculer le taux
        // CORRECTION: Utiliser price_tax et price_total pour calculer le taux de TVA
        if ($transactionModel->price_total && $transactionModel->price_tax && $transactionModel->price_tax > 0) {
            $priceTotal = (float) $transactionModel->price_total; // TTC
            $priceTax = (float) $transactionModel->price_tax; // Montant de TVA
            $priceHT = $priceTotal - $priceTax; // HT = TTC - TVA
            
            if ($priceHT > 0) {
                // Taux de TVA = (TVA / HT) * 100
                // Exemple: HT = 100€, TVA = 20€, taux = (20/100) * 100 = 20%
                $calculatedRate = ($priceTax / $priceHT) * 100;
                if ($calculatedRate > 0 && $calculatedRate <= 100) {
                    $rateDecimal = $calculatedRate / 100;
                    Log::info('TransactionHTTTCService: Taux de TVA calculé depuis price_tax et price_total', [
                        'transaction_id' => $transactionModel->id,
                        'price_total' => $priceTotal,
                        'price_tax' => $priceTax,
                        'price_ht' => $priceHT,
                        'vat_rate_percentage' => $calculatedRate . '%',
                        'vat_rate_decimal' => $rateDecimal
                    ]);
                    return $rateDecimal;
                }
            }
        }
        
        return null; // Pas de TVA
    }
    
    /**
     * Calcule HT à partir du TTC et du taux de TVA
     * 
     * Formule: HT = TTC / (1 + taux TVA)
     * Si pas de TVA: HT = TTC
     */
    protected function calculateHTFromTTC(float $amountTTC, ?float $vatRate): float
    {
        if ($vatRate === null || $vatRate <= 0) {
            return $amountTTC;
        }
        
        // HT = TTC / (1 + taux TVA)
        // Exemple: TTC = 120€, TVA = 20% (0.20)
        // HT = 120 / (1 + 0.20) = 120 / 1.20 = 100€
        return $amountTTC / (1 + $vatRate);
    }
    
    /**
     * Méthode helper pour obtenir uniquement le montant HT
     */
    public function getAmountHT($transaction): float
    {
        $amounts = $this->calculateHTTTC($transaction);
        return $amounts['ht'];
    }
    
    /**
     * Méthode helper pour obtenir uniquement le montant TTC
     */
    public function getTTC($transaction): float
    {
        $amounts = $this->calculateHTTTC($transaction);
        return $amounts['ttc'];
    }
}


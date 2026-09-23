<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\TransactionRepartition;
use App\Services\TransactionCalculator;

class FixTransactionConsistency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:transaction-consistency {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige la cohérence d\'une transaction spécifique';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🔧 Correction de la cohérence pour la transaction ID {$transactionId}...");
        $this->newLine();

        try {
            // Récupérer la transaction
            $transaction = Transaction::with(['chargingPoint', 'chargingPoint.businessProfile'])->find($transactionId);
            
            if (!$transaction) {
                $this->error("❌ Transaction ID {$transactionId} non trouvée");
                return 1;
            }
            
            $this->info("📋 Transaction trouvée:");
            $this->info("  - Montant: {$transaction->price_total} {$transaction->currency}");
            $this->info("  - Status: {$transaction->status}");
            
            // Vérifier la répartition existante
            $repartition = $transaction->repartitions()->first();
            
            if ($repartition) {
                $this->info("📊 Répartition existante:");
                $this->info("  - Admin: {$repartition->admin_amount}€");
                $this->info("  - Intégrateur: {$repartition->integrator_amount}€");
                $this->info("  - Opérateur: {$repartition->operator_amount}€");
                $this->info("  - Total: {$repartition->getTotalAmount()}€");
                
                $isConsistent = $repartition->isConsistent();
                $this->info("  - Cohérente: " . ($isConsistent ? 'Oui' : 'Non'));
                
                if (!$isConsistent) {
                    $transactionAmount = $transaction->amount ?? $transaction->price_total;
                    $difference = abs($transactionAmount - $repartition->getTotalAmount());
                    $this->warn("  - Différence: {$difference}€");
                }
            } else {
                $this->info("📊 Aucune répartition existante");
            }
            
            $this->newLine();
            
            // Recalculer la répartition
            $this->info("🧮 Recalcul de la répartition...");
            $calculator = new TransactionCalculator();
            $calculation = $calculator->calculate($transaction);
            
            $this->table(
                ['Rôle', 'Ancien', 'Nouveau', 'Différence'],
                [
                    [
                        'Admin',
                        $repartition ? number_format($repartition->admin_amount, 2) . '€' : 'N/A',
                        number_format($calculation['admin'], 2) . '€',
                        $repartition ? number_format($calculation['admin'] - $repartition->admin_amount, 2) . '€' : 'N/A'
                    ],
                    [
                        'Intégrateur',
                        $repartition ? number_format($repartition->integrator_amount, 2) . '€' : 'N/A',
                        number_format($calculation['integrator'], 2) . '€',
                        $repartition ? number_format($calculation['integrator'] - $repartition->integrator_amount, 2) . '€' : 'N/A'
                    ],
                    [
                        'Opérateur',
                        $repartition ? number_format($repartition->operator_amount, 2) . '€' : 'N/A',
                        number_format($calculation['operator'], 2) . '€',
                        $repartition ? number_format($calculation['operator'] - $repartition->operator_amount, 2) . '€' : 'N/A'
                    ],
                    [
                        'Total',
                        $repartition ? number_format($repartition->getTotalAmount(), 2) . '€' : 'N/A',
                        number_format($calculation['total'], 2) . '€',
                        $repartition ? number_format($calculation['total'] - $repartition->getTotalAmount(), 2) . '€' : 'N/A'
                    ]
                ]
            );
            
            // Mettre à jour la répartition
            $this->newLine();
            if ($this->confirm('Voulez-vous mettre à jour la répartition avec les nouveaux calculs ?')) {
                $repartition = TransactionRepartition::updateOrCreate(
                    ['transaction_id' => $transaction->id],
                    [
                        'admin_amount' => $calculation['admin'],
                        'integrator_amount' => $calculation['integrator'],
                        'operator_amount' => $calculation['operator'],
                    ]
                );
                
                $this->info("✅ Répartition mise à jour");
                
                // Vérifier la cohérence finale
                $isConsistent = $repartition->isConsistent();
                if ($isConsistent) {
                    $this->info("🎉 La répartition est maintenant cohérente !");
                } else {
                    $this->warn("⚠️ La répartition reste incohérente");
                }
            } else {
                $this->info("ℹ️ Aucune modification effectuée");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            return 1;
        }
    }
}

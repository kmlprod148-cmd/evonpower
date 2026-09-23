<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FixTransactionPriceTotalDisplay extends Command
{
    protected $signature = 'transactions:fix-price-total-display {--dry-run : Afficher seulement ce qui serait corrigé}';
    protected $description = 'Corrige l\'affichage du Montant Total Facturé pour les transactions avec price_total null ou 0';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('🔧 Correction de l\'affichage du Montant Total Facturé...');
        
        if ($dryRun) {
            $this->info('Mode DRY-RUN activé - aucune modification ne sera effectuée');
        }

        // Trouver les transactions avec des problèmes de price_total
        $problematicTransactions = Transaction::where(function($query) {
            $query->whereNull('price_total')
                  ->orWhere('price_total', 0)
                  ->orWhere('price_total', '');
        })->with(['reservation'])->get();

        if ($problematicTransactions->isEmpty()) {
            $this->info('✅ Aucune transaction avec des problèmes de price_total trouvée.');
            return;
        }

        $this->info("Trouvé {$problematicTransactions->count()} transactions à corriger.");

        $bar = $this->output->createProgressBar($problematicTransactions->count());
        $bar->start();

        $fixed = 0;
        $errors = 0;

        foreach ($problematicTransactions as $transaction) {
            try {
                $newPriceTotal = $this->calculateCorrectPriceTotal($transaction);
                
                if ($newPriceTotal > 0) {
                    if (!$dryRun) {
                        $transaction->update(['price_total' => $newPriceTotal]);
                        Log::info("Price total corrected for transaction #{$transaction->id}", [
                            'old_price_total' => $transaction->getOriginal('price_total'),
                            'new_price_total' => $newPriceTotal,
                            'amount' => $transaction->amount
                        ]);
                    }
                    $fixed++;
                } else {
                    $this->warn("Impossible de calculer un price_total valide pour la transaction #{$transaction->id}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("Erreur lors de la correction de la transaction #{$transaction->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($dryRun) {
            $this->info("DRY-RUN: {$fixed} transactions auraient été corrigées, {$errors} erreurs.");
        } else {
            $this->info("✅ {$fixed} transactions corrigées, {$errors} erreurs.");
        }
    }

    private function calculateCorrectPriceTotal(Transaction $transaction): float
    {
        // Méthode 1: Utiliser amount si disponible
        if ($transaction->amount && $transaction->amount > 0) {
            return $transaction->amount;
        }
        
        // Méthode 2: Calculer à partir des composants
        $priceEnergy = (float) ($transaction->price_energy ?? 0);
        $priceTime = (float) ($transaction->price_time ?? 0);
        $priceService = (float) ($transaction->price_service ?? 0);
        $priceTax = (float) ($transaction->price_tax ?? 0);
        $activationFee = (float) ($transaction->activation_fee ?? 0);
        
        $calculatedTotal = $priceEnergy + $priceTime + $priceService + $priceTax + $activationFee;
        
        if ($calculatedTotal > 0) {
            return $calculatedTotal;
        }
        
        // Méthode 3: Utiliser le coût estimé de la réservation
        if ($transaction->reservation && $transaction->reservation->estimated_cost) {
            return $transaction->reservation->estimated_cost;
        }
        
        return 0;
    }
}

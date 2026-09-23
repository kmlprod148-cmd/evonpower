<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Services\TransactionCalculator;
use Illuminate\Support\Facades\Log;

class RecalculateTransactionRepartitions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:recalculate-repartitions 
                            {--transaction-id= : Recalculer une transaction spécifique}
                            {--batch-size=100 : Nombre de transactions à traiter par lot}
                            {--force : Forcer le recalcul même si une répartition existe déjà}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcule les répartitions de toutes les transactions ou d\'une transaction spécifique';

    protected TransactionCalculator $calculator;

    /**
     * Create a new command instance.
     */
    public function __construct(TransactionCalculator $calculator)
    {
        parent::__construct();
        $this->calculator = $calculator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->option('transaction-id');
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');

        if ($transactionId) {
            return $this->recalculateSingleTransaction($transactionId, $force);
        }

        return $this->recalculateAllTransactions($batchSize, $force);
    }

    /**
     * Recalcule une transaction spécifique
     */
    protected function recalculateSingleTransaction(int $transactionId, bool $force): int
    {
        $transaction = Transaction::with(['chargingPoint.businessProfile', 'chargingPoint.integrator.businessProfile'])
            ->find($transactionId);

        if (!$transaction) {
            $this->error("Transaction #{$transactionId} non trouvée.");
            return 1;
        }

        $this->info("Recalcul de la transaction #{$transactionId}...");

        try {
            $repartition = $this->calculator->createRepartition($transaction);
            $calculation = $this->calculator->calculate($transaction);

            $this->info("✅ Répartition recalculée avec succès:");
            $this->table(
                ['Rôle', 'Montant', 'Pourcentage'],
                [
                    ['Admin', number_format($calculation['admin'], 2) . ' €', $this->getPercentage($calculation['admin'], $calculation['total'])],
                    ['Intégrateur', number_format($calculation['integrator'], 2) . ' €', $this->getPercentage($calculation['integrator'], $calculation['total'])],
                    ['Opérateur', number_format($calculation['operator'], 2) . ' €', $this->getPercentage($calculation['operator'], $calculation['total'])],
                    ['Total', number_format($calculation['total'], 2) . ' €', '100%'],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du recalcul: " . $e->getMessage());
            Log::error('Erreur recalcul transaction', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            return 1;
        }
    }

    /**
     * Recalcule toutes les transactions
     */
    protected function recalculateAllTransactions(int $batchSize, bool $force): int
    {
        $this->info("Recalcul des répartitions de toutes les transactions...");

        $query = Transaction::with(['chargingPoint.businessProfile', 'chargingPoint.integrator.businessProfile']);

        if (!$force) {
            // Ne traiter que les transactions sans répartition
            $query->whereDoesntHave('repartitions');
        }

        $totalTransactions = $query->count();

        if ($totalTransactions === 0) {
            $this->info("Aucune transaction à traiter.");
            return 0;
        }

        $this->info("{$totalTransactions} transactions à traiter...");

        $bar = $this->output->createProgressBar($totalTransactions);
        $bar->start();

        $processed = 0;
        $errors = 0;

        $query->chunk($batchSize, function ($transactions) use ($bar, &$processed, &$errors) {
            foreach ($transactions as $transaction) {
                try {
                    $this->calculator->createRepartition($transaction);
                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Erreur recalcul transaction', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ]);
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info("✅ Traitement terminé:");
        $this->info("  - Transactions traitées: {$processed}");
        $this->info("  - Erreurs: {$errors}");

        if ($errors > 0) {
            $this->warn("⚠️  {$errors} erreurs ont été enregistrées dans les logs.");
        }

        return 0;
    }

    /**
     * Calcule le pourcentage
     */
    protected function getPercentage(float $amount, float $total): string
    {
        if ($total == 0) {
            return '0%';
        }
        return number_format(($amount / $total) * 100, 1) . '%';
    }
}

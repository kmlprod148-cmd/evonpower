<?php

namespace App\Console\Commands;

use App\Services\TransactionRepartitionService;
use Illuminate\Console\Command;

class ProcessTransactionRepartitions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:process-repartitions {--limit=100 : Nombre maximum de transactions à traiter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Traite les transactions sans répartition et calcule leur répartition automatiquement';

    protected TransactionRepartitionService $repartitionService;

    public function __construct(TransactionRepartitionService $repartitionService)
    {
        parent::__construct();
        $this->repartitionService = $repartitionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Traitement des répartitions de transactions...');
        
        $limit = $this->option('limit');
        
        try {
            $processed = $this->repartitionService->processUnprocessedTransactions();
            
            $this->info("✅ {$processed} transactions traitées avec succès");
            
            if ($processed > 0) {
                $this->info('📊 Statistiques de répartition:');
                $stats = $this->repartitionService->getRepartitionStats();
                
                $this->table(
                    ['Métrique', 'Valeur'],
                    [
                        ['Total transactions', $stats['total_transactions']],
                        ['Total admin', $stats['total_admin_amount'] . ' EUR'],
                        ['Total intégrateur', $stats['total_integrator_amount'] . ' EUR'],
                        ['Total opérateur', $stats['total_operator_amount'] . ' EUR'],
                        ['Moyenne admin', $stats['avg_admin_amount'] . ' EUR'],
                        ['Moyenne intégrateur', $stats['avg_integrator_amount'] . ' EUR'],
                        ['Moyenne opérateur', $stats['avg_operator_amount'] . ' EUR']
                    ]
                );
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors du traitement: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}

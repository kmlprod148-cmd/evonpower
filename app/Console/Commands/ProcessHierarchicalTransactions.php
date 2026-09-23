<?php

namespace App\Console\Commands;

use App\Services\AutoTransactionService;
use Illuminate\Console\Command;

class ProcessHierarchicalTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:process-hierarchical 
                            {--reservation-id= : ID de la réservation spécifique à traiter}
                            {--pending : Traiter toutes les réservations en attente}
                            {--check : Vérifier les transactions manquantes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Traiter automatiquement les transactions hiérarchiques selon la logique métier';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $autoTransactionService = app(AutoTransactionService::class);
        
        if ($this->option('check')) {
            $this->checkMissingTransactions($autoTransactionService);
            return;
        }
        
        if ($this->option('pending')) {
            $this->processPendingReservations($autoTransactionService);
            return;
        }
        
        if ($reservationId = $this->option('reservation-id')) {
            $this->processSpecificReservation($autoTransactionService, $reservationId);
            return;
        }
        
        $this->info('Utilisation:');
        $this->line('  php artisan transactions:process-hierarchical --pending     # Traiter toutes les réservations en attente');
        $this->line('  php artisan transactions:process-hierarchical --check        # Vérifier les transactions manquantes');
        $this->line('  php artisan transactions:process-hierarchical --reservation-id=123  # Traiter une réservation spécifique');
    }
    
    /**
     * Vérifier les transactions manquantes
     */
    private function checkMissingTransactions(AutoTransactionService $service)
    {
        $this->info('Vérification des transactions manquantes...');
        
        $result = $service->checkAndFixMissingTransactions();
        
        if ($result['issues_found'] === 0) {
            $this->info('✅ Aucun problème détecté. Toutes les transactions sont correctement configurées.');
            return;
        }
        
        $this->warn("⚠️  {$result['issues_found']} problème(s) détecté(s):");
        
        foreach ($result['issues'] as $issue) {
            $this->line("  - {$issue['description']}: {$issue['count']} occurrence(s)");
        }
        
        if ($result['reservations_without_transactions'] > 0) {
            if ($this->confirm('Voulez-vous traiter les réservations sans transactions?')) {
                $this->processPendingReservations($service);
            }
        }
    }
    
    /**
     * Traiter toutes les réservations en attente
     */
    private function processPendingReservations(AutoTransactionService $service)
    {
        $this->info('Traitement des réservations en attente...');
        
        $result = $service->processPendingReservations();
        
        $this->info("📊 Résultats du traitement:");
        $this->line("  - Total traité: {$result['total_processed']}");
        $this->line("  - Succès: {$result['success_count']}");
        $this->line("  - Erreurs: {$result['error_count']}");
        
        if ($result['error_count'] > 0) {
            $this->warn('⚠️  Certaines transactions ont échoué. Vérifiez les logs pour plus de détails.');
        } else {
            $this->info('✅ Toutes les transactions ont été traitées avec succès.');
        }
    }
    
    /**
     * Traiter une réservation spécifique
     */
    private function processSpecificReservation(AutoTransactionService $service, $reservationId)
    {
        $this->info("Traitement de la réservation #{$reservationId}...");
        
        $reservation = \App\Models\Reservation::find($reservationId);
        
        if (!$reservation) {
            $this->error("❌ Réservation #{$reservationId} non trouvée.");
            return;
        }
        
        if ($reservation->status !== 'completed') {
            $this->error("❌ La réservation #{$reservationId} n'est pas terminée (statut: {$reservation->status}).");
            return;
        }
        
        if ($reservation->transactions()->exists()) {
            $this->warn("⚠️  La réservation #{$reservationId} a déjà des transactions associées.");
            if (!$this->confirm('Voulez-vous continuer?')) {
                return;
            }
        }
        
        $result = $service->processReservationTransaction($reservation);
        
        if ($result['success']) {
            $this->info("✅ Transaction hiérarchique créée avec succès pour la réservation #{$reservationId}");
            $this->line("  - Transaction principale: #{$result['main_transaction']->id}");
            
            if (isset($result['hierarchical_result']['main_transactions'])) {
                $mainTransactions = $result['hierarchical_result']['main_transactions'];
                if (isset($mainTransactions['admin_to_integrator'])) {
                    $this->line("  - Transaction Admin ↔ Intégrateur: #{$mainTransactions['admin_to_integrator']->id}");
                }
                if (isset($mainTransactions['integrator_to_operator'])) {
                    $this->line("  - Transaction Intégrateur ↔ Opérateur: #{$mainTransactions['integrator_to_operator']->id}");
                }
            }
        } else {
            $this->error("❌ Erreur lors du traitement: {$result['error']}");
        }
    }
}

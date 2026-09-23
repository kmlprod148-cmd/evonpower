<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReservationTotalCostService;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class FixReservationTotalCost extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reservations:fix-total-cost 
                            {--dry-run : Afficher ce qui serait corrigé sans faire les modifications}
                            {--force : Forcer la correction même si des erreurs sont détectées}';

    /**
     * The console command description.
     */
    protected $description = 'Corrige le total_cost de toutes les réservations qui ont des problèmes';

    protected $totalCostService;

    public function __construct(ReservationTotalCostService $totalCostService)
    {
        parent::__construct();
        $this->totalCostService = $totalCostService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Correction du total_cost des réservations...');
        $this->newLine();

        // Vérifier les réservations avec des problèmes
        $problematicReservations = Reservation::where(function($query) {
            $query->whereNull('total_cost')
                  ->orWhere('total_cost', 0)
                  ->orWhere('total_cost', '!=', DB::raw('estimated_cost'));
        })->count();

        if ($problematicReservations === 0) {
            $this->info('✅ Aucune réservation avec des problèmes de total_cost trouvée.');
            return 0;
        }

        $this->warn("⚠️  {$problematicReservations} réservations avec des problèmes détectées.");

        if ($this->option('dry-run')) {
            $this->info('🔍 Mode dry-run activé - aucune modification ne sera effectuée.');
            $this->showProblematicReservations();
            return 0;
        }

        if (!$this->option('force') && !$this->confirm('Voulez-vous continuer avec la correction ?')) {
            $this->info('❌ Opération annulée.');
            return 0;
        }

        $this->info('🚀 Début de la correction...');
        $this->newLine();

        // Exécuter la correction
        $results = $this->totalCostService->fixAllReservationsWithInvalidTotalCost();

        // Afficher les résultats
        $this->displayResults($results);

        return 0;
    }

    /**
     * Affiche les réservations problématiques
     */
    private function showProblematicReservations()
    {
        $reservations = Reservation::where(function($query) {
            $query->whereNull('total_cost')
                  ->orWhere('total_cost', 0)
                  ->orWhere('total_cost', '!=', DB::raw('estimated_cost'));
        })->with(['pricingPlan', 'chargingPoint'])->limit(10)->get();

        $this->table(
            ['ID', 'Type', 'Valeur', 'Coût Estimé', 'Total Cost', 'Plan Tarifaire'],
            $reservations->map(function($reservation) {
                return [
                    $reservation->id,
                    $reservation->reservation_type,
                    $reservation->reservation_value,
                    $reservation->estimated_cost ?? 'N/A',
                    $reservation->total_cost ?? 'N/A',
                    $reservation->pricingPlan->name ?? 'N/A'
                ];
            })
        );

        if ($reservations->count() > 10) {
            $this->info("... et " . ($reservations->count() - 10) . " autres réservations.");
        }
    }

    /**
     * Affiche les résultats de la correction
     */
    private function displayResults(array $results)
    {
        $this->newLine();
        $this->info('📊 Résultats de la correction :');
        $this->line("   • Réservations vérifiées : {$results['total_checked']}");
        $this->line("   • Réservations corrigées : {$results['fixed']}");
        $this->line("   • Erreurs : {$results['errors']}");

        if ($results['errors'] > 0 && !empty($results['error_details'])) {
            $this->newLine();
            $this->warn('⚠️  Détails des erreurs :');
            foreach ($results['error_details'] as $error) {
                $this->line("   • {$error}");
            }
        }

        if ($results['fixed'] > 0) {
            $this->newLine();
            $this->info('✅ Correction terminée avec succès !');
        }

        // Vérification finale
        $remainingIssues = Reservation::where(function($query) {
            $query->whereNull('total_cost')
                  ->orWhere('total_cost', 0)
                  ->orWhere('total_cost', '!=', DB::raw('estimated_cost'));
        })->count();

        if ($remainingIssues === 0) {
            $this->info('🎉 Toutes les réservations ont été corrigées !');
        } else {
            $this->warn("⚠️  Il reste {$remainingIssues} réservations avec des problèmes.");
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Services\UnifiedHierarchicalTransactionService;
use Illuminate\Support\Facades\Log;

class TestRevenueDistributionCalculation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'revenue:test-calculation 
                            {--charging-point-id= : ID de la borne à tester}
                            {--amount=120 : Montant de transaction à tester}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tester le calcul de répartition des revenus pour une borne spécifique';

    protected $unifiedService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->unifiedService = app(UnifiedHierarchicalTransactionService::class);

        $chargingPointId = $this->option('charging-point-id');
        $testAmount = (float) $this->option('amount');

        if (!$chargingPointId) {
            $this->error('❌ Veuillez spécifier --charging-point-id');
            return 1;
        }

        $this->info("🧪 Test du calcul de répartition pour la borne #{$chargingPointId}");
        $this->info("💰 Montant de test : {$testAmount} EUR");
        $this->newLine();

        try {
            $chargingPoint = ChargingPoint::with([
                'integrator',
                'partner',
                'user',
                'group.partner.integrator'
            ])->find($chargingPointId);

            if (!$chargingPoint) {
                $this->error("❌ Borne #{$chargingPointId} non trouvée");
                return 1;
            }

            $this->info("📍 Borne : {$chargingPoint->name}");
            $this->info("🔢 Serial : {$chargingPoint->serial_number}");
            $this->newLine();

            // Récupérer la hiérarchie
            $this->info("🔍 Récupération de la hiérarchie...");
            $hierarchy = $this->unifiedService->getCompleteHierarchy($chargingPointId);

            if (!$hierarchy) {
                $this->error('❌ Impossible de récupérer la hiérarchie complète');
                return 1;
            }

            $this->displayHierarchy($hierarchy);
            $this->newLine();

            // Récupérer les Business Profiles
            $this->info("📋 Récupération des Business Profiles...");
            $businessProfiles = $this->unifiedService->getBusinessProfilesForHierarchy($hierarchy);

            if (!$businessProfiles['admin_integrator'] || !$businessProfiles['integrator_operator']) {
                $this->error('❌ Business Profiles manquants');
                return 1;
            }

            $this->displayBusinessProfiles($businessProfiles);
            $this->newLine();

            // Calculer les parts
            $this->info("🧮 Calcul des parts...");
            $calculation = $this->unifiedService->calculateSharesWithBusinessProfiles(
                $testAmount,
                $businessProfiles,
                $hierarchy
            );

            $this->displayCalculation($calculation, $testAmount);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erreur : ' . $e->getMessage());
            Log::error('Erreur TestRevenueDistributionCalculation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Afficher la hiérarchie
     */
    protected function displayHierarchy(array $hierarchy)
    {
        $this->table(
            ['Niveau', 'Type', 'ID', 'Nom'],
            [
                ['Admin', 'User', $hierarchy['admin']->id ?? 'N/A', $hierarchy['admin']->name ?? 'N/A'],
                ['Intégrateur', 'Integrator', $hierarchy['integrator']->id ?? 'N/A', $hierarchy['integrator']->name ?? 'N/A'],
                ['Partenaire', 'Partner', $hierarchy['partner']->id ?? 'N/A', $hierarchy['partner']->name ?? 'N/A'],
                ['Opérateur', 'User', $hierarchy['operator']->id ?? 'N/A', $hierarchy['operator']->name ?? 'N/A'],
            ]
        );
    }

    /**
     * Afficher les Business Profiles
     */
    protected function displayBusinessProfiles(array $businessProfiles)
    {
        $adminBP = $businessProfiles['admin_integrator'];
        $integratorBP = $businessProfiles['integrator_operator'];

        $this->table(
            ['Business Profile', 'ID', 'Nom', 'Type'],
            [
                ['Admin → Intégrateur', $adminBP->id, $adminBP->name, 'Admin → Intégrateur'],
                ['Intégrateur → Opérateur', $integratorBP->id, $integratorBP->name, 'Intégrateur → Opérateur'],
            ]
        );

        $this->info('📊 Configuration Admin → Intégrateur :');
        $this->line('   Transaction: ' . ($adminBP->transaction_fee_config ?? 'N/A'));
        $this->line('   Charge: ' . ($adminBP->charge_fee_config ?? 'N/A'));
        $this->newLine();

        $this->info('📊 Configuration Intégrateur → Opérateur :');
        $this->line('   Transaction: ' . ($integratorBP->transaction_fee_config ?? 'N/A'));
        $this->line('   Charge: ' . ($integratorBP->charge_fee_config ?? 'N/A'));
    }

    /**
     * Afficher le calcul
     */
    protected function displayCalculation(array $calculation, float $totalAmount)
    {
        $this->info('💵 Résultat du calcul :');
        $this->newLine();

        $this->table(
            ['Part', 'Montant (EUR)', 'Pourcentage', 'Détails'],
            [
                [
                    'Admin',
                    number_format($calculation['admin_share'], 2),
                    number_format($calculation['admin_percentage'], 4) . '%',
                    'Config Transaction + Config Charge (sans admin_fee_*)'
                ],
                [
                    'Intégrateur',
                    number_format($calculation['integrator_share'], 2),
                    number_format($calculation['integrator_percentage'], 4) . '%',
                    'Frais Intégrateur - Frais Admin'
                ],
                [
                    'Opérateur',
                    number_format($calculation['operator_share'], 2),
                    number_format($calculation['operator_percentage'], 4) . '%',
                    'Total - Frais Intégrateur'
                ],
                [
                    'TOTAL',
                    number_format($totalAmount, 2),
                    '100.00%',
                    'Somme des parts'
                ],
            ]
        );

        // Vérification de cohérence
        $sum = $calculation['admin_share'] + $calculation['integrator_share'] + $calculation['operator_share'];
        $diff = abs($sum - $totalAmount);

        if ($diff < 0.01) {
            $this->info("✅ Vérification : Total cohérent (différence: {$diff} EUR)");
        } else {
            $this->warn("⚠️  Vérification : Différence détectée ({$diff} EUR)");
        }

        $this->newLine();
        $this->info('📋 Détails des frais :');
        $this->line('   Frais Admin (brut): ' . number_format($calculation['admin_fees_amount'], 2) . ' EUR');
        $this->line('   Frais Intégrateur (brut): ' . number_format($calculation['integrator_fees_amount'], 2) . ' EUR');
    }
}


<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ChargingPointFeeCalculationService;
use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\BusinessProfile;

class TestChargingPointFeeCalculation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:charging-point-fees {--charging-point-id=} {--base-amount=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the charging point fee calculation system';

    /**
     * Execute the console command.
     */
    public function handle(ChargingPointFeeCalculationService $feeService)
    {
        $this->info('Testing Charging Point Fee Calculation System...');

        try {
            $chargingPointId = $this->option('charging-point-id');
            $baseAmount = (float) $this->option('base-amount');

            if ($chargingPointId) {
                // Test avec une borne spécifique
                $chargingPoint = ChargingPoint::find($chargingPointId);
                if (!$chargingPoint) {
                    $this->error("Borne de recharge #{$chargingPointId} non trouvée");
                    return 1;
                }

                $this->testSpecificChargingPoint($feeService, $chargingPoint, $baseAmount);
            } else {
                // Test avec toutes les bornes disponibles
                $this->testAllChargingPoints($feeService, $baseAmount);
            }

            $this->info('✅ Tests terminés avec succès!');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Test avec une borne spécifique
     */
    private function testSpecificChargingPoint($feeService, ChargingPoint $chargingPoint, float $baseAmount)
    {
        $this->info("Testing charging point: {$chargingPoint->name} (ID: {$chargingPoint->id})");
        $this->info("Base amount: {$baseAmount} EUR");

        $feeBreakdown = $feeService->calculateChargingPointCreatorFees($chargingPoint, $baseAmount);

        $this->displayFeeBreakdown($feeBreakdown, $baseAmount);
    }

    /**
     * Test avec toutes les bornes
     */
    private function testAllChargingPoints($feeService, float $baseAmount)
    {
        $chargingPoints = ChargingPoint::with(['integrator', 'partner'])->take(5)->get();

        if ($chargingPoints->isEmpty()) {
            $this->warn('Aucune borne de recharge trouvée dans la base de données');
            return;
        }

        $this->info("Testing {$chargingPoints->count()} charging points with base amount: {$baseAmount} EUR");
        $this->newLine();

        foreach ($chargingPoints as $chargingPoint) {
            $this->info("--- Testing: {$chargingPoint->name} ---");
            
            $feeBreakdown = $feeService->calculateChargingPointCreatorFees($chargingPoint, $baseAmount);
            
            $this->displayFeeBreakdown($feeBreakdown, $baseAmount);
            $this->newLine();
        }
    }

    /**
     * Affiche le breakdown des frais
     */
    private function displayFeeBreakdown(array $feeBreakdown, float $baseAmount)
    {
        // Informations du créateur
        if (isset($feeBreakdown['creator_info'])) {
            $creator = $feeBreakdown['creator_info'];
            $this->info("Créateur: {$creator['type']} - {$creator['name']}");
        }

        // Informations du business profile
        if (isset($feeBreakdown['business_profile_info'])) {
            $bp = $feeBreakdown['business_profile_info'];
            $this->info("Business Profile: {$bp['name']} (ID: {$bp['id']})");
        }

        // Détail des frais
        $this->info('Détail des frais:');

        if (isset($feeBreakdown['charging_fees'])) {
            $charging = $feeBreakdown['charging_fees'];
            $this->line("  Frais de recharge: {$charging['total']} EUR");
            $this->line("    - Fixes: {$charging['fixed_fee']} EUR");
            $this->line("    - Pourcentage: {$charging['percentage_fee']} EUR");
            $this->line("    - Par kWh: {$charging['per_kwh_fee']} EUR");
            $this->line("    - Par minute: {$charging['per_minute_fee']} EUR");
        }

        if (isset($feeBreakdown['transaction_fees'])) {
            $transaction = $feeBreakdown['transaction_fees'];
            $this->line("  Frais de transaction: {$transaction['total']} EUR");
            $this->line("    - Fixes: {$transaction['fixed_fee']} EUR");
            $this->line("    - Pourcentage: {$transaction['percentage_fee']} EUR");
            $this->line("    - Minimum: {$transaction['minimum_fee']} EUR");
            $this->line("    - Maximum: {$transaction['maximum_fee']} EUR");
        }

        if (isset($feeBreakdown['activation_fees'])) {
            $activation = $feeBreakdown['activation_fees'];
            $this->line("  Frais d'activation: {$activation['total']} EUR");
            $this->line("    - Base: {$activation['base_fee']} EUR");
            $this->line("    - Uniques: {$activation['one_time_fee']} EUR");
            $this->line("    - Installation: {$activation['setup_fee']} EUR");
        }

        if (isset($feeBreakdown['admin_fees'])) {
            $admin = $feeBreakdown['admin_fees'];
            $this->line("  Frais administratifs: {$admin['total']} EUR");
            $this->line("    - Fixes: {$admin['fixed_fee']} EUR");
            $this->line("    - Pourcentage: {$admin['percentage_fee']} EUR");
        }

        // Résumé
        if (isset($feeBreakdown['summary'])) {
            $summary = $feeBreakdown['summary'];
            $totalFees = $summary['total_fees'];
            $totalAmount = $baseAmount + $totalFees;

            $this->newLine();
            $this->info("=== RÉSUMÉ ===");
            $this->line("Montant de base: {$baseAmount} EUR");
            $this->line("Total des frais: {$totalFees} EUR");
            $this->line("Montant total: {$totalAmount} EUR");
            
            if ($summary['has_fees']) {
                $this->info("✅ Des frais sont appliqués");
            } else {
                $this->warn("ℹ️ Aucun frais appliqué");
            }
        }
    }
}

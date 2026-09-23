<?php

namespace App\Console\Commands;

use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Models\Integrator;
use App\Models\Partner;
use App\Services\AdvancedFeeCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestAdvancedFeeCalculation extends Command
{
    protected $signature = 'fees:test-advanced-calculation 
                            {--charging-point-id= : Specific charging point ID to test}
                            {--base-amount=100 : Base amount for calculation}
                            {--apply-to-transactions : Apply fees to existing transactions}
                            {--scenarios : Test different scenarios}';

    protected $description = 'Test the advanced fee calculation system with comprehensive breakdowns';

    protected $advancedFeeCalculationService;

    public function __construct(AdvancedFeeCalculationService $advancedFeeCalculationService)
    {
        parent::__construct();
        $this->advancedFeeCalculationService = $advancedFeeCalculationService;
    }

    public function handle()
    {
        $this->info('🚀 Testing Advanced Fee Calculation System');
        $this->newLine();

        $chargingPointId = $this->option('charging-point-id');
        $baseAmount = (float) $this->option('base-amount');
        $applyToTransactions = $this->option('apply-to-transactions');
        $testScenarios = $this->option('scenarios');

        if ($chargingPointId) {
            $this->testSpecificChargingPoint($chargingPointId, $baseAmount);
        } else {
            $this->testMultipleChargingPoints($baseAmount);
        }

        if ($testScenarios) {
            $this->testDifferentScenarios($baseAmount);
        }

        if ($applyToTransactions) {
            $this->applyFeesToTransactions();
        }

        $this->newLine();
        $this->info('✅ Advanced fee calculation testing completed!');
    }

    private function testSpecificChargingPoint($chargingPointId, $baseAmount)
    {
        $this->info("🔍 Testing specific charging point: {$chargingPointId}");
        
        try {
            $chargingPoint = ChargingPoint::with(['integrator', 'partner', 'group'])->findOrFail($chargingPointId);
            
            $comprehensiveBreakdown = $this->advancedFeeCalculationService->calculateComprehensiveFees(
                $chargingPoint, 
                $baseAmount
            );

            $this->displayComprehensiveBreakdown($comprehensiveBreakdown);

        } catch (\Exception $e) {
            $this->error("❌ Error testing charging point {$chargingPointId}: " . $e->getMessage());
        }
    }

    private function testMultipleChargingPoints($baseAmount)
    {
        $this->info("🔍 Testing multiple charging points with base amount: {$baseAmount} EUR");
        $this->newLine();

        // Get charging points created by integrators
        $integratorChargingPoints = ChargingPoint::whereNotNull('integrator_id')
            ->with(['integrator', 'partner', 'group'])
            ->limit(5)
            ->get();

        $this->info("📊 Charging Points Created by Integrators:");
        $this->displayChargingPointsTable($integratorChargingPoints, $baseAmount);

        // Get charging points created by partners
        $partnerChargingPoints = ChargingPoint::whereNotNull('partner_id')
            ->with(['integrator', 'partner', 'group'])
            ->limit(5)
            ->get();

        $this->newLine();
        $this->info("📊 Charging Points Created by Partners:");
        $this->displayChargingPointsTable($partnerChargingPoints, $baseAmount);
    }

    private function displayChargingPointsTable($chargingPoints, $baseAmount)
    {
        if ($chargingPoints->isEmpty()) {
            $this->warn("No charging points found for this category.");
            return;
        }

        $headers = ['ID', 'Name', 'Creator', 'Admin Fees', 'Integrator Fees', 'Partner Fees', 'Operator Revenue', 'Total Fees'];
        $rows = [];

        foreach ($chargingPoints as $chargingPoint) {
            $comprehensiveBreakdown = $this->advancedFeeCalculationService->calculateComprehensiveFees(
                $chargingPoint, 
                $baseAmount
            );

            $rows[] = [
                $chargingPoint->id,
                $chargingPoint->name,
                $comprehensiveBreakdown['creator_info']['name'] . ' (' . $comprehensiveBreakdown['creator_type'] . ')',
                number_format($comprehensiveBreakdown['admin_part']['fees']['total'], 2) . ' EUR',
                number_format($comprehensiveBreakdown['integrator_part']['fees']['total'], 2) . ' EUR',
                number_format($comprehensiveBreakdown['partner_part']['fees']['total'], 2) . ' EUR',
                number_format($comprehensiveBreakdown['operator_part']['revenue']['net_revenue'], 2) . ' EUR',
                number_format($comprehensiveBreakdown['total_breakdown']['total_fees'], 2) . ' EUR'
            ];
        }

        $this->table($headers, $rows);
    }

    private function displayComprehensiveBreakdown($comprehensiveBreakdown)
    {
        $this->newLine();
        $this->info("📋 Comprehensive Fee Breakdown:");
        $this->newLine();

        // Basic info
        $this->line("🏢 Charging Point: {$comprehensiveBreakdown['charging_point_info']['name']} (ID: {$comprehensiveBreakdown['charging_point_info']['id']})");
        $this->line("👤 Creator: {$comprehensiveBreakdown['creator_info']['name']} ({$comprehensiveBreakdown['creator_type']})");
        $this->line("💰 Base Amount: " . number_format($comprehensiveBreakdown['base_amount'], 2) . " EUR");
        $this->newLine();

        // Admin Part
        $adminPart = $comprehensiveBreakdown['admin_part'];
        $this->info("🔴 Admin Part:");
        $this->line("   Applies: " . ($adminPart['applies'] ? '✅ Yes' : '❌ No'));
        $this->line("   Reason: {$adminPart['reason']}");
        if ($adminPart['applies']) {
            $this->line("   Fixed Fee: " . number_format($adminPart['fees']['fixed_fee'], 2) . " EUR");
            $this->line("   Percentage Fee: " . number_format($adminPart['fees']['percentage_fee'], 2) . " EUR");
            $this->line("   Activation Fee: " . number_format($adminPart['fees']['activation_fee'], 2) . " EUR");
            $this->line("   Transaction Fee: " . number_format($adminPart['fees']['transaction_fee'], 2) . " EUR");
            $this->line("   Total Admin Fees: " . number_format($adminPart['fees']['total'], 2) . " EUR");
        }
        $this->newLine();

        // Integrator Part
        $integratorPart = $comprehensiveBreakdown['integrator_part'];
        $this->info("🔵 Integrator Part:");
        $this->line("   Applies: " . ($integratorPart['applies'] ? '✅ Yes' : '❌ No'));
        $this->line("   Reason: {$integratorPart['reason']}");
        if ($integratorPart['applies']) {
            $this->line("   Fixed Fee: " . number_format($integratorPart['fees']['fixed_fee'], 2) . " EUR");
            $this->line("   Percentage Fee: " . number_format($integratorPart['fees']['percentage_fee'], 2) . " EUR");
            $this->line("   Commission Fee: " . number_format($integratorPart['fees']['commission_fee'], 2) . " EUR");
            $this->line("   Total Integrator Fees: " . number_format($integratorPart['fees']['total'], 2) . " EUR");
        }
        $this->newLine();

        // Partner Part
        $partnerPart = $comprehensiveBreakdown['partner_part'];
        $this->info("🟢 Partner Part:");
        $this->line("   Applies: " . ($partnerPart['applies'] ? '✅ Yes' : '❌ No'));
        $this->line("   Reason: {$partnerPart['reason']}");
        if ($partnerPart['applies']) {
            $this->line("   Fixed Fee: " . number_format($partnerPart['fees']['fixed_fee'], 2) . " EUR");
            $this->line("   Percentage Fee: " . number_format($partnerPart['fees']['percentage_fee'], 2) . " EUR");
            $this->line("   Commission Fee: " . number_format($partnerPart['fees']['commission_fee'], 2) . " EUR");
            $this->line("   Total Partner Fees: " . number_format($partnerPart['fees']['total'], 2) . " EUR");
        }
        $this->newLine();

        // Operator Part
        $operatorPart = $comprehensiveBreakdown['operator_part'];
        $this->info("🟣 Operator Part:");
        $this->line("   Base Revenue: " . number_format($operatorPart['revenue']['base_revenue'], 2) . " EUR");
        $this->line("   Deducted Fees: " . number_format($operatorPart['revenue']['deducted_fees'], 2) . " EUR");
        $this->line("   Net Revenue: " . number_format($operatorPart['revenue']['net_revenue'], 2) . " EUR");
        $this->newLine();

        // Summary
        $summary = $comprehensiveBreakdown['summary'];
        $this->info("📊 Summary:");
        $this->line("   Total Fees: " . number_format($summary['total_fees'], 2) . " EUR");
        $this->line("   Net Revenue: " . number_format($summary['net_revenue'], 2) . " EUR");
        $this->line("   Fee Distribution:");
        foreach ($summary['fee_distribution'] as $type => $amount) {
            $this->line("     {$type}: " . number_format($amount, 2) . " EUR");
        }
    }

    private function testDifferentScenarios($baseAmount)
    {
        $this->newLine();
        $this->info("🎭 Testing Different Scenarios:");
        $this->newLine();

        // Scenario 1: Integrator-created charging point
        $integratorChargingPoint = ChargingPoint::whereNotNull('integrator_id')
            ->with(['integrator', 'partner', 'group'])
            ->first();

        if ($integratorChargingPoint) {
            $this->info("📋 Scenario 1: Station créée par intégrateur");
            $comprehensiveBreakdown = $this->advancedFeeCalculationService->calculateComprehensiveFees(
                $integratorChargingPoint, 
                $baseAmount
            );
            
            $this->line("   Creator: {$comprehensiveBreakdown['creator_info']['name']} ({$comprehensiveBreakdown['creator_type']})");
            $this->line("   Admin Fees Apply: " . ($comprehensiveBreakdown['admin_part']['applies'] ? '✅ Yes' : '❌ No'));
            $this->line("   Admin Reason: {$comprehensiveBreakdown['admin_part']['reason']}");
            $this->line("   Integrator Fees Apply: " . ($comprehensiveBreakdown['integrator_part']['applies'] ? '✅ Yes' : '❌ No'));
            $this->line("   Integrator Reason: {$comprehensiveBreakdown['integrator_part']['reason']}");
            $this->line("   Total Fees: " . number_format($comprehensiveBreakdown['total_breakdown']['total_fees'], 2) . " EUR");
            $this->newLine();
        }

        // Scenario 2: Partner-created charging point
        $partnerChargingPoint = ChargingPoint::whereNotNull('partner_id')
            ->with(['integrator', 'partner', 'group'])
            ->first();

        if ($partnerChargingPoint) {
            $this->info("📋 Scenario 2: Station créée par partenaire");
            $comprehensiveBreakdown = $this->advancedFeeCalculationService->calculateComprehensiveFees(
                $partnerChargingPoint, 
                $baseAmount
            );
            
            $this->line("   Creator: {$comprehensiveBreakdown['creator_info']['name']} ({$comprehensiveBreakdown['creator_type']})");
            $this->line("   Admin Fees Apply: " . ($comprehensiveBreakdown['admin_part']['applies'] ? '✅ Yes' : '❌ No'));
            $this->line("   Admin Reason: {$comprehensiveBreakdown['admin_part']['reason']}");
            $this->line("   Integrator Fees Apply: " . ($comprehensiveBreakdown['integrator_part']['applies'] ? '✅ Yes' : '❌ No'));
            $this->line("   Integrator Reason: {$comprehensiveBreakdown['integrator_part']['reason']}");
            $this->line("   Partner Fees Apply: " . ($comprehensiveBreakdown['partner_part']['applies'] ? '✅ Yes' : '❌ No'));
            $this->line("   Partner Reason: {$comprehensiveBreakdown['partner_part']['reason']}");
            $this->line("   Total Fees: " . number_format($comprehensiveBreakdown['total_breakdown']['total_fees'], 2) . " EUR");
            $this->newLine();
        }
    }

    private function applyFeesToTransactions()
    {
        $this->newLine();
        $this->info("💳 Applying fees to existing transactions...");

        $transactions = Transaction::where('transaction_type', 'client')
            ->where('status', 'completed')
            ->whereNull('creator_fees_applied_at')
            ->with(['chargingPoint'])
            ->limit(10)
            ->get();

        if ($transactions->isEmpty()) {
            $this->warn("No transactions found to apply fees to.");
            return;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($transactions as $transaction) {
            try {
                $success = $this->advancedFeeCalculationService->applyComprehensiveFeesToTransaction($transaction);
                
                if ($success) {
                    $successCount++;
                    $this->line("✅ Applied fees to transaction {$transaction->id}");
                } else {
                    $errorCount++;
                    $this->line("❌ Failed to apply fees to transaction {$transaction->id}");
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->line("❌ Error applying fees to transaction {$transaction->id}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("📊 Fee Application Summary:");
        $this->line("   Successful: {$successCount}");
        $this->line("   Failed: {$errorCount}");
        $this->line("   Total: " . ($successCount + $errorCount));
    }
}

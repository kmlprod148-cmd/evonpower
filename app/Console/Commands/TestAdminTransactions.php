<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TransactionService;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use App\Models\Partner;

class TestAdminTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:admin-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the admin transactions system';

    /**
     * Execute the console command.
     */
    public function handle(TransactionService $transactionService)
    {
        $this->info('Testing Admin Transactions System...');

        try {
            // Test 1: Get comprehensive admin transactions
            $this->info('1. Testing getComprehensiveAdminTransactions...');
            $transactions = $transactionService->getComprehensiveAdminTransactions();
            $this->info("   Found {$transactions->total()} transactions");

            // Test 2: Get comprehensive statistics
            $this->info('2. Testing getComprehensiveAdminStatistics...');
            $statistics = $transactionService->getComprehensiveAdminStatistics();
            $this->info("   Total transactions: {$statistics['total_transactions']}");
            $this->info("   Total revenue: {$statistics['total_revenue']}");
            $this->info("   Client transactions: {$statistics['client_transactions']}");
            $this->info("   Admin transactions: {$statistics['admin_transactions']}");

            // Test 3: Get balance summary
            $this->info('3. Testing getBalanceSummary...');
            $balanceSummary = $transactionService->getBalanceSummary();
            $this->info("   Integrators: {$balanceSummary['integrators']->count()}");
            $this->info("   Partners: {$balanceSummary['partners']->count()}");
            $this->info("   Business Profiles: {$balanceSummary['business_profiles']->count()}");

            // Test 4: Test individual balance methods
            $this->info('4. Testing individual balance methods...');
            
            // Test integrator balance
            $integrators = Integrator::take(1)->get();
            if ($integrators->count() > 0) {
                $integratorBalance = $transactionService->getIntegratorBalance($integrators->first()->id);
                $this->info("   Integrator balance: {$integratorBalance}");
            }

            // Test partner balance
            $partners = Partner::take(1)->get();
            if ($partners->count() > 0) {
                $partnerBalance = $transactionService->getPartnerBalance($partners->first()->id);
                $this->info("   Partner balance: {$partnerBalance}");
            }

            // Test business profile balance
            $businessProfiles = BusinessProfile::take(1)->get();
            if ($businessProfiles->count() > 0) {
                $businessProfileBalance = $transactionService->getBusinessProfileBalance($businessProfiles->first()->id);
                $this->info("   Business Profile balance: {$businessProfileBalance}");
            }

            $this->info('✅ All tests passed successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}

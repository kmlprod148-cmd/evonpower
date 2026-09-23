<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CreatorFeesApplicationService;

class ApplyCreatorFeesToTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:apply-creator-fees 
                            {--recalculate : Recalculate creator fees for all transactions}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Apply creator fees to transactions based on business profiles';

    protected $creatorFeesService;

    public function __construct(CreatorFeesApplicationService $creatorFeesService)
    {
        parent::__construct();
        $this->creatorFeesService = $creatorFeesService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting creator fees application process...');

        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        try {
            if ($this->option('recalculate')) {
                $this->info('🔄 Recalculating creator fees for all transactions...');
                $stats = $this->creatorFeesService->recalculateCreatorFeesForAllTransactions();
            } else {
                $this->info('📝 Applying creator fees to transactions without them...');
                $stats = $this->creatorFeesService->applyCreatorFeesToExistingTransactions();
            }

            $this->displayResults($stats);

        } catch (\Exception $e) {
            $this->error('❌ Error during creator fees application: ' . $e->getMessage());
            return 1;
        }

        $this->info('✅ Creator fees application process completed!');
        return 0;
    }

    /**
     * Display the results of the creator fees application
     */
    protected function displayResults(array $stats): void
    {
        $this->newLine();
        $this->info('📊 Creator Fees Application Results:');
        $this->newLine();

        // Summary table
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Transactions', $stats['total_transactions']],
                ['Processed Transactions', $stats['processed_transactions']],
                ['Successful Applications', $stats['successful_applications'] ?? $stats['successful_recalculations'] ?? 0],
                ['Failed Applications', $stats['failed_applications'] ?? $stats['failed_recalculations'] ?? 0],
            ]
        );

        // Display errors if any
        if (!empty($stats['errors'])) {
            $this->newLine();
            $this->warn('⚠️  Errors encountered:');
            
            foreach ($stats['errors'] as $error) {
                $this->error("Transaction ID {$error['transaction_id']}: {$error['error']}");
            }
        }

        // Show statistics
        $this->newLine();
        $this->info('📈 Creator Fees Statistics:');
        
        $creatorStats = $this->creatorFeesService->getCreatorFeesStatistics();
        
        $this->table(
            ['Statistic', 'Value'],
            [
                ['Transactions with Creator Fees', $creatorStats['total_transactions_with_creator_fees']],
                ['Transactions without Creator Fees', $creatorStats['total_transactions_without_creator_fees']],
                ['Total Creator Fees Amount', number_format($creatorStats['total_creator_fees_amount'], 2) . ' EUR'],
                ['Average Creator Fees per Transaction', number_format($creatorStats['average_creator_fees_per_transaction'], 2) . ' EUR'],
            ]
        );

        // Show creator fees by source
        if (!empty($creatorStats['creator_fees_by_source'])) {
            $this->newLine();
            $this->info('🏢 Creator Fees by Source:');
            
            $sourceData = [];
            foreach ($creatorStats['creator_fees_by_source'] as $source => $data) {
                $sourceData[] = [
                    ucfirst($source),
                    $data['count'],
                    number_format($data['total_amount'], 2) . ' EUR',
                    number_format($data['average_amount'], 2) . ' EUR'
                ];
            }
            
            $this->table(
                ['Source', 'Count', 'Total Amount', 'Average Amount'],
                $sourceData
            );
        }

        // Show creator fees by month
        if (!empty($creatorStats['creator_fees_by_month'])) {
            $this->newLine();
            $this->info('📅 Creator Fees by Month (Last 12 months):');
            
            $monthData = [];
            foreach ($creatorStats['creator_fees_by_month'] as $month => $data) {
                $monthData[] = [
                    $month,
                    $data['count'],
                    number_format($data['total_amount'], 2) . ' EUR',
                    number_format($data['average_amount'], 2) . ' EUR'
                ];
            }
            
            $this->table(
                ['Month', 'Count', 'Total Amount', 'Average Amount'],
                $monthData
            );
        }
    }
}

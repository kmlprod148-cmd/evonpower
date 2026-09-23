<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\MoneyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class MoneyServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'integrator']);
        Role::create(['name' => 'partner']);
        Role::create(['name' => 'operator']);
    }

    /** @test */
    public function wallet_credit_stores_amount_in_eur()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        
        // Credit 100 USD
        $transaction = $wallet->credit(100.00, 'Test credit', [], 'USD');
        
        // Should store in EUR (100 * 0.93 = 93.00)
        $this->assertEquals(93.00, $transaction->amount);
        $this->assertEquals('EUR', $transaction->currency);
        $this->assertEquals(93.00, $wallet->fresh()->balance);
    }

    /** @test */
    public function wallet_debit_stores_amount_in_eur()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        
        // First credit some EUR
        $wallet->credit(100.00, 'Initial credit');
        
        // Debit 50 USD
        $transaction = $wallet->debit(50.00, 'Test debit', [], 'USD');
        
        // Should store in EUR (50 * 0.93 = 46.50)
        $this->assertEquals(46.50, $transaction->amount);
        $this->assertEquals('EUR', $transaction->currency);
        $this->assertEquals(53.50, $wallet->fresh()->balance); // 100 - 46.50
    }

    /** @test */
    public function wallet_formatted_balance_converts_to_display_currency()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        
        // Credit 100 EUR
        $wallet->credit(100.00, 'Test credit');
        
        // Format for USD display
        $formatted = $wallet->getFormattedBalance('USD');
        
        // Should convert EUR to USD (100 * 1.08 = 108.00)
        $this->assertStringContainsString('108.00', $formatted);
        $this->assertStringContainsString('$', $formatted);
    }

    /** @test */
    public function wallet_transaction_formatted_amount_converts_to_display_currency()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        
        // Credit 100 USD
        $transaction = $wallet->credit(100.00, 'Test credit', [], 'USD');
        
        // Format for USD display
        $formatted = $transaction->formatted_amount;
        
        // Should show original USD amount
        $this->assertStringContainsString('+100.00', $formatted);
        $this->assertStringContainsString('$', $formatted);
    }

    /** @test */
    public function wallet_transaction_metadata_stores_original_currency_info()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        
        // Credit 100 USD
        $transaction = $wallet->credit(100.00, 'Test credit', [], 'USD');
        
        // Check metadata
        $this->assertEquals(100.00, $transaction->metadata['original_amount']);
        $this->assertEquals('USD', $transaction->metadata['original_currency']);
        $this->assertArrayHasKey('exchange_rate', $transaction->metadata);
    }

    /** @test */
    public function wallet_transfer_handles_currency_conversion()
    {
        $user1 = User::factory()->create();
        $user1->assignRole('operator');
        
        $user2 = User::factory()->create();
        $user2->assignRole('operator');
        
        $wallet1 = $user1->getOrCreateWallet();
        $wallet2 = $user2->getOrCreateWallet();
        
        // Credit wallet1 with 100 EUR
        $wallet1->credit(100.00, 'Initial credit');
        
        // Transfer 50 USD from wallet1 to wallet2
        $result = $wallet1->transferTo($wallet2, 50.00, 'Transfer test', [], 'USD');
        
        // Check source transaction (debit in EUR)
        $sourceTransaction = $result['source_transaction'];
        $this->assertEquals(46.50, $sourceTransaction->amount); // 50 * 0.93
        $this->assertEquals('EUR', $sourceTransaction->currency);
        
        // Check destination transaction (credit in EUR)
        $destTransaction = $result['destination_transaction'];
        $this->assertEquals(46.50, $destTransaction->amount); // 50 * 0.93
        $this->assertEquals('EUR', $destTransaction->currency);
        
        // Check balances
        $this->assertEquals(53.50, $wallet1->fresh()->balance); // 100 - 46.50
        $this->assertEquals(46.50, $wallet2->fresh()->balance); // 0 + 46.50
    }

    /** @test */
    public function wallet_balance_history_shows_correct_amounts()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        
        // Credit 100 USD
        $wallet->credit(100.00, 'USD credit', [], 'USD');
        
        // Debit 50 USD
        $wallet->debit(50.00, 'USD debit', [], 'USD');
        
        // Get balance history
        $history = $wallet->getBalanceHistory();
        
        $this->assertCount(2, $history);
        
        // First transaction (credit)
        $creditTransaction = $history->where('type', 'credit')->first();
        $this->assertEquals(93.00, $creditTransaction->amount); // 100 * 0.93
        $this->assertEquals(93.00, $creditTransaction->current_balance);
        
        // Second transaction (debit)
        $debitTransaction = $history->where('type', 'debit')->first();
        $this->assertEquals(46.50, $debitTransaction->amount); // 50 * 0.93
        $this->assertEquals(46.50, $debitTransaction->current_balance); // 93.00 - 46.50
    }

    /** @test */
    public function wallet_statistics_calculate_correctly()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        
        // Credit 100 USD
        $wallet->credit(100.00, 'USD credit', [], 'USD');
        
        // Credit 50 EUR
        $wallet->credit(50.00, 'EUR credit');
        
        // Debit 25 USD
        $wallet->debit(25.00, 'USD debit', [], 'USD');
        
        // Get statistics
        $totalCredits = $wallet->getTotalCredits();
        $totalDebits = $wallet->getTotalDebits();
        
        // Total credits: 93.00 (USD) + 50.00 (EUR) = 143.00
        $this->assertEquals(143.00, $totalCredits);
        
        // Total debits: 23.25 (USD) = 23.25
        $this->assertEquals(23.25, $totalDebits);
        
        // Current balance: 143.00 - 23.25 = 119.75
        $this->assertEquals(119.75, $wallet->fresh()->balance);
    }

    /** @test */
    public function wallet_auto_recharge_works_with_currency_conversion()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        
        // Set up auto-recharge
        $wallet->update([
            'auto_recharge' => true,
            'auto_recharge_threshold' => 10.00, // EUR
            'auto_recharge_amount' => 50.00, // EUR
        ]);
        
        // Credit 100 USD (93.00 EUR)
        $wallet->credit(100.00, 'Initial credit', [], 'USD');
        
        // Debit 90 USD (83.70 EUR) - should trigger auto-recharge
        $wallet->debit(90.00, 'Large debit', [], 'USD');
        
        // Check if auto-recharge was triggered
        $this->assertTrue($wallet->needsAutoRecharge());
        
        // Perform auto-recharge
        $rechargeTransaction = $wallet->performAutoRecharge();
        
        $this->assertNotNull($rechargeTransaction);
        $this->assertEquals(50.00, $rechargeTransaction->amount);
        $this->assertEquals('EUR', $rechargeTransaction->currency);
    }

    /** @test */
    public function wallet_minimum_balance_constraint_works_with_currency_conversion()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        
        // Set minimum balance
        $wallet->update([
            'min_balance' => 20.00, // EUR
        ]);
        
        // Credit 100 USD (93.00 EUR)
        $wallet->credit(100.00, 'Initial credit', [], 'USD');
        
        // Try to debit 80 USD (74.40 EUR) - should fail due to min balance
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transaction would violate minimum balance constraint');
        
        $wallet->debit(80.00, 'Large debit', [], 'USD');
    }

    /** @test */
    public function wallet_insufficient_balance_check_works_with_currency_conversion()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        
        // Credit 50 USD (46.50 EUR)
        $wallet->credit(50.00, 'Initial credit', [], 'USD');
        
        // Try to debit 60 USD (55.80 EUR) - should fail due to insufficient balance
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');
        
        $wallet->debit(60.00, 'Large debit', [], 'USD');
    }

    /** @test */
    public function wallet_transaction_status_tracking_works()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        
        // Credit 100 USD
        $transaction = $wallet->credit(100.00, 'Test credit', [], 'USD');
        
        // Check transaction status
        $this->assertEquals('completed', $transaction->status);
        $this->assertTrue($transaction->isCompleted());
        $this->assertFalse($transaction->isPending());
        $this->assertFalse($transaction->isFailed());
        
        // Check transaction type
        $this->assertTrue($transaction->isCredit());
        $this->assertFalse($transaction->isDebit());
    }

    /** @test */
    public function wallet_transaction_badge_colors_work()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        
        // Credit transaction
        $creditTransaction = $wallet->credit(100.00, 'Test credit');
        $this->assertEquals('green', $creditTransaction->type_badge_color);
        
        // Debit transaction
        $debitTransaction = $wallet->debit(50.00, 'Test debit');
        $this->assertEquals('red', $debitTransaction->type_badge_color);
    }

    /** @test */
    public function wallet_transaction_summary_works()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        
        // Credit transaction
        $creditTransaction = $wallet->credit(100.00, 'Test credit');
        $this->assertStringContainsString('Credited', $creditTransaction->summary);
        $this->assertStringContainsString('+100.00', $creditTransaction->summary);
        $this->assertStringContainsString('Test credit', $creditTransaction->summary);
        
        // Debit transaction
        $debitTransaction = $wallet->debit(50.00, 'Test debit');
        $this->assertStringContainsString('Debited', $debitTransaction->summary);
        $this->assertStringContainsString('-50.00', $debitTransaction->summary);
        $this->assertStringContainsString('Test debit', $debitTransaction->summary);
    }
}

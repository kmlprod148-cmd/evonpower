<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class WalletSystemTest extends TestCase
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
    public function can_create_wallet_for_user()
    {
        $user = User::factory()->create();
        
        $wallet = WalletService::createWallet($user, [
            'name' => 'Test Wallet',
            'currency' => 'USD',
        ]);

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals($user->id, $wallet->owner_id);
        $this->assertEquals(User::class, $wallet->owner_type);
        $this->assertEquals('Test Wallet', $wallet->name);
        $this->assertEquals('USD', $wallet->currency);
        $this->assertEquals(0, $wallet->balance);
    }

    /** @test */
    public function can_get_or_create_wallet()
    {
        $user = User::factory()->create();
        
        // First call should create wallet
        $wallet1 = WalletService::getOrCreateWallet($user);
        $this->assertInstanceOf(Wallet::class, $wallet1);
        
        // Second call should return existing wallet
        $wallet2 = WalletService::getOrCreateWallet($user);
        $this->assertEquals($wallet1->id, $wallet2->id);
    }

    /** @test */
    public function can_credit_wallet()
    {
        $user = User::factory()->create();
        $wallet = WalletService::createWallet($user);
        
        $transaction = WalletService::credit($wallet, 100.50, 'Test credit');
        
        $this->assertInstanceOf(WalletTransaction::class, $transaction);
        $this->assertEquals('credit', $transaction->type);
        $this->assertEquals(100.50, $transaction->amount);
        $this->assertEquals(100.50, $wallet->fresh()->balance);
        $this->assertEquals('Test credit', $transaction->description);
    }

    /** @test */
    public function can_debit_wallet()
    {
        $user = User::factory()->create();
        $wallet = WalletService::createWallet($user, ['balance' => 100]);
        
        $transaction = WalletService::debit($wallet, 25.75, 'Test debit');
        
        $this->assertInstanceOf(WalletTransaction::class, $transaction);
        $this->assertEquals('debit', $transaction->type);
        $this->assertEquals(25.75, $transaction->amount);
        $this->assertEquals(74.25, $wallet->fresh()->balance);
        $this->assertEquals('Test debit', $transaction->description);
    }

    /** @test */
    public function debit_fails_with_insufficient_balance()
    {
        $user = User::factory()->create();
        $wallet = WalletService::createWallet($user, ['balance' => 50]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');
        
        WalletService::debit($wallet, 100, 'Test debit');
    }

    /** @test */
    public function can_transfer_between_wallets()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $wallet1 = WalletService::createWallet($user1, ['balance' => 100]);
        $wallet2 = WalletService::createWallet($user2, ['balance' => 50]);
        
        $result = WalletService::transfer($wallet1, $wallet2, 30, 'Test transfer');
        
        $this->assertArrayHasKey('source_transaction', $result);
        $this->assertArrayHasKey('destination_transaction', $result);
        
        $this->assertEquals(70, $wallet1->fresh()->balance);
        $this->assertEquals(80, $wallet2->fresh()->balance);
        
        $this->assertEquals('debit', $result['source_transaction']->type);
        $this->assertEquals('credit', $result['destination_transaction']->type);
    }

    /** @test */
    public function transfer_fails_with_insufficient_balance()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $wallet1 = WalletService::createWallet($user1, ['balance' => 50]);
        $wallet2 = WalletService::createWallet($user2);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance for transfer');
        
        WalletService::transfer($wallet1, $wallet2, 100, 'Test transfer');
    }

    /** @test */
    public function user_can_credit_their_wallet()
    {
        $user = User::factory()->create();
        
        $transaction = $user->creditWallet(50, 'User credit');
        
        $this->assertInstanceOf(WalletTransaction::class, $transaction);
        $this->assertEquals(50, $user->getWalletBalance());
        $this->assertTrue($user->hasSufficientWalletBalance(50));
    }

    /** @test */
    public function user_can_debit_their_wallet()
    {
        $user = User::factory()->create();
        $user->creditWallet(100, 'Initial credit');
        
        $transaction = $user->debitWallet(30, 'User debit');
        
        $this->assertInstanceOf(WalletTransaction::class, $transaction);
        $this->assertEquals(70, $user->getWalletBalance());
    }

    /** @test */
    public function can_process_charging_payment()
    {
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet(['balance' => 100]);
        
        $sessionData = [
            'session_id' => 'SESS123',
            'charging_point_id' => 1,
            'energy_delivered' => 15.5,
        ];
        
        $transaction = WalletService::processChargingPayment($wallet, 25.50, $sessionData);
        
        $this->assertEquals('debit', $transaction->type);
        $this->assertEquals(25.50, $transaction->amount);
        $this->assertEquals(74.50, $wallet->fresh()->balance);
        $this->assertStringContains('SESS123', $transaction->description);
    }

    /** @test */
    public function can_process_commission_payment()
    {
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        
        $commissionData = [
            'commission_type' => 'charging_session',
            'rate' => 0.05,
            'base_amount' => 100,
        ];
        
        $transaction = WalletService::processCommissionPayment(
            $wallet, 
            5.00, 
            'Commission from charging session',
            $commissionData
        );
        
        $this->assertEquals('credit', $transaction->type);
        $this->assertEquals(5.00, $transaction->amount);
        $this->assertEquals(5.00, $wallet->fresh()->balance);
    }

    /** @test */
    public function can_process_refund()
    {
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        
        $refundData = [
            'original_transaction_id' => 'TXN123',
            'refund_reason' => 'Session cancelled',
        ];
        
        $transaction = WalletService::processRefund(
            $wallet, 
            15.00, 
            'Session cancelled',
            $refundData
        );
        
        $this->assertEquals('credit', $transaction->type);
        $this->assertEquals(15.00, $transaction->amount);
        $this->assertEquals(15.00, $wallet->fresh()->balance);
    }

    /** @test */
    public function can_get_wallet_statistics()
    {
        $user = User::factory()->create();
        $wallet = WalletService::createWallet($user);
        
        // Add some transactions
        WalletService::credit($wallet, 100, 'Initial credit');
        WalletService::debit($wallet, 25, 'First debit');
        WalletService::credit($wallet, 50, 'Second credit');
        WalletService::debit($wallet, 10, 'Second debit');
        
        $stats = WalletService::getWalletStatistics($wallet);
        
        $this->assertEquals(115, $stats['current_balance']);
        $this->assertEquals(150, $stats['total_credits']);
        $this->assertEquals(35, $stats['total_debits']);
        $this->assertEquals(115, $stats['net_change']);
        $this->assertEquals(4, $stats['transaction_count']);
    }

    /** @test */
    public function can_get_transaction_history()
    {
        $user = User::factory()->create();
        $wallet = WalletService::createWallet($user);
        
        WalletService::credit($wallet, 100, 'Credit 1');
        WalletService::debit($wallet, 25, 'Debit 1');
        WalletService::credit($wallet, 50, 'Credit 2');
        
        $history = WalletService::getTransactionHistory($wallet, 10, 0);
        
        $this->assertCount(3, $history);
        $this->assertEquals('Credit 2', $history->first()->description);
        $this->assertEquals('Credit 1', $history->last()->description);
    }

    /** @test */
    public function wallet_respects_minimum_balance_constraint()
    {
        $user = User::factory()->create();
        $wallet = WalletService::createWallet($user, [
            'balance' => 100,
            'min_balance' => 20,
        ]);
        
        // Should succeed - balance would be 30
        $transaction = WalletService::debit($wallet, 70, 'Valid debit');
        $this->assertEquals(30, $wallet->fresh()->balance);
        
        // Should fail - balance would be 10 (below minimum)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('minimum balance constraint');
        
        WalletService::debit($wallet, 20, 'Invalid debit');
    }

    /** @test */
    public function auto_recharge_works_correctly()
    {
        $user = User::factory()->create();
        $wallet = WalletService::createWallet($user, [
            'balance' => 5,
            'auto_recharge' => true,
            'auto_recharge_threshold' => 10,
            'auto_recharge_amount' => 50,
        ]);
        
        $this->assertTrue($wallet->needsAutoRecharge());
        
        $transaction = $wallet->performAutoRecharge();
        
        $this->assertInstanceOf(WalletTransaction::class, $transaction);
        $this->assertEquals(55, $wallet->fresh()->balance);
        $this->assertEquals('Auto-recharge triggered', $transaction->description);
    }

    /** @test */
    public function can_get_wallets_needing_attention()
    {
        // Create wallets with different states
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        WalletService::createWallet($user1, ['balance' => 5]); // Low balance
        WalletService::createWallet($user2, [
            'balance' => 5,
            'auto_recharge' => true,
            'auto_recharge_threshold' => 10,
        ]); // Needing recharge
        WalletService::createWallet($user3, ['is_active' => false]); // Inactive
        
        $attention = WalletService::getWalletsNeedingAttention();
        
        $this->assertCount(1, $attention['low_balance']);
        $this->assertCount(1, $attention['needing_recharge']);
        $this->assertCount(1, $attention['inactive']);
    }

    /** @test */
    public function can_process_bulk_credits()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        $wallet1 = WalletService::createWallet($user1);
        $wallet2 = WalletService::createWallet($user2);
        $wallet3 = WalletService::createWallet($user3);
        
        $walletCredits = [
            $wallet1->id => 100,
            $wallet2->id => 200,
            $wallet3->id => 300,
        ];
        
        $results = WalletService::bulkCredit($walletCredits, 'Bulk credit');
        
        $this->assertCount(3, $results);
        $this->assertTrue($results[$wallet1->id]['success']);
        $this->assertTrue($results[$wallet2->id]['success']);
        $this->assertTrue($results[$wallet3->id]['success']);
        
        $this->assertEquals(100, $wallet1->fresh()->balance);
        $this->assertEquals(200, $wallet2->fresh()->balance);
        $this->assertEquals(300, $wallet3->fresh()->balance);
    }

    /** @test */
    public function can_get_balance_summary()
    {
        $user1 = User::factory()->create(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);
        
        $wallet1 = WalletService::createWallet($user1, ['balance' => 100, 'currency' => 'EUR']);
        $wallet2 = WalletService::createWallet($user2, ['balance' => 200, 'currency' => 'USD']);
        
        $summary = WalletService::getBalanceSummary([$wallet1->id, $wallet2->id]);
        
        $this->assertCount(2, $summary);
        
        $wallet1Summary = collect($summary)->firstWhere('wallet_id', $wallet1->id);
        $this->assertEquals('User 1', $wallet1Summary['owner']);
        $this->assertEquals(100, $wallet1Summary['balance']);
        $this->assertEquals('EUR', $wallet1Summary['currency']);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalRequestService;
use App\Services\MoneyService;
use App\Enums\WithdrawalStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use Exception;

class WithdrawalRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WithdrawalRequestService $service;
    protected MoneyService $moneyService;
    protected User $user;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->moneyService = Mockery::mock(MoneyService::class);
        $this->service = new WithdrawalRequestService($this->moneyService);

        // Créer un utilisateur de test
        $this->user = User::factory()->create();

        // Créer un wallet avec solde
        $this->wallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $this->user->id,
            'balance' => 1000.00,
            'currency' => 'EUR',
            'status' => 'active',
        ]);

        $this->user->wallets()->save($this->wallet);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_a_withdrawal_request_successfully()
    {
        $bankDetails = [
            'method' => 'bank_transfer',
            'bank_name' => 'Crédit Agricole',
            'bank_account' => 'FR7612345678901234567890123',
        ];

        $withdrawal = $this->service->createWithdrawalRequest(
            $this->user,
            100.00,
            $bankDetails,
            'Test withdrawal'
        );

        $this->assertInstanceOf(WithdrawalRequest::class, $withdrawal);
        $this->assertEquals(100.00, $withdrawal->amount);
        $this->assertEquals(1.00, $withdrawal->fee); // 1% de 100
        $this->assertEquals(99.00, $withdrawal->net_amount);
        $this->assertEquals(WithdrawalStatus::PENDING, $withdrawal->status);
        $this->assertEquals('Crédit Agricole', $withdrawal->bank_name);
    }

    /** @test */
    public function it_validates_minimum_amount()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('minimum de retrait');

        $this->service->createWithdrawalRequest(
            $this->user,
            5.00, // En dessous du minimum
            ['method' => 'bank_transfer', 'bank_name' => 'Test', 'bank_account' => '123']
        );
    }

    /** @test */
    public function it_validates_maximum_amount()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('maximum de retrait');

        $this->service->createWithdrawalRequest(
            $this->user,
            50000.00, // Au-dessus du maximum
            ['method' => 'bank_transfer', 'bank_name' => 'Test', 'bank_account' => '123']
        );
    }

    /** @test */
    public function it_validates_insufficient_balance()
    {
        // Wallet avec petit solde
        $this->wallet->update(['balance' => 50.00]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Solde insuffisant');

        $this->service->createWithdrawalRequest(
            $this->user,
            100.00,
            ['method' => 'bank_transfer', 'bank_name' => 'Test', 'bank_account' => '123']
        );
    }

    /** @test */
    public function it_validates_bank_details()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Coordonnées bancaires manquantes');

        $this->service->createWithdrawalRequest(
            $this->user,
            100.00,
            ['method' => 'bank_transfer'] // Manque bank_name et bank_account
        );
    }

    /** @test */
    public function it_validates_iban_correctly()
    {
        // IBAN valide
        $validIban = 'FR7612345678901234567890123';
        
        $result = $this->service->validateBankDetails([
            'method' => 'bank_transfer',
            'bank_name' => 'Test Bank',
            'iban' => $validIban,
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_invalid_iban()
    {
        // IBAN invalide
        $invalidIban = 'INVALID_IBAN';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('IBAN invalide');

        $this->service->validateBankDetails([
            'method' => 'bank_transfer',
            'bank_name' => 'Test Bank',
            'iban' => $invalidIban,
        ]);
    }

    /** @test */
    public function it_calculates_fee_correctly()
    {
        $fee = $this->service->calculateFee(100.00);
        
        $this->assertEquals(1.00, $fee);
        
        $fee = $this->service->calculateFee(250.00);
        $this->assertEquals(2.50, $fee);
    }

    /** @test */
    public function it_approves_withdrawal_request()
    {
        $withdrawal = WithdrawalRequest::create([
            'owner_type' => User::class,
            'owner_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'amount' => 100.00,
            'currency' => 'EUR',
            'fee' => 1.00,
            'net_amount' => 99.00,
            'status' => WithdrawalStatus::PENDING,
            'withdrawal_method' => 'bank_transfer',
            'bank_name' => 'Test Bank',
            'bank_account' => '123456',
        ]);

        $result = $this->service->approve($withdrawal, $this->user, 'Approved');

        $this->assertTrue($result);
        $this->assertEquals(WithdrawalStatus::APPROVED, $withdrawal->fresh()->status);
    }

    /** @test */
    public function it_rejects_withdrawal_request()
    {
        $withdrawal = WithdrawalRequest::create([
            'owner_type' => User::class,
            'owner_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'amount' => 100.00,
            'currency' => 'EUR',
            'fee' => 1.00,
            'net_amount' => 99.00,
            'status' => WithdrawalStatus::PENDING,
            'withdrawal_method' => 'bank_transfer',
            'bank_name' => 'Test Bank',
            'bank_account' => '123456',
        ]);

        $result = $this->service->reject($withdrawal, $this->user, 'Invalid account');

        $this->assertTrue($result);
        $this->assertEquals(WithdrawalStatus::CANCELLED, $withdrawal->fresh()->status);
    }

    /** @test */
    public function it_cannot_approve_non_pending_request()
    {
        $withdrawal = WithdrawalRequest::create([
            'owner_type' => User::class,
            'owner_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'amount' => 100.00,
            'currency' => 'EUR',
            'fee' => 1.00,
            'net_amount' => 99.00,
            'status' => WithdrawalStatus::COMPLETED,
            'withdrawal_method' => 'bank_transfer',
            'bank_name' => 'Test Bank',
            'bank_account' => '123456',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ne peut pas être approuvée');

        $this->service->approve($withdrawal, $this->user);
    }

    /** @test */
    public function it_returns_user_withdrawal_history()
    {
        // Créer plusieurs demandes
        for ($i = 0; $i < 3; $i++) {
            WithdrawalRequest::create([
                'owner_type' => User::class,
                'owner_id' => $this->user->id,
                'wallet_id' => $this->wallet->id,
                'amount' => 50.00 + ($i * 10),
                'currency' => 'EUR',
                'fee' => 0.50,
                'net_amount' => 49.50,
                'status' => WithdrawalStatus::PENDING,
                'withdrawal_method' => 'bank_transfer',
                'bank_name' => 'Test Bank',
                'bank_account' => '123456',
            ]);
        }

        // Créer des demandes pour un autre utilisateur
        $otherUser = User::factory()->create();
        $otherWallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $otherUser->id,
            'balance' => 500.00,
            'currency' => 'EUR',
            'status' => 'active',
        ]);

        WithdrawalRequest::create([
            'owner_type' => User::class,
            'owner_id' => $otherUser->id,
            'wallet_id' => $otherWallet->id,
            'amount' => 100.00,
            'currency' => 'EUR',
            'fee' => 1.00,
            'net_amount' => 99.00,
            'status' => WithdrawalStatus::PENDING,
            'withdrawal_method' => 'bank_transfer',
            'bank_name' => 'Test Bank',
            'bank_account' => '123456',
        ]);

        $history = $this->service->getUserHistory($this->user);

        $this->assertCount(3, $history);
    }

    /** @test */
    public function it_validates_card_details()
    {
        $result = $this->service->validateBankDetails([
            'method' => 'card',
            'card_last4' => '1234',
            'card_brand' => 'visa',
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_rejects_card_without_last4()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('4 derniers chiffres');

        $this->service->validateBankDetails([
            'method' => 'card',
            'card_brand' => 'visa',
        ]);
    }

    /** @test */
    public function it_validates_offline_successfully()
    {
        $withdrawal = WithdrawalRequest::create([
            'owner_type' => User::class,
            'owner_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'amount' => 100.00,
            'currency' => 'EUR',
            'fee' => 1.00,
            'net_amount' => 99.00,
            'status' => WithdrawalStatus::PENDING,
            'withdrawal_method' => 'bank_transfer',
            'bank_name' => 'Test Bank',
            'bank_account' => '123456',
            'metadata' => [],
        ]);

        $result = $this->service->validateOffline($withdrawal);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['valid']);
    }
}

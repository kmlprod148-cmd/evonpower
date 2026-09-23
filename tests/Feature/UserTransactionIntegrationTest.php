<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class UserTransactionIntegrationTest extends TestCase
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
    public function user_can_access_transactions_from_sidebar()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        // Create some transactions
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'Test credit');
        $wallet->debit(50, 'Test debit');

        $response = $this->actingAs($user)->get('/transactions');

        $response->assertStatus(200);
        $response->assertViewIs('transactions.index');
        $response->assertSee('Test credit');
        $response->assertSee('Test debit');
    }

    /** @test */
    public function different_user_roles_can_access_their_transactions()
    {
        $roles = ['operator', 'integrator', 'partner'];
        
        foreach ($roles as $roleName) {
            $user = User::factory()->create();
            $user->assignRole($roleName);
            
            $wallet = $user->getOrCreateWallet();
            $wallet->credit(100, "Test credit for {$roleName}");

            $response = $this->actingAs($user)->get('/transactions');

            $response->assertStatus(200);
            $response->assertSee("Test credit for {$roleName}");
        }
    }

    /** @test */
    public function user_can_navigate_to_transaction_details()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $transaction = $wallet->credit(100, 'Test transaction');

        // Navigate from index to details
        $indexResponse = $this->actingAs($user)->get('/transactions');
        $indexResponse->assertStatus(200);
        
        $detailsResponse = $this->actingAs($user)->get('/transactions/' . $transaction->id);
        $detailsResponse->assertStatus(200);
        $detailsResponse->assertViewIs('transactions.show');
        $detailsResponse->assertSee('Test transaction');
    }

    /** @test */
    public function user_can_export_their_transactions()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'Export test credit');
        $wallet->debit(50, 'Export test debit');

        $response = $this->actingAs($user)->get('/transactions/export/csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv');
        
        // Check CSV content
        $csvContent = $response->getContent();
        $this->assertStringContainsString('Export test credit', $csvContent);
        $this->assertStringContainsString('Export test debit', $csvContent);
    }

    /** @test */
    public function user_can_use_api_endpoints()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'API test transaction');

        // Test balance API
        $balanceResponse = $this->actingAs($user)->get('/transactions/api/balance');
        $balanceResponse->assertStatus(200);
        $balanceResponse->assertJson([
            'balance' => 100.0,
            'currency' => 'EUR',
        ]);

        // Test recent transactions API
        $recentResponse = $this->actingAs($user)->get('/transactions/api/recent');
        $recentResponse->assertStatus(200);
        $recentResponse->assertJsonStructure([
            'transactions',
            'wallet' => [
                'balance',
                'formatted_balance',
                'currency',
            ],
        ]);
    }

    /** @test */
    public function user_can_filter_and_search_transactions()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'Charging session payment');
        $wallet->debit(50, 'Service fee');
        $wallet->credit(200, 'Refund for overpayment');

        // Test type filtering
        $creditResponse = $this->actingAs($user)->get('/transactions?type=credit');
        $creditResponse->assertStatus(200);
        $creditResponse->assertSee('Charging session payment');
        $creditResponse->assertSee('Refund for overpayment');
        $creditResponse->assertDontSee('Service fee');

        // Test search
        $searchResponse = $this->actingAs($user)->get('/transactions?search=charging');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Charging session payment');
        $searchResponse->assertDontSee('Service fee');
        $searchResponse->assertDontSee('Refund for overpayment');
    }

    /** @test */
    public function user_can_see_their_wallet_balance()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'Initial credit');
        $wallet->debit(30, 'Payment');

        $response = $this->actingAs($user)->get('/transactions');

        $response->assertStatus(200);
        $response->assertSee('70.00 €'); // Current balance
        $response->assertSee('Current Balance');
    }

    /** @test */
    public function user_can_see_transaction_statistics()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'Credit 1');
        $wallet->credit(200, 'Credit 2');
        $wallet->debit(50, 'Debit 1');

        $response = $this->actingAs($user)->get('/transactions');

        $response->assertStatus(200);
        $response->assertSee('3'); // Total transactions
        $response->assertSee('300.00 €'); // Total credits
        $response->assertSee('50.00 €'); // Total debits
        $response->assertSee('250.00 €'); // Net amount
    }

    /** @test */
    public function user_cannot_access_other_users_transactions()
    {
        $user1 = User::factory()->create();
        $user1->assignRole('operator');
        
        $user2 = User::factory()->create();
        $user2->assignRole('operator');
        
        $wallet2 = $user2->getOrCreateWallet();
        $transaction2 = $wallet2->credit(100, 'User 2 transaction');

        // User 1 tries to access User 2's transaction
        $response = $this->actingAs($user1)->get('/transactions/' . $transaction2->id);
        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_transactions()
    {
        $response = $this->get('/transactions');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function transaction_details_show_correct_information()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $transaction = $wallet->credit(100, 'Test transaction', [
            'session_id' => 'test-session-123',
            'charging_point_id' => 1,
        ]);

        $response = $this->actingAs($user)->get('/transactions/' . $transaction->id);

        $response->assertStatus(200);
        $response->assertSee('Test transaction');
        $response->assertSee('+100.00 €');
        $response->assertSee('test-session-123');
        $response->assertSee('Wallet Information');
    }
}

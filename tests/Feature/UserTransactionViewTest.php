<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Partner;
use App\Models\Integrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class UserTransactionViewTest extends TestCase
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
    public function authenticated_user_can_view_their_transactions()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        $response = $this->actingAs($user)->get('/transactions');

        $response->assertStatus(200);
        $response->assertViewIs('transactions.index');
    }

    /** @test */
    public function unauthenticated_user_cannot_view_transactions()
    {
        $response = $this->get('/transactions');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function user_can_only_view_their_own_transactions()
    {
        $user1 = User::factory()->create();
        $user1->assignRole('operator');
        
        $user2 = User::factory()->create();
        $user2->assignRole('operator');

        // Create transactions for both users
        $wallet1 = $user1->getOrCreateWallet();
        $wallet2 = $user2->getOrCreateWallet();

        $transaction1 = $wallet1->credit(100, 'User 1 credit');
        $transaction2 = $wallet2->credit(200, 'User 2 credit');

        // User 1 should only see their own transactions
        $response = $this->actingAs($user1)->get('/transactions');

        $response->assertStatus(200);
        $response->assertSee('User 1 credit');
        $response->assertDontSee('User 2 credit');
    }

    /** @test */
    public function transactions_show_correct_colors()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();

        $wallet->credit(100, 'Credit transaction');
        $wallet->debit(50, 'Debit transaction');

        $response = $this->actingAs($user)->get('/transactions');

        $response->assertStatus(200);
        $response->assertSee('text-success'); // Green for credit
        $response->assertSee('text-danger'); // Red for debit
        $response->assertSee('badge-success'); // Green badge for credit
        $response->assertSee('badge-danger'); // Red badge for debit
    }

    /** @test */
    public function transactions_can_be_filtered_by_type()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();

        $wallet->credit(100, 'Credit transaction');
        $wallet->debit(50, 'Debit transaction');

        $response = $this->actingAs($user)->get('/transactions?type=credit');

        $response->assertStatus(200);
        $response->assertSee('Credit transaction');
        $response->assertDontSee('Debit transaction');
    }

    /** @test */
    public function transactions_can_be_filtered_by_date()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();

        // Create transaction with specific date
        $transaction = $wallet->credit(100, 'Test transaction');
        $transaction->update(['created_at' => now()->subDays(5)]);

        $response = $this->actingAs($user)->get('/transactions?date_from=' . now()->subDays(10)->format('Y-m-d') . '&date_to=' . now()->subDays(1)->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertSee('Test transaction');
    }

    /** @test */
    public function transactions_can_be_searched()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();

        $wallet->credit(100, 'Payment for charging session');
        $wallet->debit(50, 'Service fee');

        $response = $this->actingAs($user)->get('/transactions?search=charging');

        $response->assertStatus(200);
        $response->assertSee('Payment for charging session');
        $response->assertDontSee('Service fee');
    }

    /** @test */
    public function user_can_view_transaction_details()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $transaction = $wallet->credit(100, 'Test transaction');

        $response = $this->actingAs($user)->get('/transactions/' . $transaction->id);

        $response->assertStatus(200);
        $response->assertViewIs('transactions.show');
        $response->assertSee('Test transaction');
        $response->assertSee('+100.00 €');
    }

    /** @test */
    public function user_cannot_view_other_users_transaction_details()
    {
        $user1 = User::factory()->create();
        $user1->assignRole('operator');
        
        $user2 = User::factory()->create();
        $user2->assignRole('operator');
        
        $wallet2 = $user2->getOrCreateWallet();
        $transaction = $wallet2->credit(100, 'Test transaction');

        $response = $this->actingAs($user1)->get('/transactions/' . $transaction->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_export_their_transactions()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'Test transaction');

        $response = $this->actingAs($user)->get('/transactions/export/csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv');
        $response->assertHeader('Content-Disposition');
    }

    /** @test */
    public function transactions_show_wallet_balance()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'Test credit');

        $response = $this->actingAs($user)->get('/transactions');

        $response->assertStatus(200);
        $response->assertSee('100.00 €'); // Current balance
    }

    /** @test */
    public function transactions_show_statistics()
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
    public function integrator_can_view_their_transactions()
    {
        $integrator = User::factory()->create();
        $integrator->assignRole('integrator');
        
        $wallet = $integrator->getOrCreateWallet();
        $wallet->credit(1000, 'Integrator credit');

        $response = $this->actingAs($integrator)->get('/transactions');

        $response->assertStatus(200);
        $response->assertSee('Integrator credit');
    }

    /** @test */
    public function partner_can_view_their_transactions()
    {
        $partner = User::factory()->create();
        $partner->assignRole('partner');
        
        $wallet = $partner->getOrCreateWallet();
        $wallet->credit(500, 'Partner credit');

        $response = $this->actingAs($partner)->get('/transactions');

        $response->assertStatus(200);
        $response->assertSee('Partner credit');
    }

    /** @test */
    public function user_can_get_wallet_balance_api()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'Test credit');

        $response = $this->actingAs($user)->get('/transactions/api/balance');

        $response->assertStatus(200);
        $response->assertJson([
            'balance' => 100.0,
            'currency' => 'EUR',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_can_get_recent_transactions_api()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'Recent credit');
        $wallet->debit(50, 'Recent debit');

        $response = $this->actingAs($user)->get('/transactions/api/recent');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'transactions',
            'wallet' => [
                'balance',
                'formatted_balance',
                'currency',
            ],
        ]);
    }

    /** @test */
    public function pagination_works_correctly()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();

        // Create many transactions
        for ($i = 1; $i <= 25; $i++) {
            $wallet->credit($i, "Transaction $i");
        }

        $response = $this->actingAs($user)->get('/transactions');

        $response->assertStatus(200);
        $response->assertSee('Showing 1 to 20 of 25 results');
    }

    /** @test */
    public function empty_transactions_shows_appropriate_message()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        $response = $this->actingAs($user)->get('/transactions');

        $response->assertStatus(200);
        $response->assertSee('No transactions found');
    }

    /** @test */
    public function filtered_empty_transactions_shows_appropriate_message()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'Test transaction');

        $response = $this->actingAs($user)->get('/transactions?search=nonexistent');

        $response->assertStatus(200);
        $response->assertSee('No transactions found');
        $response->assertSee('Try adjusting your filters');
    }

    /** @test */
    public function transaction_details_show_metadata()
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
        $response->assertSee('Additional Information');
        $response->assertSee('test-session-123');
    }

    /** @test */
    public function transaction_details_show_wallet_information()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'Test transaction');
        
        $transaction = $wallet->transactions()->first();

        $response = $this->actingAs($user)->get('/transactions/' . $transaction->id);

        $response->assertStatus(200);
        $response->assertSee('Wallet Information');
        $response->assertSee('My Wallet');
        $response->assertSee('100.00 €');
    }
}

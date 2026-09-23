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

class AdminTransactionViewTest extends TestCase
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
    public function admin_can_view_transactions_page()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/admin/transactions');

        $response->assertStatus(200);
        $response->assertViewIs('admin.transactions.index');
    }

    /** @test */
    public function non_admin_cannot_view_transactions_page()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        $response = $this->actingAs($user)->get('/admin/transactions');

        $response->assertStatus(403);
    }

    /** @test */
    public function transactions_page_shows_all_transactions()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create test transactions
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $wallet1 = $user1->getOrCreateWallet();
        $wallet2 = $user2->getOrCreateWallet();

        $transaction1 = $wallet1->credit(100, 'Test credit');
        $transaction2 = $wallet2->debit(50, 'Test debit');

        $response = $this->actingAs($admin)->get('/admin/transactions');

        $response->assertStatus(200);
        $response->assertSee('Test credit');
        $response->assertSee('Test debit');
        $response->assertSee('+100.00 €');
        $response->assertSee('-50.00 €');
    }

    /** @test */
    public function transactions_page_shows_statistics()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create test transactions
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();

        $wallet->credit(100, 'Credit 1');
        $wallet->credit(200, 'Credit 2');
        $wallet->debit(50, 'Debit 1');

        $response = $this->actingAs($admin)->get('/admin/transactions');

        $response->assertStatus(200);
        $response->assertSee('3'); // Total transactions
        $response->assertSee('300.00 €'); // Total credits
        $response->assertSee('50.00 €'); // Total debits
        $response->assertSee('250.00 €'); // Net amount
    }

    /** @test */
    public function transactions_can_be_filtered_by_role()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create users with different roles
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        
        $partner = User::factory()->create();
        $partner->assignRole('partner');

        // Create transactions
        $operatorWallet = $operator->getOrCreateWallet();
        $partnerWallet = $partner->getOrCreateWallet();

        $operatorWallet->credit(100, 'Operator credit');
        $partnerWallet->credit(200, 'Partner credit');

        $response = $this->actingAs($admin)->get('/admin/transactions?role=operator');

        $response->assertStatus(200);
        $response->assertSee('Operator credit');
        $response->assertDontSee('Partner credit');
    }

    /** @test */
    public function transactions_can_be_filtered_by_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create users
        $user1 = User::factory()->create(['name' => 'User One']);
        $user2 = User::factory()->create(['name' => 'User Two']);

        // Create transactions
        $wallet1 = $user1->getOrCreateWallet();
        $wallet2 = $user2->getOrCreateWallet();

        $wallet1->credit(100, 'User One credit');
        $wallet2->credit(200, 'User Two credit');

        $response = $this->actingAs($admin)->get('/admin/transactions?user=' . $user1->id);

        $response->assertStatus(200);
        $response->assertSee('User One credit');
        $response->assertDontSee('User Two credit');
    }

    /** @test */
    public function transactions_can_be_filtered_by_date()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();

        // Create transaction with specific date
        $transaction = $wallet->credit(100, 'Test transaction');
        $transaction->update(['created_at' => now()->subDays(5)]);

        $response = $this->actingAs($admin)->get('/admin/transactions?date_from=' . now()->subDays(10)->format('Y-m-d') . '&date_to=' . now()->subDays(1)->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertSee('Test transaction');
    }

    /** @test */
    public function transactions_can_be_filtered_by_type()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();

        $wallet->credit(100, 'Credit transaction');
        $wallet->debit(50, 'Debit transaction');

        $response = $this->actingAs($admin)->get('/admin/transactions?type=credit');

        $response->assertStatus(200);
        $response->assertSee('Credit transaction');
        $response->assertDontSee('Debit transaction');
    }

    /** @test */
    public function transactions_can_be_searched()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();

        $wallet->credit(100, 'Payment for charging session');
        $wallet->debit(50, 'Service fee');

        $response = $this->actingAs($admin)->get('/admin/transactions?search=charging');

        $response->assertStatus(200);
        $response->assertSee('Payment for charging session');
        $response->assertDontSee('Service fee');
    }

    /** @test */
    public function admin_can_view_transaction_details()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        $transaction = $wallet->credit(100, 'Test transaction');

        $response = $this->actingAs($admin)->get('/admin/transactions/' . $transaction->id);

        $response->assertStatus(200);
        $response->assertViewIs('admin.transactions.show');
        $response->assertSee('Test transaction');
        $response->assertSee('+100.00 €');
    }

    /** @test */
    public function non_admin_cannot_view_transaction_details()
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        $otherUser = User::factory()->create();
        $wallet = $otherUser->getOrCreateWallet();
        $transaction = $wallet->credit(100, 'Test transaction');

        $response = $this->actingAs($user)->get('/admin/transactions/' . $transaction->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_export_transactions()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'Test transaction');

        $response = $this->actingAs($admin)->get('/admin/transactions/export/csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv');
        $response->assertHeader('Content-Disposition');
    }

    /** @test */
    public function transactions_show_correct_colors()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();

        $wallet->credit(100, 'Credit transaction');
        $wallet->debit(50, 'Debit transaction');

        $response = $this->actingAs($admin)->get('/admin/transactions');

        $response->assertStatus(200);
        $response->assertSee('text-success'); // Green for credit
        $response->assertSee('text-danger'); // Red for debit
        $response->assertSee('badge-success'); // Green badge for credit
        $response->assertSee('badge-danger'); // Red badge for debit
    }

    /** @test */
    public function transactions_show_owner_information()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
        $user->assignRole('operator');
        
        $wallet = $user->getOrCreateWallet();
        $wallet->credit(100, 'Test transaction');

        $response = $this->actingAs($admin)->get('/admin/transactions');

        $response->assertStatus(200);
        $response->assertSee('Test User');
        $response->assertSee('test@example.com');
        $response->assertSee('operator');
    }

    /** @test */
    public function transactions_show_partner_and_integrator_transactions()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create integrator
        $integrator = Integrator::create([
            'name' => 'Test Integrator',
            'email' => 'integrator@test.com',
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        // Create partner
        $partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'integrator_id' => $integrator->id,
            'created_by' => $admin->id,
            'created_by_role' => 'admin'
        ]);

        // Create transactions
        $integratorWallet = $integrator->getOrCreateWallet();
        $partnerWallet = $partner->getOrCreateWallet();

        $integratorWallet->credit(1000, 'Integrator credit');
        $partnerWallet->credit(500, 'Partner credit');

        $response = $this->actingAs($admin)->get('/admin/transactions');

        $response->assertStatus(200);
        $response->assertSee('Test Integrator');
        $response->assertSee('Test Partner');
        $response->assertSee('Integrator credit');
        $response->assertSee('Partner credit');
    }

    /** @test */
    public function pagination_works_correctly()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create many transactions
        $user = User::factory()->create();
        $wallet = $user->getOrCreateWallet();

        for ($i = 1; $i <= 60; $i++) {
            $wallet->credit($i, "Transaction $i");
        }

        $response = $this->actingAs($admin)->get('/admin/transactions');

        $response->assertStatus(200);
        $response->assertSee('Showing 1 to 50 of 60 results');
    }
}

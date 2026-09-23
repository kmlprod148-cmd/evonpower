<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\Connector;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use App\Models\BusinessProfile;

class TransactionCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $regularUser;
    protected ChargingPoint $chargingPoint;
    protected Connector $connector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        // Create necessary entities
        $businessProfile = BusinessProfile::factory()->create([
            'operator_commission' => 50,
            'integrator_commission' => 30,
            'partner_commission' => 20,
            'owner_commission' => 0,
        ]);

        $this->chargingPoint = ChargingPoint::factory()->create([
            'business_profile_id' => $businessProfile->id,
        ]);
        
        $this->connector = Connector::factory()->create([
            'charging_point_id' => $this->chargingPoint->id,
            'connector_id' => 1,
        ]);

        // Create users with roles
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('user');
    }

    #[Test]
    public function admin_can_view_transactions()
    {
        $this->actingAs($this->adminUser);
        $response = $this->get(route('transactions.index'));

        $response->assertOk();
        $response->assertViewIs('transactions.index');
        $response->assertViewHas('transactions');
    }

    #[Test]
    public function regular_user_can_view_transactions()
    {
        // Assuming regular users have permission to view transactions
        $this->actingAs($this->regularUser);
        $response = $this->get(route('transactions.index'));

        $response->assertOk();
        $response->assertViewIs('transactions.index');
        $response->assertViewHas('transactions');
    }

    #[Test]
    public function admin_can_create_transaction()
    {
        $this->actingAs($this->adminUser);
        $transactionData = [
            'transaction_id' => (string) \Illuminate\Support\Str::uuid(),
            'charging_point_id' => $this->chargingPoint->id,
            'connector_id' => $this->connector->connector_id,
            'user_id' => $this->regularUser->id,
            'start_timestamp' => now(),
            'meter_start' => 0,
            'status' => 'in_progress',
            'currency' => 'EUR',
        ];
        $response = $this->post(route('transactions.store'), $transactionData);
        $response->assertRedirect(route('transactions.show', ['transaction' => Transaction::latest()->first()->id]));
        $this->assertDatabaseHas('transactions', [
            'transaction_id' => $transactionData['transaction_id'],
            'charging_point_id' => $transactionData['charging_point_id'],
            'connector_id' => $transactionData['connector_id'],
        ]);
    }

    #[Test]
    public function admin_cannot_create_transaction_with_duplicate_transaction_id()
    {
        $this->actingAs($this->adminUser);
        $existingTransaction = Transaction::factory()->create();
        $transactionData = [
            'transaction_id' => $existingTransaction->transaction_id,
            'charging_point_id' => $this->chargingPoint->id,
            'connector_id' => $this->connector->connector_id,
            'user_id' => $this->regularUser->id,
            'start_timestamp' => now(),
            'meter_start' => 0,
            'status' => 'in_progress',
            'currency' => 'EUR',
        ];
        $response = $this->post(route('transactions.store'), $transactionData);
        $response->assertSessionHasErrors('transaction_id');
        $response->assertRedirect();
        $this->assertDatabaseMissing('transactions', [
            'transaction_id' => $transactionData['transaction_id'],
        ]);
    }

    #[Test]
    public function admin_can_view_transaction_details()
    {
        $this->actingAs($this->adminUser);
        $transaction = Transaction::factory()->create();

        $response = $this->get(route('transactions.show', ['transaction' => $transaction->id]));

        $response->assertOk();
        $response->assertViewIs('transactions.show');
        $response->assertViewHas('transaction', $transaction);
    }

    #[Test]
    public function admin_can_update_transaction()
    {
        $this->actingAs($this->adminUser);
        $transaction = Transaction::factory()->create([
             'start_timestamp' => now()->subHour(),
             'meter_start' => 0,
             'status' => 'in_progress',
             'currency' => 'EUR',
        ]);
        $updateData = $transaction->toArray();
        $updateData['stop_timestamp'] = now();
        $updateData['meter_stop'] = 10;
        $updateData['status'] = 'completed';
        $updateData['currency'] = 'EUR';
        unset($updateData['energy_delivered']); // Si non requis ou problème de décimales
        unset($updateData['price_energy']); // Idem
        unset($updateData['duration']); // Calculé automatiquement
        $response = $this->put(route('transactions.update', $transaction), $updateData);
        $response->assertRedirect(route('transactions.show', $transaction));
        $this->assertDatabaseHas('transactions', array_merge(['id' => $transaction->id], [
            'meter_stop' => 10,
            'status' => 'completed',
        ]));
        $updatedTransaction = $transaction->fresh();
        $this->assertNotNull($updatedTransaction->stop_timestamp);
    }

    #[Test]
    public function admin_cannot_update_transaction_with_stop_time_before_start_time()
    {
        $this->actingAs($this->adminUser);
        $transaction = Transaction::factory()->create([
             'start_timestamp' => now(),
             'meter_start' => 0,
             'status' => 'in_progress',
             'currency' => 'EUR',
        ]);
        $updateData = $transaction->toArray();
        $updateData['stop_timestamp'] = now()->subHour();
        $updateData['meter_stop'] = 10;
        $updateData['status'] = 'completed';
        $updateData['currency'] = 'EUR';

        $response = $this->put(route('transactions.update', $transaction), $updateData);

        $response->assertSessionHasErrors('stop_timestamp');
        $response->assertRedirect();
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'in_progress',
        ]);
    }

     #[Test]
    public function admin_cannot_update_transaction_with_meter_stop_less_than_meter_start()
    {
        $this->actingAs($this->adminUser);
        $transaction = Transaction::factory()->create([
             'start_timestamp' => now()->subHour(),
             'meter_start' => 10,
             'status' => 'in_progress',
             'currency' => 'EUR',
        ]);
        $updateData = $transaction->toArray();
        $updateData['stop_timestamp'] = now();
        $updateData['meter_stop'] = 5;
        $updateData['status'] = 'completed';
        $updateData['currency'] = 'EUR';

        $response = $this->put(route('transactions.update', $transaction), $updateData);

        $response->assertSessionHasErrors('meter_stop');
        $response->assertRedirect();
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => 'in_progress',
        ]);
    }

    #[Test]
    public function admin_can_delete_transaction()
    {
        $this->actingAs($this->adminUser);
        $transaction = Transaction::factory()->create();

        $response = $this->delete(route('transactions.destroy', $transaction));

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    #[Test]
    public function api_returns_repartition_for_transaction()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        $transaction = Transaction::factory()->create([
            'repartition_breakdown' => [
                'operator' => 10,
                'integrator' => 20,
                'owner' => 70,
            ],
        ]);

        $response = $this->getJson('/api/transactions/' . $transaction->transaction_id . '/repartition');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'repartition' => [
                        'operator',
                        'integrator',
                        'partner',
                        'owner',
                        'platform_fee',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'repartition' => [
                        'operator' => 10,
                        'integrator' => 20,
                        'partner' => 0, // Assuming partner is 0 for this test case
                        'owner' => 70,
                        'platform_fee' => 0, // Assuming platform_fee is 0 for this test case
                    ],
                ],
            ]);
    }

    // Add tests for other user roles and permissions as needed
}
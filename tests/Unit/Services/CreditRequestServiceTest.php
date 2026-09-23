<?php

namespace Tests\Unit\Services;

use App\Models\ClientUser;
use App\Models\User;
use App\Services\CreditRequestService;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreditRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('user', 'web');
    }

    public function test_it_resolves_linked_user_when_client_user_id_overlaps_with_system_user_id(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $linkedUser = User::factory()->create();
        $linkedUser->assignRole('user');

        $clientUser = ClientUser::create([
            'name' => 'Client Mobile',
            'email' => 'client-mobile@example.com',
            'user_id' => $linkedUser->id,
            'is_active' => true,
        ]);

        $this->assertSame($admin->id, $clientUser->id, 'Test setup requires overlapping IDs.');

        $service = new CreditRequestService(app(CreditService::class));

        $result = $service->createRequest($clientUser->id, 150.00, 'manuel', 'Demande test');

        $this->assertTrue($result['success']);
        $this->assertSame($linkedUser->id, $result['request']->client_id);
        $this->assertSame($admin->id, $result['request']->owner_id);
        $this->assertDatabaseHas('credit_requests', [
            'id' => $result['request']->id,
            'client_id' => $linkedUser->id,
            'owner_id' => $admin->id,
            'amount' => 150.00,
            'status' => 'pending',
        ]);
    }
}

<?php

namespace Tests\Feature\Repositories;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Group;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\ChargingPoint;

class RepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        // Create a user for testing purposes if needed by GroupRepository
        User::factory()->create(['id' => 1]);
    }

    public function test_all_repositories_can_be_instantiated()
    {
        $repositories = [
            'App\Repositories\IntegratorRepository',
            'App\Repositories\GroupRepository',
            // 'App\Repositories\PartnerRepository', // Uncomment if PartnerRepository is ready for DI
            // 'App\Repositories\ChargingPointRepository', // Uncomment if ChargingPointRepository is ready for DI
        ];

        foreach ($repositories as $repositoryClass) {
            $repo = app($repositoryClass);
            $this->assertNotNull($repo);
        }
    }

    public function test_group_repository_crud_operations()
    {
        $repo = app('App\Repositories\GroupRepository');
        
        // Create
        $group = $repo->create([
            'name' => 'Test Group',
            'type' => 'public',
            'user_id' => 1, // Ensure user with ID 1 exists
        ]);
        
        $this->assertNotNull($group->id);
        
        // Read
        $found = $repo->find($group->id);
        $this->assertEquals('Test Group', $found->name);
        
        // Update
        $updated = $repo->update($group, ['name' => 'Updated Group']);
        $this->assertNotNull($updated);
        $this->assertEquals('Updated Group', $updated->name);
        
        // Delete
        $deleted = $repo->delete($group);
        $this->assertTrue($deleted);
    }

    public function test_integrator_repository_crud_operations()
    {
        $repo = app('App\Repositories\IntegratorRepository');

        $data = [
            'name' => 'Test Integrator',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TS',
            'zip' => '12345',
            'country' => 'Test Country',
            'contact_person' => 'Test Contact',
            'contact_email' => 'contact@example.com',
            'contact_phone' => '0987654321',
            'is_active' => true,
        ];
        
        // Create
        $integrator = $repo->create($data);
        
        $this->assertNotNull($integrator->id);
        
        // Read
        $found = $repo->find($integrator->id);
        $this->assertEquals('Test Integrator', $found->name);
        
        // Update
        $updated = $repo->update($integrator, ['name' => 'Updated Integrator']);
        $this->assertNotNull($updated); // update returns model, not bool
        $this->assertEquals('Updated Integrator', $updated->name);
        
        // Delete
        $deleted = $repo->delete($integrator);
        $this->assertTrue($deleted);
    }
}
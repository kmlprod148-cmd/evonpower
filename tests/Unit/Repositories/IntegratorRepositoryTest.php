<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\IntegratorRepository;
use App\Models\Integrator;
use ReflectionMethod;
use Mockery;

class IntegratorRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    public function test_model_method_exists_and_is_public()
    {
        $repo = app(IntegratorRepository::class);
        
        $this->assertTrue(method_exists($repo, 'model'));
        
        $reflection = new ReflectionMethod($repo, 'model');
        $this->assertTrue($reflection->isPublic());
    }

    public function test_model_method_returns_correct_class()
    {
        $repo = app(IntegratorRepository::class);
        
        $this->assertEquals(Integrator::class, $repo->model());
    }

    public function test_repository_can_create_model()
    {
        $repo = app(IntegratorRepository::class);
        
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
        
        $integrator = $repo->create($data);
        
        $this->assertInstanceOf(Integrator::class, $integrator);
        $this->assertDatabaseHas('integrators', ['email' => 'test@example.com']);
    }
}
<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\GroupRepository;
use App\Models\Group;
use ReflectionMethod;

class GroupRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    public function test_model_method_is_implemented()
    {
        $repo = app(GroupRepository::class);
        
        $this->assertTrue(method_exists($repo, 'model'));
        $this->assertEquals(Group::class, $repo->model());
    }

    public function test_repository_inheritance()
    {
        $repo = app(GroupRepository::class);
        
        $this->assertInstanceOf('App\Repositories\BaseRepository', $repo);
    }

    public function test_repository_can_create_model()
    {
        $repo = app(GroupRepository::class);
        
        $data = [
            'name' => 'Test Group',
            'type' => 'public',
            'user_id' => 1, // Assuming a user with ID 1 exists or is created by factories
        ];
        
        $group = $repo->create($data);
        
        $this->assertInstanceOf(Group::class, $group);
        $this->assertDatabaseHas('groups', ['name' => 'Test Group']);
    }
}
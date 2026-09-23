<?php

namespace App\Core\Services;

use App\Core\Interfaces\GroupRepositoryInterface;
use App\Core\Interfaces\GroupServiceInterface;
use App\Exceptions\CrudException;
use Illuminate\Database\Eloquent\Model;

class GroupService implements GroupServiceInterface
{
    protected GroupRepositoryInterface $repository;

    public function __construct(GroupRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function createGroup(array $data): Model
    {
        try {
            return $this->repository->create($data);
        } catch (\Exception $e) {
            throw new CrudException("Failed to create group: " . $e->getMessage());
        }
    }

    public function updateGroup(int $id, array $data): Model
    {
        try {
            $group = $this->repository->find($id);
            if (!$group) {
                throw new CrudException("Group with ID {$id} not found.");
            }
            $this->repository->update($id, $data);
            return $group->fresh();
        } catch (\Exception $e) {
            throw new CrudException("Failed to update group with ID {$id}: " . $e->getMessage());
        }
    }

    public function deleteGroup(int $id): bool
    {
        try {
            $group = $this->repository->find($id);
            if (!$group) {
                throw new CrudException("Group with ID {$id} not found.");
            }
            return $this->repository->delete($id);
        } catch (\Exception $e) {
            throw new CrudException("Failed to delete group with ID {$id}: " . $e->getMessage());
        }
    }
}
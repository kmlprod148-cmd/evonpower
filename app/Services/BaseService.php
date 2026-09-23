<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class BaseService
{
    protected $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $model = $this->repository->create($data);
            DB::commit();
            $this->logActivity('created', $model, $data);
            return $model;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating model: ' . $e->getMessage());
            throw new \App\Exceptions\CrudException('Error creating model: ' . $e->getMessage(), 0, $e); // Throw CrudException
        }
    }

    public function update($model, array $data)
    {
        DB::beginTransaction();
        try {
            $updated = $this->repository->update($model->id, $data);
            DB::commit();
            if ($updated) {
                $this->logActivity('updated', $model, $data);
            }
            return $updated;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating model: ' . $e->getMessage());
            throw new \App\Exceptions\CrudException('Error updating model: ' . $e->getMessage(), 0, $e); // Throw CrudException
        }
    }

    public function delete($model)
    {
        DB::beginTransaction();
        try {
            $deleted = $this->repository->delete($model->id);
            DB::commit();
            if ($deleted) {
                $this->logActivity('deleted', $model);
            }
            return $deleted;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting model: ' . $e->getMessage());
            throw new \App\Exceptions\CrudException('Error deleting model: ' . $e->getMessage(), 0, $e); // Throw CrudException
        }
    }

    protected function logActivity(string $action, $model, array $data = [])
    {
        // Check if activity function exists
        if (function_exists('activity')) {
            activity()
                ->performedOn($model)
                ->causedBy(auth()->user())
                ->withProperties($data)
                ->log($action);
        } else {
            // Fallback: Log to Laravel's default log
            \Log::info("Activity: {$action}", [
                'model' => get_class($model),
                'model_id' => $model->id ?? null,
                'user_id' => auth()->id(),
                'data' => $data
            ]);
        }
    }
}
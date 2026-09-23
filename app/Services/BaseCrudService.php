<?php
namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\CrudException;
use Illuminate\Support\Facades\DB;

abstract class BaseCrudService
{
    protected $repository;

    public function create(array $data): Model
    {
        try {
            Log::info('BaseCrudService create method called', ['data' => $data]);
            DB::beginTransaction();
            Log::info('Database transaction started');

            $data = $this->preprocessData($data);
            $this->validateCreate($data);

            $model = $this->repository->create($data);
            Log::info('Repository create method called', ['model_id' => $model->id ?? null]);
            $this->afterCreate($model, $data);

            DB::commit();
            Log::info('Database transaction committed');
            return $model;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create model', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw new CrudException("Failed to create: " . $e->getMessage());
        }
    }

    public function update(Model $model, array $data): Model
    {
        try {
            DB::beginTransaction();

            $data = $this->preprocessData($data);
            $this->validateUpdate($model, $data);

            $model = $this->repository->update($model, $data);
            $this->afterUpdate($model, $data);

            DB::commit();
            return $model;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new CrudException("Failed to update: " . $e->getMessage());
        }
    }

    public function delete(Model $model): bool
    {
        try {
            $this->validateDelete($model);

            DB::beginTransaction();
            $this->beforeDelete($model);

            $result = $this->repository->delete($model);

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new CrudException("Failed to delete: " . $e->getMessage());
        }
    }

    // Hook methods for customization
    protected function preprocessData(array $data): array { return $data; }
    protected function validateCreate(array $data): void {}
    protected function validateUpdate(Model $model, array $data): void {}
    protected function validateDelete(Model $model): void {}
    protected function afterCreate(Model $model, array $data): void {}
    protected function afterUpdate(Model $model, array $data): void {}
    protected function beforeDelete(Model $model): void {}
}
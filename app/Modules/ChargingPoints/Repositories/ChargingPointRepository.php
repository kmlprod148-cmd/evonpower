<?php

namespace App\Modules\ChargingPoints\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Repositories\Interfaces\ChargingPointRepositoryInterface;
use App\Modules\ChargingPoints\Models\ChargingPoint;
use App\Modules\ChargingPoints\Models\Connector;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ChargingPointRepository extends BaseRepository implements ChargingPointRepositoryInterface
{
    /**
     * ChargingPointRepository constructor.
     * 
     * @param ChargingPoint $model
     */
    public function __construct(ChargingPoint $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupère les points de recharge avec filtres avancés
     * 
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFiltered(array $filters = [])
    {
        $query = $this->applyFilters($this->model->newQuery(), $filters);
        
        return $query->get();
    }

    /**
     * Récupère les points de recharge avec pagination et filtres avancés
     * 
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, array $filters = [])
    {
        $query = $this->applyFilters($this->model->newQuery(), $filters);
        
        return $query->paginate($perPage);
    }
    
    /**
     * Applique les filtres à la requête
     * 
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }
        
        if (isset($filters['integrator_id'])) {
            $query->where('integrator_id', $filters['integrator_id']);
        }
        
        if (isset($filters['partner_id'])) {
            $query->where('partner_id', $filters['partner_id']);
        }
        
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }
        
        if (isset($filters['accessibility'])) {
            $query->where('accessibility', $filters['accessibility']);
        }
        
        if (isset($filters['manufacturer'])) {
            $query->where('manufacturer', $filters['manufacturer']);
        }
        
        if (isset($filters['model'])) {
            $query->where('model', $filters['model']);
        }
        
        if (isset($filters['power_min'])) {
            $query->where('power_output', '>=', $filters['power_min']);
        }
        
        if (isset($filters['power_max'])) {
            $query->where('power_output', '<=', $filters['power_max']);
        }
        
        if (isset($filters['installation_date_from'])) {
            $query->whereDate('installation_date', '>=', $filters['installation_date_from']);
        }
        
        if (isset($filters['installation_date_to'])) {
            $query->whereDate('installation_date', '<=', $filters['installation_date_to']);
        }
        
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
        
        $relations = $filters['with'] ?? [];
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query;
    }
    
    public function findBySerialNumber(string $serialNumber): ?ChargingPoint
    {
        return $this->model->where('serial_number', $serialNumber)->first();
    }
}
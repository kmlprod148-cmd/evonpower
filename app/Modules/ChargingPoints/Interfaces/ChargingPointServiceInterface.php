<?php

namespace App\Modules\ChargingPoints\Interfaces;

use App\Core\Interfaces\BaseServiceInterface;
use App\Modules\ChargingPoints\DTOs\ChargingPointDTO;
use App\Modules\ChargingPoints\Models\ChargingPoint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ChargingPointServiceInterface extends BaseServiceInterface
{
    /**
     * Récupère les points de charge avec filtres
     * 
     * @param array $filters
     * @return Collection
     */
    public function getFiltered(array $filters = []): Collection;

    /**
     * Récupère les points de charge avec pagination
     * 
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Crée un nouveau point de charge avec ses connecteurs
     * 
     * @param ChargingPointDTO $dto
     * @return ChargingPoint
     */
    public function createWithConnectors(ChargingPointDTO $dto): ChargingPoint;

    /**
     * Met à jour un point de charge avec ses connecteurs
     * 
     * @param int $id
     * @param ChargingPointDTO $dto
     * @return ChargingPoint
     */
    public function updateWithConnectors(int $id, ChargingPointDTO $dto): ChargingPoint;

    /**
     * Connecte un point de charge via OCPP
     * 
     * @param int $id
     * @param array $connectionDetails
     * @return array
     */
    public function connect(int $id, array $connectionDetails): array;

    /**
     * Déconnecte un point de charge
     * 
     * @param int $id
     * @return array
     */
    public function disconnect(int $id): array;

    /**
     * Envoie une commande à un point de charge
     * 
     * @param int $id
     * @param string $command
     * @param array $params
     * @return array
     */
    public function sendCommand(int $id, string $command, array $params = []): array;
}
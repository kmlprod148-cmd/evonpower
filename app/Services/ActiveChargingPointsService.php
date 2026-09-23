<?php

namespace App\Services;

use App\Repositories\ChargingPointRepository;
use Illuminate\Support\Facades\Log;

class ActiveChargingPointsService
{
    protected $repository;

    public function __construct(ChargingPointRepository $repository = null)
    {
        $this->repository = $repository;
    }

    /**
     * Enregistre les données des bornes actives dans la base de données
     * 
     * @param array $data Les données à enregistrer
     * @return bool
     */
    public function recordActiveChargingPoints(array $data)
    {
        try {
            // Si le repository n'est pas injecté (pour les tests par exemple)
            if (!$this->repository) {
                Log::info('Données simulées enregistrées', $data);
                return true;
            }
            
            // Sinon utiliser le repository pour sauvegarder les données
            return $this->repository->saveActiveChargingPointsData($data);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement des données des bornes: ' . $e->getMessage());
            return false;
        }
    }
}
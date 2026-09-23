<?php

namespace App\Services;

use App\Repositories\BorneRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class BorneService extends BaseService
{
    protected $repository;

    public function __construct(BorneRepository $repository)
    {
        parent::__construct($repository);
        $this->repository = $repository;
    }

    // You can add specific methods for Borne-related business logic here if needed.
    // Otherwise, the create, update, and delete methods from BaseService will be used,
    // which in turn call the corresponding methods on the BorneRepository.

    /**
     * Connect a borne by its ID with validation and error handling
     */
    public function connectById(int $borneId): \App\Models\ChargingPoint
    {
        try {
            $borne = $this->repository->find($borneId);
            if (!$borne) {
                throw new \Exception('Borne introuvable pour l\'ID fourni.');
            }
            
            // Add any additional connection logic here
            // For example, check if the borne is available for connection
            if ($borne->status === 'offline') {
                throw new \Exception('Cette borne est actuellement hors ligne et ne peut pas être connectée.');
            }
            
            if ($borne->status === 'maintenance') {
                throw new \Exception('Cette borne est en maintenance et ne peut pas être connectée.');
            }
            
            Log::info('Borne connectée avec succès', [
                'borne_id' => $borneId,
                'borne_name' => $borne->name,
                'status' => $borne->status
            ]);
            
            return $borne;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la connexion de la borne', [
                'borne_id' => $borneId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // Example of a specific method:
    // public function updateStatus($borneId, string $status)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $updated = $this->repository->update($borneId, ['status' => $status]);
    //         DB::commit();
    //         if ($updated) {
    //             $borne = $this->repository->find($borneId);
    //             $this->logActivity('updated_status', $borne, ['status' => $status]);
    //         }
    //         return $updated;
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error updating borne status: ' . $e->getMessage());
    //         throw $e;
    //     }
    // }
}
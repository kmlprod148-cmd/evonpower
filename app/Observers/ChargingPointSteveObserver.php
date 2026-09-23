<?php

namespace App\Observers;

use App\Models\ChargingPoint;
use App\Services\ChargingPointSteveSyncService;
use Illuminate\Support\Facades\Log;

/**
 * Observer pour synchroniser automatiquement les bornes avec Steve API
 */
class ChargingPointSteveObserver
{
    private static bool $skipCreateSync = false;

    protected ChargingPointSteveSyncService $syncService;

    public function __construct(ChargingPointSteveSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Run model creation while leaving all other model events intact, but skip
     * this legacy create-sync observer. ChargingPointService performs the
     * canonical atomic SteVe provisioning itself.
     */
    public static function withoutCreateSync(callable $callback): mixed
    {
        $previous = self::$skipCreateSync;
        self::$skipCreateSync = true;

        try {
            return $callback();
        } finally {
            self::$skipCreateSync = $previous;
        }
    }

    /**
     * Handle the ChargingPoint "created" event.
     */
    public function created(ChargingPoint $chargingPoint): void
    {
        if (self::$skipCreateSync) {
            Log::debug('ChargingPointSteveObserver: Skipping legacy create sync during managed provisioning', [
                'charging_point_id' => $chargingPoint->id,
            ]);
            return;
        }

        // Synchroniser automatiquement avec Steve si activé
        if ($chargingPoint->steve_auto_sync !== false) {
            try {
                $result = $this->syncService->syncCreateToSteve($chargingPoint, true);
                
                if ($result['success'] && $result['synced']) {
                    Log::info('ChargingPointSteveObserver: Charging point synced to Steve on creation', [
                        'charging_point_id' => $chargingPoint->id,
                        'steve_pk' => $chargingPoint->fresh()->steve_charge_box_pk
                    ]);
                } elseif (!$result['success']) {
                    Log::warning('ChargingPointSteveObserver: Failed to sync on creation', [
                        'charging_point_id' => $chargingPoint->id,
                        'error' => $result['error'] ?? 'Unknown'
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('ChargingPointSteveObserver: Exception during sync on creation', [
                    'charging_point_id' => $chargingPoint->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle the ChargingPoint "updated" event.
     */
    public function updated(ChargingPoint $chargingPoint): void
    {
        // Synchroniser automatiquement avec Steve si activé et si déjà synchronisé
        if ($chargingPoint->steve_auto_sync !== false && !empty($chargingPoint->steve_charging_point_id)) {
            // Ne synchroniser que si les données pertinentes ont changé
            $relevantFields = [
                'name', 'description', 'notes', 'address', 'city', 'postal_code', 
                'country', 'latitude', 'longitude', 'status'
            ];

            $hasRelevantChanges = false;
            foreach ($relevantFields as $field) {
                if ($chargingPoint->isDirty($field)) {
                    $hasRelevantChanges = true;
                    break;
                }
            }

            if ($hasRelevantChanges) {
                try {
                    $result = $this->syncService->syncUpdateToSteve($chargingPoint);
                    
                    if ($result['success'] && $result['synced']) {
                        Log::info('ChargingPointSteveObserver: Charging point update synced to Steve', [
                            'charging_point_id' => $chargingPoint->id
                        ]);
                    } elseif (!$result['success']) {
                        Log::warning('ChargingPointSteveObserver: Failed to sync update', [
                            'charging_point_id' => $chargingPoint->id,
                            'error' => $result['error'] ?? 'Unknown'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('ChargingPointSteveObserver: Exception during sync on update', [
                        'charging_point_id' => $chargingPoint->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Handle the ChargingPoint "deleting" event.
     * Note: On utilise "deleting" au lieu de "deleted" pour pouvoir bloquer la suppression si nécessaire
     */
    public function deleting(ChargingPoint $chargingPoint): bool
    {
        // Synchroniser automatiquement avec Steve si activé et si synchronisé
        if ($chargingPoint->steve_auto_sync !== false && !empty($chargingPoint->steve_charging_point_id)) {
            try {
                $result = $this->syncService->syncDeleteToSteve($chargingPoint, false);
                
                if ($result['success']) {
                    Log::info('ChargingPointSteveObserver: Charging point deletion synced to Steve', [
                        'charging_point_id' => $chargingPoint->id
                    ]);
                    return true; // Autoriser la suppression locale
                } else {
                    Log::warning('ChargingPointSteveObserver: Failed to sync deletion to Steve', [
                        'charging_point_id' => $chargingPoint->id,
                        'error' => $result['error'] ?? 'Unknown'
                    ]);
                    
                    // Par défaut, bloquer la suppression locale si Steve échoue
                    // L'utilisateur devra utiliser force_delete
                    return false; // Bloquer la suppression
                }
            } catch (\Exception $e) {
                Log::error('ChargingPointSteveObserver: Exception during sync on deletion', [
                    'charging_point_id' => $chargingPoint->id,
                    'error' => $e->getMessage()
                ]);
                
                // En cas d'exception, bloquer la suppression
                return false;
            }
        }

        // Si pas de synchronisation Steve, autoriser la suppression
        return true;
    }
}


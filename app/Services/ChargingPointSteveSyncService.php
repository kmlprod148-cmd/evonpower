<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Services\SteveService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Service de synchronisation entre les bornes locales et l'API Steve
 * 
 * @deprecated Use SteVeDataSyncService instead. This class will be removed in a future version.
 * 
 * Gère la création, mise à jour et suppression bidirectionnelle
 */
class ChargingPointSteveSyncService
{
    protected SteveService $steveService;

    public function __construct(SteveService $steveService)
    {
        $this->steveService = $steveService;
    }

    /**
     * Synchroniser la création d'une borne locale vers Steve API
     * 
     * @param ChargingPoint $chargingPoint
     * @param bool $autoSync Si false, ne pas synchroniser automatiquement
     * @return array
     */
    public function syncCreateToSteve(ChargingPoint $chargingPoint, bool $autoSync = true): array
    {
        if (!$autoSync) {
            Log::info('ChargingPointSteveSyncService: Auto-sync disabled for creation', [
                'charging_point_id' => $chargingPoint->id
            ]);
            return ['success' => true, 'synced' => false, 'reason' => 'auto_sync_disabled'];
        }

        try {
            Log::info('ChargingPointSteveSyncService: Syncing new charging point to Steve', [
                'charging_point_id' => $chargingPoint->id,
                'name' => $chargingPoint->name
            ]);

            // Préparer les données pour l'API Steve
            $steveData = $this->mapChargingPointToSteveFormat($chargingPoint);

            // Créer sur l'API Steve
            $result = $this->steveService->createChargePoint($steveData);

            if ($result['success']) {
                // Mettre à jour la borne locale avec les données Steve (sans déclencher l'observer)
                $chargingPoint->updateQuietly([
                    'steve_charging_point_id' => $result['data']['chargeBoxId'] ?? $steveData['chargeBoxId'],
                    'steve_charge_box_pk' => $result['data']['chargeBoxPk'] ?? null,
                    'steve_synced_at' => now(),
                    'steve_sync_status' => 'synced'
                ]);

                Log::info('ChargingPointSteveSyncService: Successfully synced to Steve', [
                    'charging_point_id' => $chargingPoint->id,
                    'steve_pk' => $result['data']['chargeBoxPk'] ?? 'N/A'
                ]);

                return [
                    'success' => true,
                    'synced' => true,
                    'steve_data' => $result['data'],
                    'message' => 'Borne synchronisée avec succès sur Steve'
                ];
            } else {
                // Échec de la synchronisation mais la borne locale est créée
                $chargingPoint->updateQuietly([
                    'steve_sync_status' => 'failed',
                    'steve_sync_error' => $result['error'] ?? 'Unknown error'
                ]);

                Log::warning('ChargingPointSteveSyncService: Failed to sync to Steve', [
                    'charging_point_id' => $chargingPoint->id,
                    'error' => $result['error'] ?? 'Unknown'
                ]);

                return [
                    'success' => false,
                    'synced' => false,
                    'error' => $result['error'] ?? 'Unknown error',
                    'message' => 'Borne créée localement mais échec de synchronisation avec Steve: ' . ($result['error'] ?? 'Unknown')
                ];
            }

        } catch (Exception $e) {
            Log::error('ChargingPointSteveSyncService: Exception during sync to Steve', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            // Marquer comme échec de sync
            try {
                $chargingPoint->updateQuietly([
                    'steve_sync_status' => 'failed',
                    'steve_sync_error' => $e->getMessage()
                ]);
            } catch (Exception $updateException) {
                Log::error('Failed to update sync status', ['error' => $updateException->getMessage()]);
            }

            return [
                'success' => false,
                'synced' => false,
                'error' => $e->getMessage(),
                'message' => 'Exception lors de la synchronisation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Synchroniser la mise à jour d'une borne locale vers Steve API
     * 
     * @param ChargingPoint $chargingPoint
     * @return array
     */
    public function syncUpdateToSteve(ChargingPoint $chargingPoint): array
    {
        // Si la borne n'est pas synchronisée avec Steve, ne rien faire
        if (empty($chargingPoint->steve_charge_box_pk) && empty($chargingPoint->steve_charging_point_id)) {
            Log::info('ChargingPointSteveSyncService: Charging point not synced with Steve, skipping update sync', [
                'charging_point_id' => $chargingPoint->id
            ]);
            return ['success' => true, 'synced' => false, 'reason' => 'not_synced_with_steve'];
        }

        try {
            Log::info('ChargingPointSteveSyncService: Syncing update to Steve', [
                'charging_point_id' => $chargingPoint->id,
                'steve_pk' => $chargingPoint->steve_charge_box_pk
            ]);

            // Préparer les données pour l'API Steve
            $steveData = $this->mapChargingPointToSteveFormat($chargingPoint);

            // Mettre à jour sur l'API Steve
            $steveId = $chargingPoint->steve_charge_box_pk ?? $chargingPoint->steve_charging_point_id;
            $result = $this->steveService->updateChargePoint($steveId, $steveData);

            if ($result['success']) {
                $chargingPoint->updateQuietly([
                    'steve_synced_at' => now(),
                    'steve_sync_status' => 'synced',
                    'steve_sync_error' => null
                ]);

                Log::info('ChargingPointSteveSyncService: Successfully synced update to Steve', [
                    'charging_point_id' => $chargingPoint->id
                ]);

                return [
                    'success' => true,
                    'synced' => true,
                    'message' => 'Mise à jour synchronisée avec Steve'
                ];
            } else {
                $status = $result['status'] ?? null;
                $errorText = strtolower($result['error'] ?? '');
                $remoteMissing = in_array($status, [404, 410], true)
                    || ($status === 500 && (empty($errorText) || str_contains($errorText, 'not found') || str_contains($errorText, 'not exist')));

                if ($remoteMissing) {
                    $recreateResult = $this->attemptRecreateOnMissing($chargingPoint, $steveId, $result);
                    if ($recreateResult !== null) {
                        return $recreateResult;
                    }
                }

                $chargingPoint->updateQuietly([
                    'steve_sync_status' => 'out_of_sync',
                    'steve_sync_error' => $result['error'] ?? 'Unknown error'
                ]);

                return [
                    'success' => false,
                    'synced' => false,
                    'error' => $result['error'] ?? 'Unknown',
                    'message' => 'Échec de synchronisation avec Steve: ' . ($result['error'] ?? 'Unknown')
                ];
            }

        } catch (Exception $e) {
            Log::error('ChargingPointSteveSyncService: Exception during update sync', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'synced' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Synchroniser la suppression d'une borne locale vers Steve API
     * 
     * @param ChargingPoint $chargingPoint
     * @param bool $forceDelete Si true, supprimer même si sync échoue
     * @return array
     */
    public function syncDeleteToSteve(ChargingPoint $chargingPoint, bool $forceDelete = false): array
    {
        // Si la borne n'est pas synchronisée avec Steve, permettre la suppression locale
        if (empty($chargingPoint->steve_charge_box_pk) && empty($chargingPoint->steve_charging_point_id)) {
            Log::info('ChargingPointSteveSyncService: Charging point not synced with Steve, allowing local delete', [
                'charging_point_id' => $chargingPoint->id
            ]);
            return ['success' => true, 'synced' => false, 'reason' => 'not_synced_with_steve'];
        }

        try {
            Log::info('ChargingPointSteveSyncService: Syncing delete to Steve', [
                'charging_point_id' => $chargingPoint->id,
                'steve_pk' => $chargingPoint->steve_charge_box_pk,
                'force_delete' => $forceDelete
            ]);

            // Supprimer sur l'API Steve
            $steveId = $chargingPoint->steve_charge_box_pk ?? $chargingPoint->steve_charging_point_id;
            $result = $this->steveService->deleteChargePoint($steveId);

            if ($result['success']) {
                Log::info('ChargingPointSteveSyncService: Successfully deleted from Steve', [
                    'charging_point_id' => $chargingPoint->id
                ]);

                return [
                    'success' => true,
                    'synced' => true,
                    'message' => 'Borne supprimée de Steve et localement'
                ];
            } else {
                if ($forceDelete) {
                    Log::warning('ChargingPointSteveSyncService: Failed to delete from Steve but force delete enabled', [
                        'charging_point_id' => $chargingPoint->id,
                        'error' => $result['error'] ?? 'Unknown'
                    ]);

                    return [
                        'success' => true,
                        'synced' => false,
                        'warning' => 'Borne supprimée localement mais échec suppression Steve',
                        'steve_error' => $result['error'] ?? 'Unknown'
                    ];
                } else {
                    return [
                        'success' => false,
                        'synced' => false,
                        'error' => $result['error'] ?? 'Unknown',
                        'message' => 'Échec de suppression sur Steve. Utilisez force_delete pour supprimer uniquement en local.'
                    ];
                }
            }

        } catch (Exception $e) {
            Log::error('ChargingPointSteveSyncService: Exception during delete sync', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            if ($forceDelete) {
                return [
                    'success' => true,
                    'synced' => false,
                    'warning' => 'Exception lors de la suppression Steve mais force delete activé',
                    'steve_error' => $e->getMessage()
                ];
            } else {
                return [
                    'success' => false,
                    'synced' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
    }

    /**
     * Mapper les données d'une borne locale vers le format Steve API
     * 
     * @param ChargingPoint $chargingPoint
     * @return array
     */
    protected function mapChargingPointToSteveFormat(ChargingPoint $chargingPoint): array
    {
        // Utiliser serial_number ou un ID généré comme chargeBoxId
        $chargeBoxId = $chargingPoint->steve_charging_point_id 
            ?? $chargingPoint->serial_number 
            ?? 'CP-' . str_pad($chargingPoint->id, 6, '0', STR_PAD_LEFT);

        $data = [
            'chargeBoxId' => $chargeBoxId,
            'description' => $chargingPoint->name ?? $chargingPoint->description ?? 'Borne #' . $chargingPoint->id,
            'note' => $chargingPoint->notes ?? $chargingPoint->description ?? null,
        ];

        // Adresse
        if ($chargingPoint->address || $chargingPoint->city || $chargingPoint->postal_code) {
            $data['address'] = array_filter([
                'street' => $chargingPoint->address,
                'houseNumber' => $chargingPoint->house_number ?? null,
                'zipCode' => $chargingPoint->postal_code,
                'city' => $chargingPoint->city,
                'country' => $chargingPoint->country ?? 'UNDEFINED',
            ]);
        }

        // Coordonnées GPS
        if ($chargingPoint->latitude && $chargingPoint->longitude) {
            $data['locationLatitude'] = (float) $chargingPoint->latitude;
            $data['locationLongitude'] = (float) $chargingPoint->longitude;
        }

        // Adresse admin (peut être un email ou username)
        if ($chargingPoint->admin_email ?? $chargingPoint->contact_email) {
            $data['adminAddress'] = $chargingPoint->admin_email ?? $chargingPoint->contact_email;
        }

        // Statut d'enregistrement
        if ($chargingPoint->registration_status) {
            $data['registrationStatus'] = $chargingPoint->registration_status;
        }

        return $data;
    }

    /**
     * Vérifie sur Steve si le chargeBox existe encore et tente une recréation si nécessaire.
     */
    private function attemptRecreateOnMissing(ChargingPoint $chargingPoint, string $steveId = null, array $lastResult = []): ?array
    {
        $steveId = $steveId ?? $chargingPoint->steve_charging_point_id;

        Log::warning('ChargingPointSteveSyncService: Remote charge point might be missing, performing verification', [
            'charging_point_id' => $chargingPoint->id,
            'steve_id' => $steveId,
            'last_status' => $lastResult['status'] ?? null,
            'last_error' => $lastResult['error'] ?? null
        ]);

        $exists = $this->steveService->getChargePoint($steveId);

        if ($exists) {
            Log::info('ChargingPointSteveSyncService: Charge point found on Steve despite previous error, skipping auto-recreate', [
                'charging_point_id' => $chargingPoint->id,
                'steve_id' => $steveId
            ]);
            return null;
        }

        Log::warning('ChargingPointSteveSyncService: Charge point missing on Steve, auto-recreating', [
            'charging_point_id' => $chargingPoint->id,
            'steve_id' => $steveId
        ]);

        $recreateResult = $this->syncCreateToSteve($chargingPoint, true);
        $recreateResult['recreated_after_missing'] = true;
        return $recreateResult;
    }

    /**
     * Synchroniser toutes les bornes locales non synchronisées vers Steve
     * 
     * @param int $limit Nombre maximum de bornes à synchroniser
     * @return array
     */
    public function syncAllToSteve(int $limit = 100): array
    {
        try {
            // Trouver les bornes locales non synchronisées ou en échec de sync
            $chargingPoints = ChargingPoint::whereNull('steve_charging_point_id')
                ->orWhere('steve_sync_status', 'failed')
                ->orWhereNull('steve_sync_status')
                ->limit($limit)
                ->get();

            $results = [
                'total' => $chargingPoints->count(),
                'success' => 0,
                'failed' => 0,
                'skipped' => 0,
                'details' => []
            ];

            foreach ($chargingPoints as $chargingPoint) {
                $result = $this->syncCreateToSteve($chargingPoint, true);

                if ($result['success'] && $result['synced']) {
                    $results['success']++;
                } elseif ($result['success'] && !$result['synced']) {
                    $results['skipped']++;
                } else {
                    $results['failed']++;
                }

                $results['details'][] = [
                    'charging_point_id' => $chargingPoint->id,
                    'name' => $chargingPoint->name,
                    'result' => $result
                ];
            }

            Log::info('ChargingPointSteveSyncService: Batch sync completed', $results);

            return [
                'success' => true,
                'results' => $results
            ];

        } catch (Exception $e) {
            Log::error('ChargingPointSteveSyncService: Exception during batch sync', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Importer les bornes depuis Steve API vers la base de données locale
     * 
     * @param bool $updateExisting Si true, mettre à jour les bornes existantes
     * @return array
     */
    public function importFromSteve(bool $updateExisting = false): array
    {
        try {
            Log::info('ChargingPointSteveSyncService: Importing charging points from Steve');

            // Récupérer toutes les bornes de Steve
            $steveChargePoints = $this->steveService->getChargePoints();

            if ($steveChargePoints === null) {
                return [
                    'success' => false,
                    'error' => 'Failed to fetch charge points from Steve API',
                    'message' => 'Impossible de récupérer les bornes depuis Steve'
                ];
            }

            $results = [
                'total' => count($steveChargePoints),
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'details' => []
            ];

            foreach ($steveChargePoints as $steveCP) {
                try {
                    $chargeBoxId = $steveCP['chargeBoxId'] ?? null;
                    $chargeBoxPk = $steveCP['chargeBoxPk'] ?? null;

                    if (empty($chargeBoxId)) {
                        $results['skipped']++;
                        continue;
                    }

                    // Chercher si la borne existe déjà localement
                    $existing = ChargingPoint::where('steve_charging_point_id', $chargeBoxId)
                        ->orWhere('steve_charge_box_pk', $chargeBoxPk)
                        ->first();

                    if ($existing) {
                        if ($updateExisting) {
                            // Mettre à jour la borne existante
                            $this->updateLocalFromSteve($existing, $steveCP);
                            $results['updated']++;
                            $results['details'][] = [
                                'action' => 'updated',
                                'charging_point_id' => $existing->id,
                                'chargeBoxId' => $chargeBoxId
                            ];
                        } else {
                            $results['skipped']++;
                        }
                    } else {
                        // Créer une nouvelle borne locale
                        $newChargingPoint = $this->createLocalFromSteve($steveCP);
                        $results['created']++;
                        $results['details'][] = [
                            'action' => 'created',
                            'charging_point_id' => $newChargingPoint->id,
                            'chargeBoxId' => $chargeBoxId
                        ];
                    }

                } catch (Exception $e) {
                    $results['failed']++;
                    $results['details'][] = [
                        'action' => 'failed',
                        'chargeBoxId' => $steveCP['chargeBoxId'] ?? 'N/A',
                        'error' => $e->getMessage()
                    ];
                }
            }

            Log::info('ChargingPointSteveSyncService: Import from Steve completed', $results);

            return [
                'success' => true,
                'results' => $results
            ];

        } catch (Exception $e) {
            Log::error('ChargingPointSteveSyncService: Exception during import from Steve', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Créer une borne locale à partir de données Steve
     * 
     * @param array $steveData
     * @return ChargingPoint
     */
    protected function createLocalFromSteve(array $steveData): ChargingPoint
    {
        return ChargingPoint::create([
            'name' => $steveData['description'] ?? $steveData['chargeBoxId'],
            'description' => $steveData['description'] ?? null,
            'notes' => $steveData['note'] ?? null,
            'steve_charging_point_id' => $steveData['chargeBoxId'],
            'steve_charge_box_pk' => $steveData['chargeBoxPk'] ?? null,
            'address' => $steveData['street'] ?? null,
            'city' => $steveData['city'] ?? null,
            'postal_code' => $steveData['zipCode'] ?? null,
            'latitude' => $steveData['locationLatitude'] ?? null,
            'longitude' => $steveData['locationLongitude'] ?? null,
            'status' => 'available', // Par défaut
            'communication_protocol' => $steveData['ocppProtocol'] ?? 'OCPP 1.6',
            'steve_synced_at' => now(),
            'steve_sync_status' => 'synced'
        ]);
    }

    /**
     * Mettre à jour une borne locale depuis des données Steve
     * 
     * @param ChargingPoint $chargingPoint
     * @param array $steveData
     * @return void
     */
    protected function updateLocalFromSteve(ChargingPoint $chargingPoint, array $steveData): void
    {
        $updateData = [];

        if (!empty($steveData['description'])) {
            $updateData['description'] = $steveData['description'];
        }
        if (isset($steveData['street'])) {
            $updateData['address'] = $steveData['street'];
        }
        if (isset($steveData['city'])) {
            $updateData['city'] = $steveData['city'];
        }
        if (isset($steveData['zipCode'])) {
            $updateData['postal_code'] = $steveData['zipCode'];
        }
        if (isset($steveData['ocppProtocol'])) {
            $updateData['communication_protocol'] = $steveData['ocppProtocol'];
        }

        $updateData['steve_synced_at'] = now();
        $updateData['steve_sync_status'] = 'synced';

        $chargingPoint->updateQuietly($updateData);
    }

    /**
     * Vérifier le statut de synchronisation d'une borne
     * 
     * @param ChargingPoint $chargingPoint
     * @return array
     */
    public function checkSyncStatus(ChargingPoint $chargingPoint): array
    {
        $status = [
            'is_synced' => false,
            'steve_charging_point_id' => $chargingPoint->steve_charging_point_id,
            'steve_charge_box_pk' => $chargingPoint->steve_charge_box_pk,
            'last_synced_at' => $chargingPoint->steve_synced_at,
            'sync_status' => $chargingPoint->steve_sync_status,
            'sync_error' => $chargingPoint->steve_sync_error,
            'exists_on_steve' => false,
            'can_sync' => false
        ];

        // Vérifier si la borne est marquée comme synchronisée
        $status['is_synced'] = !empty($chargingPoint->steve_charging_point_id) 
            && $chargingPoint->steve_sync_status === 'synced';

        // Vérifier si la borne existe vraiment sur Steve
        if (!empty($chargingPoint->steve_charge_box_pk) || !empty($chargingPoint->steve_charging_point_id)) {
            try {
                $steveId = $chargingPoint->steve_charge_box_pk ?? $chargingPoint->steve_charging_point_id;
                $steveData = $this->steveService->getChargePoint($steveId);
                $status['exists_on_steve'] = $steveData !== null;
            } catch (Exception $e) {
                $status['exists_on_steve'] = false;
            }
        }

        // Déterminer si on peut synchroniser
        $status['can_sync'] = !empty($chargingPoint->latitude) && !empty($chargingPoint->longitude);

        return $status;
    }
}


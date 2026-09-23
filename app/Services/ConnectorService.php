<?php

namespace App\Services;

use App\DTO\Connector\ConnectorCollectionDTO;
use App\DTO\Connector\ConnectorDTO;
use App\DTO\Connector\ConnectorErrorDTO;
use App\DTO\Connector\ConnectorStatusDTO;
use App\Models\Connector;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Service pour la gestion des connecteurs de points de charge
 * 
 * Gère la récupération des statuts de connecteurs via l'API SteVe,
 * le mapping vers les DTOs, et la logique de sélection du connecteur par défaut.
 */
class ConnectorService
{
    /**
     * Durée de cache en secondes pour les statuts de connecteurs
     */
    protected const CACHE_TTL = 30;

    /**
     * Connecteur par défaut si non spécifié
     */
    protected const DEFAULT_CONNECTOR_ID = 1;

    public function __construct(
        protected SteVeHttpClientService $steveClient
    ) {}

    // =========================================================================
    // MÉTHODES PRINCIPALES
    // =========================================================================

    /**
     * Récupère les statuts de tous les connecteurs d'un point de charge
     */
    public function getConnectorStatuses(string $chargeBoxId, bool $useCache = true): ConnectorCollectionDTO
    {
        $cacheKey = "connector_statuses_{$chargeBoxId}";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            Log::debug('ConnectorService: Fetching connector statuses', [
                'chargeBoxId' => $chargeBoxId,
            ]);

            $response = $this->steveClient->getConnectorStatus($chargeBoxId);

            if (!$this->isSuccessfulResponse($response)) {
                $error = $response['error'] ?? $response['message'] ?? 'Erreur inconnue';
                Log::warning('ConnectorService: Failed to fetch connector statuses', [
                    'chargeBoxId' => $chargeBoxId,
                    'error' => $error,
                ]);

                $collection = ConnectorCollectionDTO::withError($chargeBoxId, $error);

                // Cache "not found" (404) results so we don't hammer Steve on every request
                // for a charge point that isn't registered. Use a 5-minute TTL.
                if ($useCache && str_contains($error, 'HTTP 404')) {
                    Cache::put($cacheKey, $collection, 300);
                }

                return $collection;
            }

            $collection = ConnectorCollectionDTO::fromApiResponse($response, $chargeBoxId);

            if ($useCache && $collection->success) {
                Cache::put($cacheKey, $collection, self::CACHE_TTL);
            }

            Log::debug('ConnectorService: Successfully fetched connector statuses', [
                'chargeBoxId' => $chargeBoxId,
                'count' => $collection->count(),
            ]);

            return $collection;

        } catch (Exception $e) {
            Log::error('ConnectorService: Exception while fetching connector statuses', [
                'chargeBoxId' => $chargeBoxId,
                'error' => $e->getMessage(),
            ]);

            return ConnectorCollectionDTO::withError(
                $chargeBoxId,
                'Erreur de communication avec SteVe: ' . $e->getMessage()
            );
        }
    }

    /**
     * Récupère le statut d'un connecteur spécifique
     */
    public function getConnectorStatus(string $chargeBoxId, int $connectorId): ConnectorStatusDTO|ConnectorErrorDTO
    {
        try {
            Log::debug('ConnectorService: Fetching single connector status', [
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connectorId,
            ]);

            $response = $this->steveClient->getConnectorStatus($chargeBoxId, $connectorId);

            if (!$this->isSuccessfulResponse($response)) {
                return $this->handleApiError($response, $chargeBoxId, $connectorId);
            }

            return $this->mapApiResponseToDTO($response, $chargeBoxId, $connectorId);

        } catch (Exception $e) {
            Log::error('ConnectorService: Exception while fetching connector status', [
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connectorId,
                'error' => $e->getMessage(),
            ]);

            return ConnectorErrorDTO::connectionError(
                'Erreur de communication: ' . $e->getMessage(),
                $chargeBoxId
            );
        }
    }

    /**
     * Obtient le connecteur par défaut (connector_id = 1)
     */
    public function getDefaultConnector(string $chargeBoxId): ConnectorStatusDTO|ConnectorErrorDTO
    {
        return $this->getConnectorStatus($chargeBoxId, self::DEFAULT_CONNECTOR_ID);
    }

    /**
     * Vérifie la disponibilité d'un connecteur
     */
    public function isConnectorAvailable(string $chargeBoxId, int $connectorId = self::DEFAULT_CONNECTOR_ID): bool
    {
        $status = $this->getConnectorStatus($chargeBoxId, $connectorId);

        if ($status instanceof ConnectorErrorDTO) {
            return false;
        }

        return $status->isAvailable();
    }

    /**
     * Vérifie si un connecteur peut démarrer une session
     */
    public function canStartSession(string $chargeBoxId, int $connectorId = self::DEFAULT_CONNECTOR_ID): array
    {
        $status = $this->getConnectorStatus($chargeBoxId, $connectorId);

        if ($status instanceof ConnectorErrorDTO) {
            return [
                'canStart' => false,
                'reason' => $status->getUserMessage(),
                'error' => $status,
            ];
        }

        $canStart = $status->canStartSession();

        return [
            'canStart' => $canStart,
            'reason' => $canStart ? 'Connecteur prêt' : 'Connecteur non disponible (statut: ' . $status->status . ')',
            'status' => $status,
        ];
    }

    /**
     * Obtient le premier connecteur disponible
     */
    public function getFirstAvailableConnector(string $chargeBoxId): ?ConnectorStatusDTO
    {
        $collection = $this->getConnectorStatuses($chargeBoxId);

        if (!$collection->success || $collection->isEmpty()) {
            return null;
        }

        return $collection->getFirstAvailableConnector();
    }

    /**
     * Obtient le meilleur connecteur pour démarrer une session
     * Logique: connecteur par défaut (1) s'il est disponible, sinon le premier disponible
     */
    public function getBestConnectorForSession(string $chargeBoxId): ?ConnectorStatusDTO
    {
        $collection = $this->getConnectorStatuses($chargeBoxId);

        if (!$collection->success || $collection->isEmpty()) {
            return null;
        }

        // Essayer d'abord le connecteur par défaut
        $defaultConnector = $collection->getDefaultConnector();
        if ($defaultConnector && $defaultConnector->canStartSession()) {
            return $defaultConnector;
        }

        // Sinon, prendre le premier disponible
        return $collection->getFirstAvailableConnector();
    }

    // =========================================================================
    // MÉTHODES DE SYNCHRONISATION AVEC LA BASE DE DONNÉES
    // =========================================================================

    /**
     * Synchronise les connecteurs d'un point de charge depuis SteVe vers la base de données
     */
    public function syncConnectorsFromSteve(ChargingPoint $chargingPoint): array
    {
        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (!$chargeBoxId) {
            return [
                'success' => false,
                'error' => 'Point de charge sans charge_box_id configuré',
                'synced' => 0,
            ];
        }

        $collection = $this->getConnectorStatuses($chargeBoxId, false);

        if (!$collection->success) {
            return [
                'success' => false,
                'error' => $collection->error,
                'synced' => 0,
            ];
        }

        $synced = 0;

        foreach ($collection->connectors as $connectorStatus) {
            $connector = Connector::updateOrCreate(
                [
                    'charging_point_id' => $chargingPoint->id,
                    'connector_id' => $connectorStatus->connectorId,
                ],
                [
                    'status' => $connectorStatus->status,
                    'type' => null, // Non disponible dans le status endpoint
                ]
            );

            if ($connector->wasRecentlyCreated || $connector->wasChanged()) {
                $synced++;
            }
        }

        Log::info('ConnectorService: Synced connectors from SteVe', [
            'chargingPointId' => $chargingPoint->id,
            'chargeBoxId' => $chargeBoxId,
            'synced' => $synced,
            'total' => $collection->count(),
        ]);

        return [
            'success' => true,
            'synced' => $synced,
            'total' => $collection->count(),
        ];
    }

    /**
     * Met à jour le statut d'un connecteur local depuis l'API
     */
    public function updateLocalConnectorStatus(Connector $connector): bool
    {
        $chargingPoint = $connector->chargingPoint;
        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (!$chargeBoxId) {
            return false;
        }

        $status = $this->getConnectorStatus($chargeBoxId, $connector->connector_id);

        if ($status instanceof ConnectorErrorDTO) {
            return false;
        }

        $connector->update([
            'status' => $status->status,
        ]);

        return true;
    }

    // =========================================================================
    // MÉTHODES DE CONTRÔLE DES CONNECTEURS
    // =========================================================================

    /**
     * Déverrouille un connecteur
     */
    public function unlockConnector(string $chargeBoxId, int $connectorId): array
    {
        try {
            $response = $this->steveClient->unlockConnector($chargeBoxId, $connectorId);

            return [
                'success' => $response['success'] ?? false,
                'message' => $response['message'] ?? 'Opération effectuée',
                'data' => $response['data'] ?? null,
            ];

        } catch (Exception $e) {
            Log::error('ConnectorService: Failed to unlock connector', [
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connectorId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du déverrouillage: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Change la disponibilité d'un connecteur
     */
    public function changeAvailability(string $chargeBoxId, int $connectorId, bool $operative): array
    {
        try {
            $type = $operative ? 'Operative' : 'Inoperative';
            $response = $this->steveClient->changeAvailability($chargeBoxId, $connectorId, $type);

            // Invalider le cache après changement
            $this->invalidateCache($chargeBoxId);

            return [
                'success' => $response['success'] ?? false,
                'message' => $response['message'] ?? 'Disponibilité modifiée',
                'data' => $response['data'] ?? null,
            ];

        } catch (Exception $e) {
            Log::error('ConnectorService: Failed to change availability', [
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connectorId,
                'operative' => $operative,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du changement de disponibilité: ' . $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    // MÉTHODES DE CACHE
    // =========================================================================

    /**
     * Invalide le cache des connecteurs pour un point de charge
     */
    public function invalidateCache(string $chargeBoxId): void
    {
        Cache::forget("connector_statuses_{$chargeBoxId}");
    }

    /**
     * Précharge le cache des connecteurs
     */
    public function warmCache(string $chargeBoxId): void
    {
        $this->getConnectorStatuses($chargeBoxId, false);
    }

    // =========================================================================
    // MÉTHODES PRIVÉES
    // =========================================================================

    /**
     * Vérifie si la réponse API est un succès
     */
    protected function isSuccessfulResponse(array $response): bool
    {
        return ($response['success'] ?? false) === true;
    }

    /**
     * Mappe une réponse API vers un ConnectorStatusDTO
     */
    protected function mapApiResponseToDTO(array $response, string $chargeBoxId, int $connectorId): ConnectorStatusDTO
    {
        $data = $response['data'] ?? $response;

        // Si la réponse contient directement les infos du connecteur
        if (isset($data['status'])) {
            return ConnectorStatusDTO::fromApiResponse($data, $chargeBoxId);
        }

        // Si la réponse contient une liste de connecteurs, chercher le bon
        $connectors = $data['connectors'] ?? $data;
        if (is_array($connectors)) {
            foreach ($connectors as $connectorData) {
                if (is_array($connectorData) && ($connectorData['connectorId'] ?? null) == $connectorId) {
                    return ConnectorStatusDTO::fromApiResponse($connectorData, $chargeBoxId);
                }
            }
        }

        // Connecteur non trouvé dans la réponse, créer un DTO par défaut
        return new ConnectorStatusDTO([
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
            'status' => ConnectorStatusDTO::STATUS_UNKNOWN,
        ]);
    }

    /**
     * Gère une erreur API et retourne un ConnectorErrorDTO
     */
    protected function handleApiError(array $response, string $chargeBoxId, int $connectorId): ConnectorErrorDTO
    {
        return ConnectorErrorDTO::fromApiError($response, $chargeBoxId, $connectorId);
    }

    /**
     * Crée un DTO à partir du modèle Connector local
     */
    public function createDTOFromModel(Connector $connector): ConnectorDTO
    {
        return ConnectorDTO::fromModel($connector);
    }

    /**
     * Récupère les connecteurs depuis la base de données locale
     * 
     * @return ConnectorDTO[]
     */
    public function getLocalConnectors(ChargingPoint $chargingPoint): array
    {
        return $chargingPoint->connectors
            ->map(fn($connector) => ConnectorDTO::fromModel($connector))
            ->all();
    }
}

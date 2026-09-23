<?php

namespace App\DTO\Connector;

use App\Core\DTOs\BaseDTO;
use Illuminate\Support\Collection;

/**
 * DTO pour représenter une collection de statuts de connecteurs
 * Facilite la gestion de plusieurs connecteurs d'un point de charge
 */
class ConnectorCollectionDTO extends BaseDTO
{
    public string $chargeBoxId;
    
    /** @var ConnectorStatusDTO[] */
    public array $connectors;
    
    public string $timestamp;
    public bool $success;
    public ?string $error;

    /**
     * Constructeur
     */
    public function __construct(array $data = [])
    {
        $this->chargeBoxId = $data['chargeBoxId'] ?? '';
        $this->connectors = $data['connectors'] ?? [];
        $this->timestamp = $data['timestamp'] ?? now()->toISOString();
        $this->success = $data['success'] ?? true;
        $this->error = $data['error'] ?? null;
    }

    /**
     * Crée une collection à partir de la réponse API SteVe
     */
    public static function fromApiResponse(array $response, string $chargeBoxId): self
    {
        $connectors = [];
        $rawConnectors = [];

        // Extraction des connecteurs selon le format de réponse
        // Format A: {success: true, data: {connectors: [...]}}
        if (isset($response['data']['connectors']) && is_array($response['data']['connectors'])) {
            $rawConnectors = $response['data']['connectors'];
        }
        // Format B: {connectors: [...]}
        elseif (isset($response['connectors']) && is_array($response['connectors'])) {
            $rawConnectors = $response['connectors'];
        }
        // Format C: Tableau direct [...]
        elseif (is_array($response) && !empty($response) && array_is_list($response)) {
            $rawConnectors = $response;
        }
        // Format D: {success: true, data: [...]}
        elseif (isset($response['data']) && is_array($response['data']) && array_is_list($response['data'])) {
            $rawConnectors = $response['data'];
        }

        // Conversion en DTOs
        foreach ($rawConnectors as $connectorData) {
            if (is_array($connectorData)) {
                $connectors[] = ConnectorStatusDTO::fromApiResponse($connectorData, $chargeBoxId);
            }
        }

        // Tri par connectorId
        usort($connectors, fn($a, $b) => $a->connectorId <=> $b->connectorId);

        return new self([
            'chargeBoxId' => $chargeBoxId,
            'connectors' => $connectors,
            'success' => !empty($connectors),
            'error' => empty($connectors) ? 'Aucun connecteur trouvé' : null,
        ]);
    }

    /**
     * Crée une collection vide avec erreur
     */
    public static function withError(string $chargeBoxId, string $error): self
    {
        return new self([
            'chargeBoxId' => $chargeBoxId,
            'connectors' => [],
            'success' => false,
            'error' => $error,
        ]);
    }

    /**
     * Obtient le nombre de connecteurs
     */
    public function count(): int
    {
        return count($this->connectors);
    }

    /**
     * Vérifie si la collection est vide
     */
    public function isEmpty(): bool
    {
        return empty($this->connectors);
    }

    /**
     * Obtient tous les connecteurs disponibles
     * 
     * @return ConnectorStatusDTO[]
     */
    public function getAvailableConnectors(): array
    {
        return array_filter($this->connectors, fn($c) => $c->isAvailable());
    }

    /**
     * Obtient le connecteur par défaut (connector_id = 1)
     */
    public function getDefaultConnector(): ?ConnectorStatusDTO
    {
        return $this->findByConnectorId(1);
    }

    /**
     * Obtient le premier connecteur disponible
     */
    public function getFirstAvailableConnector(): ?ConnectorStatusDTO
    {
        $available = $this->getAvailableConnectors();
        return !empty($available) ? reset($available) : null;
    }

    /**
     * Trouve un connecteur par son ID
     */
    public function findByConnectorId(int $connectorId): ?ConnectorStatusDTO
    {
        foreach ($this->connectors as $connector) {
            if ($connector->connectorId === $connectorId) {
                return $connector;
            }
        }
        return null;
    }

    /**
     * Obtient les connecteurs en charge
     * 
     * @return ConnectorStatusDTO[]
     */
    public function getChargingConnectors(): array
    {
        return array_filter($this->connectors, fn($c) => $c->isCharging());
    }

    /**
     * Obtient les connecteurs avec erreur
     * 
     * @return ConnectorStatusDTO[]
     */
    public function getFaultedConnectors(): array
    {
        return array_filter($this->connectors, fn($c) => $c->hasError());
    }

    /**
     * Vérifie si au moins un connecteur est disponible
     */
    public function hasAvailableConnector(): bool
    {
        return !empty($this->getAvailableConnectors());
    }

    /**
     * Obtient un résumé des statuts
     */
    public function getStatusSummary(): array
    {
        $summary = [
            'total' => $this->count(),
            'available' => 0,
            'charging' => 0,
            'reserved' => 0,
            'unavailable' => 0,
            'faulted' => 0,
            'other' => 0,
        ];

        foreach ($this->connectors as $connector) {
            if ($connector->isAvailable()) {
                $summary['available']++;
            } elseif ($connector->isCharging()) {
                $summary['charging']++;
            } elseif ($connector->isReserved()) {
                $summary['reserved']++;
            } elseif ($connector->isUnavailable()) {
                $summary['unavailable']++;
            } elseif ($connector->hasError()) {
                $summary['faulted']++;
            } else {
                $summary['other']++;
            }
        }

        return $summary;
    }

    /**
     * Convertit en Collection Laravel
     */
    public function toCollection(): Collection
    {
        return collect($this->connectors);
    }

    /**
     * Convertit en tableau pour réponse API
     */
    public function toApiResponse(): array
    {
        return [
            'success' => $this->success,
            'data' => [
                'chargeBoxId' => $this->chargeBoxId,
                'connectors' => array_map(fn($c) => $c->toArray(), $this->connectors),
                'summary' => $this->getStatusSummary(),
            ],
            'timestamp' => $this->timestamp,
            'error' => $this->error,
        ];
    }

    /**
     * Implémentation de toArray depuis BaseDTO
     */
    public function toArray(): array
    {
        return [
            'chargeBoxId' => $this->chargeBoxId,
            'connectors' => array_map(fn($c) => $c->toArray(), $this->connectors),
            'timestamp' => $this->timestamp,
            'success' => $this->success,
            'error' => $this->error,
        ];
    }
}

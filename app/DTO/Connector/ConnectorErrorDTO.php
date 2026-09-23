<?php

namespace App\DTO\Connector;

use App\Core\DTOs\BaseDTO;
use Carbon\Carbon;

/**
 * DTO pour représenter les erreurs liées aux connecteurs
 * Mappe les erreurs API SteVe de manière cohérente
 */
class ConnectorErrorDTO extends BaseDTO
{
    // Codes d'erreur OCPP standards
    public const ERROR_CONNECTOR_LOCK_FAILURE = 'ConnectorLockFailure';
    public const ERROR_EV_COMMUNICATION = 'EVCommunicationError';
    public const ERROR_GROUND_FAILURE = 'GroundFailure';
    public const ERROR_HIGH_TEMPERATURE = 'HighTemperature';
    public const ERROR_INTERNAL = 'InternalError';
    public const ERROR_LOCAL_LIST_CONFLICT = 'LocalListConflict';
    public const ERROR_NO_ERROR = 'NoError';
    public const ERROR_OTHER = 'OtherError';
    public const ERROR_OVER_CURRENT = 'OverCurrentFailure';
    public const ERROR_OVER_VOLTAGE = 'OverVoltage';
    public const ERROR_POWER_METER_FAILURE = 'PowerMeterFailure';
    public const ERROR_POWER_SWITCH_FAILURE = 'PowerSwitchFailure';
    public const ERROR_READER_FAILURE = 'ReaderFailure';
    public const ERROR_RESET_FAILURE = 'ResetFailure';
    public const ERROR_UNDER_VOLTAGE = 'UnderVoltage';
    public const ERROR_WEAK_SIGNAL = 'WeakSignal';

    // Codes d'erreur API
    public const API_ERROR_NOT_FOUND = 'CONNECTOR_NOT_FOUND';
    public const API_ERROR_UNAVAILABLE = 'CONNECTOR_UNAVAILABLE';
    public const API_ERROR_TIMEOUT = 'API_TIMEOUT';
    public const API_ERROR_CONNECTION = 'CONNECTION_ERROR';
    public const API_ERROR_AUTH = 'AUTH_ERROR';
    public const API_ERROR_UNKNOWN = 'UNKNOWN_ERROR';

    public string $code;
    public string $message;
    public ?array $details;
    public string $timestamp;
    public ?int $connectorId;
    public ?string $chargeBoxId;
    public ?int $httpStatus;

    /**
     * Constructeur
     */
    public function __construct(array $data = [])
    {
        $this->code = $data['code'] ?? self::API_ERROR_UNKNOWN;
        $this->message = $data['message'] ?? 'Une erreur inconnue est survenue';
        $this->details = $data['details'] ?? null;
        $this->timestamp = $data['timestamp'] ?? Carbon::now()->toISOString();
        $this->connectorId = isset($data['connectorId']) ? (int) $data['connectorId'] : null;
        $this->chargeBoxId = $data['chargeBoxId'] ?? null;
        $this->httpStatus = isset($data['httpStatus']) ? (int) $data['httpStatus'] : null;
    }

    /**
     * Crée un DTO à partir d'une réponse d'erreur API
     */
    public static function fromApiError(array $response, ?string $chargeBoxId = null, ?int $connectorId = null): self
    {
        $code = self::API_ERROR_UNKNOWN;
        $message = 'Une erreur est survenue lors de la communication avec SteVe';
        $httpStatus = null;

        // Détection du type d'erreur basée sur la réponse
        if (isset($response['error'])) {
            $error = $response['error'];
            
            if (str_contains(strtolower($error), 'not found')) {
                $code = self::API_ERROR_NOT_FOUND;
                $message = 'Connecteur non trouvé';
            } elseif (str_contains(strtolower($error), 'timeout')) {
                $code = self::API_ERROR_TIMEOUT;
                $message = 'Délai d\'attente dépassé';
            } elseif (str_contains(strtolower($error), 'connection') || str_contains(strtolower($error), 'connect')) {
                $code = self::API_ERROR_CONNECTION;
                $message = 'Erreur de connexion à SteVe';
            } elseif (str_contains(strtolower($error), 'auth') || str_contains(strtolower($error), '401') || str_contains(strtolower($error), '403')) {
                $code = self::API_ERROR_AUTH;
                $message = 'Erreur d\'authentification';
            } else {
                $message = $error;
            }
        }

        // Extraction du code HTTP si disponible
        if (isset($response['status'])) {
            $httpStatus = (int) $response['status'];
        } elseif (preg_match('/HTTP (\d{3})/', $response['error'] ?? '', $matches)) {
            $httpStatus = (int) $matches[1];
        }

        return new self([
            'code' => $code,
            'message' => $message,
            'details' => $response,
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
            'httpStatus' => $httpStatus,
        ]);
    }

    /**
     * Crée un DTO pour une erreur de connecteur non trouvé
     */
    public static function notFound(string $chargeBoxId, int $connectorId): self
    {
        return new self([
            'code' => self::API_ERROR_NOT_FOUND,
            'message' => "Connecteur {$connectorId} non trouvé pour la borne {$chargeBoxId}",
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
        ]);
    }

    /**
     * Crée un DTO pour une erreur de connecteur indisponible
     */
    public static function unavailable(string $chargeBoxId, int $connectorId, string $status): self
    {
        return new self([
            'code' => self::API_ERROR_UNAVAILABLE,
            'message' => "Connecteur {$connectorId} indisponible (statut: {$status})",
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
            'details' => ['status' => $status],
        ]);
    }

    /**
     * Crée un DTO pour une erreur de timeout
     */
    public static function timeout(string $chargeBoxId, ?int $connectorId = null): self
    {
        return new self([
            'code' => self::API_ERROR_TIMEOUT,
            'message' => 'Délai d\'attente dépassé lors de la communication avec SteVe',
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
        ]);
    }

    /**
     * Crée un DTO pour une erreur de connexion
     */
    public static function connectionError(string $message, ?string $chargeBoxId = null): self
    {
        return new self([
            'code' => self::API_ERROR_CONNECTION,
            'message' => $message,
            'chargeBoxId' => $chargeBoxId,
        ]);
    }

    /**
     * Vérifie si c'est une erreur récupérable (retry possible)
     */
    public function isRetryable(): bool
    {
        return in_array($this->code, [
            self::API_ERROR_TIMEOUT,
            self::API_ERROR_CONNECTION,
        ]);
    }

    /**
     * Vérifie si c'est une erreur client (4xx)
     */
    public function isClientError(): bool
    {
        return $this->httpStatus !== null && $this->httpStatus >= 400 && $this->httpStatus < 500;
    }

    /**
     * Vérifie si c'est une erreur serveur (5xx)
     */
    public function isServerError(): bool
    {
        return $this->httpStatus !== null && $this->httpStatus >= 500;
    }

    /**
     * Obtient le message d'erreur pour l'utilisateur
     */
    public function getUserMessage(): string
    {
        $userMessages = [
            self::API_ERROR_NOT_FOUND => 'Le connecteur demandé n\'existe pas.',
            self::API_ERROR_UNAVAILABLE => 'Ce connecteur n\'est pas disponible actuellement.',
            self::API_ERROR_TIMEOUT => 'La borne ne répond pas. Veuillez réessayer.',
            self::API_ERROR_CONNECTION => 'Impossible de contacter la borne. Vérifiez la connexion.',
            self::API_ERROR_AUTH => 'Erreur d\'authentification avec le serveur.',
            self::API_ERROR_UNKNOWN => 'Une erreur inattendue s\'est produite.',
        ];

        return $userMessages[$this->code] ?? $this->message;
    }

    /**
     * Convertit en tableau pour réponse API
     */
    public function toApiResponse(): array
    {
        return [
            'success' => false,
            'error' => [
                'code' => $this->code,
                'message' => $this->getUserMessage(),
                'timestamp' => $this->timestamp,
            ],
        ];
    }
}

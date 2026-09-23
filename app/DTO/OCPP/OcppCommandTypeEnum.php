<?php

namespace App\DTO\OCPP;

/**
 * Enum pour les types de commande OCPP
 */
enum OcppCommandTypeEnum: string
{
    case RESET = 'Reset';
    case LOCK_CONNECTOR = 'LockConnector';
    case UNLOCK_CONNECTOR = 'UnlockConnector';
    case REMOTE_START = 'RemoteStart';
    case REMOTE_STOP = 'RemoteStop';
    case CHANGE_AVAILABILITY = 'ChangeAvailability';
    case CLEAR_CACHE = 'ClearCache';
    case CHANGE_CONFIGURATION = 'ChangeConfiguration';
    case GET_CONFIGURATION = 'GetConfiguration';
    case GET_DIAGNOSTICS = 'GetDiagnostics';
    case UPDATE_FIRMWARE = 'UpdateFirmware';
    case GET_LOGS = 'GetLogs';

    /**
     * Obtient le libellé en français
     */
    public function label(): string
    {
        return match($this) {
            self::RESET => 'Réinitialisation',
            self::LOCK_CONNECTOR => 'Verrouillage connecteur',
            self::UNLOCK_CONNECTOR => 'Déverrouillage connecteur',
            self::REMOTE_START => 'Démarrage à distance',
            self::REMOTE_STOP => 'Arrêt à distance',
            self::CHANGE_AVAILABILITY => 'Changement disponibilité',
            self::CLEAR_CACHE => 'Vidage cache',
            self::CHANGE_CONFIGURATION => 'Modification configuration',
            self::GET_CONFIGURATION => 'Récupération configuration',
            self::GET_DIAGNOSTICS => 'Diagnostics',
            self::UPDATE_FIRMWARE => 'Mise à jour firmware',
            self::GET_LOGS => 'Récupération logs',
        };
    }

    /**
     * Obtient l'endpoint API correspondant
     */
    public function endpoint(): string
    {
        return match($this) {
            self::RESET => '/api/v1/operations/Reset',
            self::LOCK_CONNECTOR => '/api/v1/operations/ChangeAvailability',
            self::UNLOCK_CONNECTOR => '/api/v1/operations/UnlockConnector',
            self::REMOTE_START => '/api/v1/operations/RemoteStartTransaction',
            self::REMOTE_STOP => '/api/v1/operations/RemoteStopTransaction',
            self::CHANGE_AVAILABILITY => '/api/v1/operations/ChangeAvailability',
            self::CLEAR_CACHE => '/api/v1/operations/ClearCache',
            self::CHANGE_CONFIGURATION => '/api/v1/operations/ChangeConfiguration',
            self::GET_CONFIGURATION => '/api/v1/operations/GetConfiguration',
            self::GET_DIAGNOSTICS => '/api/v1/operations/GetDiagnostics',
            self::UPDATE_FIRMWARE => '/api/v1/operations/UpdateFirmware',
            self::GET_LOGS => '/api/v1/operations/GetLog',
        };
    }

    /**
     * Vérifie si la commande nécessite un connectorId
     */
    public function requiresConnectorId(): bool
    {
        return in_array($this, [
            self::LOCK_CONNECTOR,
            self::UNLOCK_CONNECTOR,
            self::CHANGE_AVAILABILITY,
        ]);
    }

    /**
     * Vérifie si la commande nécessite un idTag (OCPP tag)
     */
    public function requiresIdTag(): bool
    {
        return in_array($this, [
            self::REMOTE_START,
        ]);
    }
}

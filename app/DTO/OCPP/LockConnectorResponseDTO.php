<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use App\Http\Responses\ApiEnvelope;
use Carbon\Carbon;

/**
 * DTO pour la réponse Lock Connector OCPP
 */
class LockConnectorResponseDTO extends BaseDTO
{
    public bool $success;
    public string $message;
    public ?string $chargeBoxId;
    public ?int $connectorId;
    public ?ConnectorLockStatusEnum $status;
    public ?int $taskId;
    public string $timestamp;
    public ?array $rawResponse;

    public function __construct(array $data = [])
    {
        $this->success = $data['success'] ?? false;
        $this->message = $data['message'] ?? '';
        $this->chargeBoxId = $data['chargeBoxId'] ?? null;
        $this->connectorId = isset($data['connectorId']) ? (int) $data['connectorId'] : null;
        $this->taskId = isset($data['taskId']) ? (int) $data['taskId'] : null;
        $this->timestamp = $data['timestamp'] ?? Carbon::now()->toISOString();
        $this->rawResponse = $data['rawResponse'] ?? null;

        if (isset($data['status'])) {
            if ($data['status'] instanceof ConnectorLockStatusEnum) {
                $this->status = $data['status'];
            } elseif (is_string($data['status'])) {
                $this->status = ConnectorLockStatusEnum::fromString($data['status']);
            } else {
                $this->status = null;
            }
        } else {
            $this->status = null;
        }
    }

    /**
     * Crée une réponse de succès
     */
    public static function success(string $chargeBoxId, int $connectorId, ?array $rawResponse = null): self
    {
        return new self([
            'success' => true,
            'message' => 'Connecteur verrouillé avec succès. Les nouvelles sessions sont bloquées.',
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
            'status' => ConnectorLockStatusEnum::LOCKED,
            'rawResponse' => $rawResponse,
        ]);
    }

    /**
     * Crée une réponse d'erreur
     */
    public static function error(string $message, ?string $chargeBoxId = null, ?int $connectorId = null, ?array $rawResponse = null): self
    {
        return new self([
            'success' => false,
            'message' => $message,
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
            'status' => ConnectorLockStatusEnum::UNLOCKED,
            'rawResponse' => $rawResponse,
        ]);
    }

    /**
     * Crée une réponse depuis la réponse API SteVe.
     * Lock-connector is implemented as ChangeAvailability(Inoperative); SteVe
     * 3.9.0 returns a bare task ID on success.
     */
    public static function fromApiResponse(array $response, LockConnectorRequestDTO $request): self
    {
        $httpSuccess = (bool) ($response['success'] ?? false);
        $data        = $response['data'] ?? [];
        $taskId      = is_array($data) ? ($data['taskId'] ?? null) : null;
        $operationStatus = self::firstSteveOperationResponse($response);
        $accepted = $operationStatus !== null
            ? in_array(strtolower($operationStatus), ['accepted', 'scheduled'], true)
            : $httpSuccess;

        $message = self::firstSteveOperationError($response)
            ?? $response['message']
            ?? ($accepted
                ? ($taskId !== null
                    ? "Lock queued by SteVe (task #{$taskId})."
                    : 'Connecteur verrouillé')
                : 'Erreur lors du verrouillage');

        return new self([
            'success'     => $httpSuccess && $accepted,
            'message'     => $message,
            'chargeBoxId' => $request->chargeBoxId,
            'connectorId' => $request->connectorId,
            'status'      => $accepted ? ConnectorLockStatusEnum::LOCKED : ConnectorLockStatusEnum::UNLOCKED,
            'taskId'      => $taskId,
            'rawResponse' => $response,
        ]);
    }

    private static function firstSteveOperationResponse(array $response): ?string
    {
        $data = $response['data'] ?? $response;
        $first = $data['successResponses'][0]['response'] ?? null;

        return is_scalar($first) ? (string) $first : null;
    }

    private static function firstSteveOperationError(array $response): ?string
    {
        $data = $response['data'] ?? $response;

        return $data['errorResponses'][0]['errorDescription']
            ?? $data['exceptions'][0]['exceptionMessage']
            ?? null;
    }

    /**
     * Vérifie si le connecteur est verrouillé
     */
    public function isLocked(): bool
    {
        return $this->success && $this->status?->isLocked();
    }

    /**
     * P3: canonical envelope. chargeBoxId/connectorId in data; lock status in meta.
     */
    public function toApiResponse(): array
    {
        $data = [
            'chargeBoxId' => $this->chargeBoxId,
            'connectorId' => $this->connectorId,
        ];
        $meta = [
            'status'      => $this->status?->value,
            'statusLabel' => $this->status?->label(),
            'taskId'      => $this->taskId,
        ];

        if ($this->success) {
            return ApiEnvelope::ok($this->message, $data, $meta, $this->timestamp);
        }

        return ApiEnvelope::error($this->message, 'lock_failed', $data, $meta, $this->timestamp);
    }

    /**
     * Convertit en tableau
     */
    public function toArray(): array
    {
        return [
            'success'     => $this->success,
            'message'     => $this->message,
            'chargeBoxId' => $this->chargeBoxId,
            'connectorId' => $this->connectorId,
            'status'      => $this->status?->value,
            'taskId'      => $this->taskId,
            'timestamp'   => $this->timestamp,
        ];
    }
}

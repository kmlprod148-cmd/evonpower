<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use App\Http\Responses\ApiEnvelope;
use Carbon\Carbon;

/**
 * DTO for the UnlockConnector OCPP response.
 */
class UnlockConnectorResponseDTO extends BaseDTO
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

    public static function success(string $chargeBoxId, int $connectorId, ?array $rawResponse = null): self
    {
        return new self([
            'success' => true,
            'message' => 'Connector unlocked successfully.',
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
            'status' => ConnectorLockStatusEnum::UNLOCKED,
            'rawResponse' => $rawResponse,
        ]);
    }

    public static function error(string $message, ?string $chargeBoxId = null, ?int $connectorId = null, ?array $rawResponse = null): self
    {
        return new self([
            'success' => false,
            'message' => $message,
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
            'status' => ConnectorLockStatusEnum::LOCKED,
            'rawResponse' => $rawResponse,
        ]);
    }

    public static function fromApiResponse(array $response, UnlockConnectorRequestDTO $request): self
    {
        $httpSuccess = (bool) ($response['success'] ?? false);
        $data = $response['data'] ?? [];

        // SteVe 3.9.0: bare integer (taskId) on success. Legacy: bulk envelope
        // with successResponses[]/errorResponses[] containing 'Unlocked' string.
        $taskId = is_array($data) ? ($data['taskId'] ?? null) : null;
        $operationStatus = self::firstSteveOperationResponse($response);
        $unlocked = $operationStatus !== null
            ? strcasecmp($operationStatus, 'Unlocked') === 0
            : $httpSuccess;

        $message = self::firstSteveOperationError($response)
            ?? $response['message']
            ?? ($unlocked
                ? ($taskId !== null
                    ? "Unlock queued by SteVe (task #{$taskId})."
                    : 'Connector unlocked by SteVe')
                : 'Connector unlock rejected by SteVe');

        return new self([
            'success' => $httpSuccess && $unlocked,
            'message' => $message,
            'chargeBoxId' => $request->chargeBoxId,
            'connectorId' => $request->connectorId,
            'status' => $unlocked ? ConnectorLockStatusEnum::UNLOCKED : ConnectorLockStatusEnum::LOCKED,
            'taskId' => $taskId,
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

    public function isUnlocked(): bool
    {
        return $this->success && $this->status?->isUnlocked();
    }

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

        return ApiEnvelope::error($this->message, 'unlock_failed', $data, $meta, $this->timestamp);
    }

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

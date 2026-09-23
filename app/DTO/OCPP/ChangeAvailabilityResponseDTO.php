<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use App\Http\Responses\ApiEnvelope;
use Carbon\Carbon;

/**
 * DTO for the ChangeAvailability OCPP response.
 */
class ChangeAvailabilityResponseDTO extends BaseDTO
{
    public bool $success;
    public ?AvailabilityStatusEnum $status;
    public string $message;
    public ?string $chargeBoxId;
    public ?int $connectorId;
    public ?AvailabilityTypeEnum $requestedType;
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
            if ($data['status'] instanceof AvailabilityStatusEnum) {
                $this->status = $data['status'];
            } elseif (is_string($data['status'])) {
                $this->status = AvailabilityStatusEnum::fromString($data['status']);
            } else {
                $this->status = null;
            }
        } else {
            $this->status = null;
        }

        if (isset($data['requestedType'])) {
            if ($data['requestedType'] instanceof AvailabilityTypeEnum) {
                $this->requestedType = $data['requestedType'];
            } elseif (is_string($data['requestedType'])) {
                $this->requestedType = AvailabilityTypeEnum::fromString($data['requestedType']);
            } else {
                $this->requestedType = null;
            }
        } else {
            $this->requestedType = null;
        }
    }

    public static function success(
        AvailabilityStatusEnum $status,
        ChangeAvailabilityRequestDTO $request,
        ?array $rawResponse = null
    ): self {
        return new self([
            'success' => true,
            'status' => $status,
            'message' => $status->userMessage(),
            'chargeBoxId' => $request->chargeBoxId,
            'connectorId' => $request->connectorId,
            'requestedType' => $request->type,
            'rawResponse' => $rawResponse,
        ]);
    }

    public static function error(
        string $message,
        ?ChangeAvailabilityRequestDTO $request = null,
        ?array $rawResponse = null
    ): self {
        return new self([
            'success' => false,
            'status' => AvailabilityStatusEnum::REJECTED,
            'message' => $message,
            'chargeBoxId' => $request?->chargeBoxId,
            'connectorId' => $request?->connectorId,
            'requestedType' => $request?->type,
            'rawResponse' => $rawResponse,
        ]);
    }

    public static function fromApiResponse(
        array $response,
        ChangeAvailabilityRequestDTO $request
    ): self {
        $httpSuccess = (bool) ($response['success'] ?? false);
        $data = $response['data'] ?? $response;
        $operationStatus = self::firstSteveOperationResponse($response);

        // SteVe 3.9.0: bare integer task ID — treat as Accepted (queued).
        // Legacy: status string in body or in successResponses[0].response.
        $taskId = is_array($data) ? ($data['taskId'] ?? null) : null;

        $statusString = $operationStatus
            ?? ($data['status'] ?? null)
            ?? ($data['availability'] ?? null)
            ?? ($data['result'] ?? null)
            ?? ($taskId !== null ? 'Accepted' : ($httpSuccess ? 'Accepted' : 'Rejected'));

        $status = AvailabilityStatusEnum::fromString((string) $statusString);
        $message = self::firstSteveOperationError($response)
            ?? $response['message']
            ?? ($taskId !== null && ($status?->isSuccess() ?? false)
                ? "Availability change queued by SteVe (task #{$taskId})."
                : $status?->userMessage())
            ?? 'Unknown SteVe response';

        return new self([
            'success' => $httpSuccess && ($status?->isSuccess() ?? false),
            'status' => $status,
            'message' => $message,
            'chargeBoxId' => $request->chargeBoxId,
            'connectorId' => $request->connectorId,
            'requestedType' => $request->type,
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

    public function isAccepted(): bool
    {
        return $this->status?->isAccepted() ?? false;
    }

    public function isScheduled(): bool
    {
        return $this->status?->isScheduled() ?? false;
    }

    public function isRejected(): bool
    {
        return $this->status?->isRejected() ?? false;
    }

    public function toApiResponse(): array
    {
        $data = [
            'chargeBoxId' => $this->chargeBoxId,
            'connectorId' => $this->connectorId,
        ];
        $meta = [
            'status'             => $this->status?->value,
            'statusLabel'        => $this->status?->label(),
            'requestedType'      => $this->requestedType?->value,
            'requestedTypeLabel' => $this->requestedType?->label(),
            'taskId'             => $this->taskId,
        ];

        if ($this->success) {
            return ApiEnvelope::ok($this->message, $data, $meta, $this->timestamp);
        }

        return ApiEnvelope::error($this->message, 'availability_change_failed', $data, $meta, $this->timestamp);
    }

    public function toArray(): array
    {
        return [
            'success'       => $this->success,
            'status'        => $this->status?->value,
            'statusLabel'   => $this->status?->label(),
            'message'       => $this->message,
            'chargeBoxId'   => $this->chargeBoxId,
            'connectorId'   => $this->connectorId,
            'requestedType' => $this->requestedType?->value,
            'taskId'        => $this->taskId,
            'timestamp'     => $this->timestamp,
        ];
    }
}

<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use App\Http\Responses\ApiEnvelope;
use Carbon\Carbon;

/**
 * DTO for the Reset OCPP response.
 */
class ResetResponseDTO extends BaseDTO
{
    public bool $success;
    public string $message;
    public ?string $chargeBoxId;
    public ?ResetTypeEnum $requestedType;
    public ?int $taskId;
    public string $timestamp;
    public ?array $rawResponse;

    public function __construct(array $data = [])
    {
        $this->success = $data['success'] ?? false;
        $this->message = $data['message'] ?? '';
        $this->chargeBoxId = $data['chargeBoxId'] ?? null;
        $this->taskId = isset($data['taskId']) ? (int) $data['taskId'] : null;
        $this->timestamp = $data['timestamp'] ?? Carbon::now()->toISOString();
        $this->rawResponse = $data['rawResponse'] ?? null;

        if (isset($data['requestedType'])) {
            if ($data['requestedType'] instanceof ResetTypeEnum) {
                $this->requestedType = $data['requestedType'];
            } elseif (is_string($data['requestedType'])) {
                $this->requestedType = ResetTypeEnum::fromString($data['requestedType']);
            } else {
                $this->requestedType = null;
            }
        } else {
            $this->requestedType = null;
        }
    }

    public static function success(ResetTypeEnum $type, string $chargeBoxId, ?array $rawResponse = null): self
    {
        $message = $type->isHard()
            ? 'Hard reset accepted. Active sessions may be interrupted.'
            : 'Soft reset accepted. The charger should restart gracefully.';

        return new self([
            'success' => true,
            'message' => $message,
            'chargeBoxId' => $chargeBoxId,
            'requestedType' => $type,
            'rawResponse' => $rawResponse,
        ]);
    }

    public static function error(string $message, ?string $chargeBoxId = null, ?array $rawResponse = null): self
    {
        return new self([
            'success' => false,
            'message' => $message,
            'chargeBoxId' => $chargeBoxId,
            'rawResponse' => $rawResponse,
        ]);
    }

    public static function fromApiResponse(array $response, ResetRequestDTO $request): self
    {
        $httpSuccess = (bool) ($response['success'] ?? false);
        $data = $response['data'] ?? [];

        // SteVe 3.9.0 REST returns a bare integer (task ID) on success.
        // Older / legacy SteVe builds returned a bulk-response envelope with
        // successResponses[] / errorResponses[] — we still accept that shape
        // so callers stubbed against the old contract keep passing.
        $taskId = is_array($data) ? ($data['taskId'] ?? null) : null;
        $operationStatus = self::firstSteveOperationResponse($response);
        $accepted = $operationStatus !== null
            ? strcasecmp($operationStatus, 'Accepted') === 0
            : $httpSuccess;

        $message = self::firstSteveOperationError($response)
            ?? $response['message']
            ?? ($accepted
                ? ($taskId !== null
                    ? "Reset queued by SteVe (task #{$taskId})."
                    : 'Reset accepted by SteVe')
                : 'Reset rejected by SteVe');

        return new self([
            'success' => $httpSuccess && $accepted,
            'message' => $message,
            'chargeBoxId' => $request->chargeBoxId,
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

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function toApiResponse(): array
    {
        $data = ['chargeBoxId' => $this->chargeBoxId];
        $meta = [
            'type'      => $this->requestedType?->value,
            'typeLabel' => $this->requestedType?->label(),
            'taskId'    => $this->taskId,
        ];

        if ($this->success) {
            return ApiEnvelope::ok($this->message, $data, $meta, $this->timestamp);
        }

        return ApiEnvelope::error($this->message, 'reset_failed', $data, $meta, $this->timestamp);
    }

    public function toArray(): array
    {
        return [
            'success'     => $this->success,
            'message'     => $this->message,
            'chargeBoxId' => $this->chargeBoxId,
            'type'        => $this->requestedType?->value,
            'taskId'      => $this->taskId,
            'timestamp'   => $this->timestamp,
        ];
    }
}

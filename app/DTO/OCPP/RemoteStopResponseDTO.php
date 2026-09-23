<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use App\Http\Responses\ApiEnvelope;
use Carbon\Carbon;

/**
 * DTO for the RemoteStopTransaction OCPP response.
 */
class RemoteStopResponseDTO extends BaseDTO
{
    public bool $success;
    public string $message;
    public ?RemoteStopStatusEnum $status;
    public ?int $transactionId;
    public string $timestamp;
    public ?array $rawResponse;
    /**
     * Structured error code for rejected outcomes. The controller maps this
     * to an HTTP status: `session_already_terminated` → 409 Conflict, anything
     * else → 400 Bad Request. Null on accepted outcomes.
     */
    public ?string $errorCode;

    public function __construct(array $data = [])
    {
        $this->success = (bool) ($data['success'] ?? false);
        $this->message = (string) ($data['message'] ?? '');
        $this->transactionId = isset($data['transactionId']) ? (int) $data['transactionId'] : null;
        $this->timestamp = $data['timestamp'] ?? Carbon::now()->toISOString();
        $this->rawResponse = $data['rawResponse'] ?? null;
        $this->errorCode = $data['errorCode'] ?? null;

        if (isset($data['status'])) {
            if ($data['status'] instanceof RemoteStopStatusEnum) {
                $this->status = $data['status'];
            } elseif (is_string($data['status'])) {
                $this->status = RemoteStopStatusEnum::fromString($data['status']);
            } else {
                $this->status = null;
            }
        } else {
            $this->status = null;
        }
    }

    public static function accepted(?int $transactionId = null, ?array $rawResponse = null): self
    {
        return new self([
            'success' => true,
            'message' => 'Remote stop accepted. The charging session should end shortly.',
            'status' => RemoteStopStatusEnum::ACCEPTED,
            'transactionId' => $transactionId,
            'rawResponse' => $rawResponse,
        ]);
    }

    public static function rejected(string $message, ?array $rawResponse = null, ?string $errorCode = null): self
    {
        return new self([
            'success' => false,
            'message' => $message,
            'status' => RemoteStopStatusEnum::REJECTED,
            'rawResponse' => $rawResponse,
            'errorCode' => $errorCode,
        ]);
    }

    public static function conflict(string $message, ?array $rawResponse = null): self
    {
        return self::rejected($message, $rawResponse, 'session_already_terminated');
    }

    public static function fromApiResponse(array $response, RemoteStopRequestDTO $request): self
    {
        $success = (bool) ($response['success'] ?? false);
        $operationStatus = self::firstSteveOperationResponse($response);
        $statusValue = $operationStatus
            ?? $response['status']
            ?? $response['data']['status']
            ?? ($success ? 'ACCEPTED' : 'REJECTED');
        $status = RemoteStopStatusEnum::fromString((string) $statusValue);

        $message = self::firstSteveOperationError($response)
            ?? $response['message']
            ?? $response['data']['message']
            ?? $status?->userMessage()
            ?? 'Unknown SteVe response';
        $errorCode = $success ? null : self::firstErrorCode($response);

        // Canonical SteVe 3.9.0 puts the transaction id at data.transaction.id;
        // older / legacy shapes used data.transactionId. Fall through both, then
        // back to the request value (which we plumbed for audit precision).
        $transactionId = $response['data']['transaction']['id']
            ?? $response['data']['transactionId']
            ?? $response['data']['transaction_id']
            ?? $response['transactionId']
            ?? $request->transactionId;

        return new self([
            'success' => $success && $status?->isAccepted(),
            'message' => $message,
            'status' => $status,
            'transactionId' => $transactionId !== null ? (int) $transactionId : null,
            'rawResponse' => $response,
            'errorCode' => $errorCode,
        ]);
    }

    private static function firstErrorCode(array $response): ?string
    {
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $code = $response['error_code']
            ?? $response['errorCode']
            ?? $data['error_code']
            ?? $data['errorCode']
            ?? null;

        if (is_scalar($code)) {
            return (string) $code;
        }

        return null;
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
        return $this->status === RemoteStopStatusEnum::ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === RemoteStopStatusEnum::REJECTED;
    }

    public function toApiResponse(): array
    {
        $meta = [
            'status' => $this->status?->value,
            'statusLabel' => $this->status?->label(),
        ];

        $data = $this->transactionId !== null ? ['transactionId' => $this->transactionId] : null;

        if ($this->success) {
            return ApiEnvelope::ok($this->message, $data, $meta, $this->timestamp);
        }

        return ApiEnvelope::error($this->message, $this->errorCode ?? 'remote_stop_rejected', $data, $meta, $this->timestamp);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'status' => $this->status?->value,
            'transactionId' => $this->transactionId,
            'timestamp' => $this->timestamp,
        ];
    }
}

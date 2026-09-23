<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use App\Http\Responses\ApiEnvelope;
use Carbon\Carbon;

/**
 * DTO for the RemoteStartTransaction OCPP response.
 */
class RemoteStartResponseDTO extends BaseDTO
{
    public bool $success;
    public string $message;
    public ?RemoteStartStatusEnum $status;
    public ?array $transaction;
    public string $timestamp;
    public ?array $rawResponse;
    /**
     * Structured error code for rejected outcomes. The controller maps this
     * to an HTTP status: `session_already_active` → 409 Conflict, anything
     * else → 400 Bad Request. Null on accepted outcomes.
     */
    public ?string $errorCode;

    public function __construct(array $data = [])
    {
        $this->success = $data['success'] ?? false;
        $this->message = $data['message'] ?? '';
        $this->transaction = $data['transaction'] ?? null;
        $this->timestamp = $data['timestamp'] ?? Carbon::now()->toISOString();
        $this->rawResponse = $data['rawResponse'] ?? null;
        $this->errorCode = $data['errorCode'] ?? null;

        if (isset($data['status'])) {
            if ($data['status'] instanceof RemoteStartStatusEnum) {
                $this->status = $data['status'];
            } elseif (is_string($data['status'])) {
                $this->status = RemoteStartStatusEnum::fromString($data['status']);
            } else {
                $this->status = null;
            }
        } else {
            $this->status = null;
        }
    }

    public static function accepted(array $transaction = null, ?array $rawResponse = null): self
    {
        return new self([
            'success' => true,
            'message' => 'Remote start accepted. The charging session should begin shortly.',
            'status' => RemoteStartStatusEnum::ACCEPTED,
            'transaction' => $transaction,
            'rawResponse' => $rawResponse,
        ]);
    }

    public static function rejected(string $message, ?array $rawResponse = null, ?string $errorCode = null): self
    {
        return new self([
            'success' => false,
            'message' => $message,
            'status' => RemoteStartStatusEnum::REJECTED,
            'transaction' => null,
            'rawResponse' => $rawResponse,
            'errorCode' => $errorCode,
        ]);
    }

    public static function conflict(string $message, ?array $rawResponse = null): self
    {
        return self::rejected($message, $rawResponse, 'session_already_active');
    }

    public static function fromApiResponse(array $response, RemoteStartRequestDTO $request): self
    {
        $success = (bool) ($response['success'] ?? false);
        $operationStatus = self::firstSteveOperationResponse($response);
        $statusValue = $operationStatus
            ?? $response['status']
            ?? $response['data']['status']
            ?? ($success ? 'ACCEPTED' : 'REJECTED');
        $status = RemoteStartStatusEnum::fromString((string) $statusValue);

        $message = self::firstSteveOperationError($response)
            ?? $response['message']
            ?? $response['data']['message']
            ?? $status?->userMessage()
            ?? 'Unknown SteVe response';
        $errorCode = $success ? null : self::firstErrorCode($response);

        $transaction = $response['data']['transaction']
            ?? $response['transaction']
            ?? $response['data']
            ?? null;

        return new self([
            'success' => $success && $status?->isAccepted(),
            'message' => $message,
            'status' => $status,
            'transaction' => $transaction,
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
        return $this->status === RemoteStartStatusEnum::ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === RemoteStartStatusEnum::REJECTED;
    }

    public function getTransactionId(): ?int
    {
        return $this->transaction['id'] ?? $this->transaction['transactionId'] ?? null;
    }

    public function toApiResponse(): array
    {
        $meta = [
            'status' => $this->status?->value,
            'statusLabel' => $this->status?->label(),
        ];

        $data = $this->transaction !== null ? ['transaction' => $this->transaction] : null;

        if ($this->success) {
            return ApiEnvelope::ok($this->message, $data, $meta, $this->timestamp);
        }

        return ApiEnvelope::error($this->message, $this->errorCode ?? 'remote_start_rejected', $data, $meta, $this->timestamp);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'status' => $this->status?->value,
            'transaction' => $this->transaction,
            'timestamp' => $this->timestamp,
        ];
    }
}

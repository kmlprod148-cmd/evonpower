<?php

namespace App\Exceptions;

use App\Http\Responses\ApiEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

/**
 * Thrown by ChargingPointService when SteVe rejects (or fails to receive) a
 * createChargePoint call. The DB transaction that wraps the local row insert
 * sees this exception and rolls back, so a missed SteVe provisioning never
 * leaves a half-created charging point in the local database.
 *
 * Carries the SteVe error envelope verbatim so callers (controllers, API
 * surfaces) can decide how much of it to expose to the user.
 */
class SteVeProvisioningFailedException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly array $upstreamError = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function fromEnvelope(array $envelope): self
    {
        $message = $envelope['message']
            ?? $envelope['error']
            ?? 'SteVe rejected the create-charge-point request.';

        return new self(
            (string) $message,
            isset($envelope['status']) ? (int) $envelope['status'] : null,
            $envelope,
        );
    }

    public function render(Request $request): ?JsonResponse
    {
        if (!$request->expectsJson() && !$request->is('api/*')) {
            return null;
        }

        return response()->json(
            ApiEnvelope::error(
                $this->getMessage(),
                'steve_provisioning_failed',
                null,
                [
                    'code' => 'steve_provisioning_failed',
                    'upstream_status' => $this->httpStatus,
                ]
            ),
            502
        );
    }
}

<?php

namespace App\Http\Responses;

use Carbon\Carbon;

/**
 * P3: canonical response envelope for the OCPP / SteVe API surface.
 *
 * Every API response (success, error, validation failure, configuration error)
 * shares the same top-level shape, so clients can rely on a single parser:
 *
 *     {
 *       "success":   bool,
 *       "message":   string,        // human-readable summary
 *       "data":      mixed|null,    // domain payload on success
 *       "error":     string|null,   // short error code on failure
 *       "errors":    object|null,   // per-field validation errors (422)
 *       "meta":      object|null,   // protocol-specific fields (status, codes, IDs)
 *       "timestamp": ISO-8601 string
 *     }
 *
 * Helpers return associative arrays — callers wrap them in `response()->json(...)`
 * with the appropriate HTTP status. Keeping it as arrays lets DTOs build envelopes
 * inside `toApiResponse()` without forcing a JsonResponse return type.
 */
final class ApiEnvelope
{
    public static function ok(string $message, mixed $data = null, ?array $meta = null, ?string $timestamp = null): array
    {
        return self::build(true, $message, $data, null, null, $meta, $timestamp);
    }

    public static function error(string $message, ?string $error = null, mixed $data = null, ?array $meta = null, ?string $timestamp = null): array
    {
        return self::build(false, $message, $data, $error, null, $meta, $timestamp);
    }

    public static function validation(string $message, array $errors, ?string $error = 'validation_failed'): array
    {
        return self::build(false, $message, null, $error, $errors, null, null);
    }

    private static function build(
        bool $success,
        string $message,
        mixed $data,
        ?string $error,
        ?array $errors,
        ?array $meta,
        ?string $timestamp
    ): array {
        return [
            'success'   => $success,
            'message'   => $message,
            'data'      => $data,
            'error'     => $error,
            'errors'    => $errors,
            'meta'      => $meta,
            'timestamp' => $timestamp ?? Carbon::now()->toISOString(),
        ];
    }
}

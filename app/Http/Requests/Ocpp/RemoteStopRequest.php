<?php

namespace App\Http\Requests\Ocpp;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for POST /api/v1/ocpp/charging-points/{id}/remote-stop.
 *
 * Constraints:
 *  - transactionId: positive int; SteVe assigns 1..PHP_INT_MAX. We accept up
 *    to 2^31-1 (DB column is INT) and reject anything larger client-side so
 *    we surface a clean 422 instead of a SQL truncation later.
 *  - idempotencyKey (optional): same semantics as RemoteStartRequest.
 */
class RemoteStopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transactionId'  => ['required', 'integer', 'min:1', 'max:2147483647'],
            'idempotencyKey' => ['sometimes', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'idempotencyKey.regex' => 'idempotencyKey must contain only letters, digits, dashes, or underscores.',
        ];
    }
}

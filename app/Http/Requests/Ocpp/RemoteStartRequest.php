<?php

namespace App\Http\Requests\Ocpp;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for POST /api/v1/ocpp/charging-points/{id}/remote-start.
 *
 * Defaults (applied in prepareForValidation when the field is missing):
 *   - connectorId → 1
 *   - ocppTag     → resolved server-side by OcppTagResolver (config default
 *                   STEVE_DEFAULT_ID_TAG or the first usable SteVe tag).
 *
 * Constraints:
 *  - connectorId: 1..16 (OCPP 1.6 doesn't cap explicitly but >16 is unheard of
 *    on real hardware and any value > 16 is almost certainly a bug)
 *  - ocppTag:     CiString20Type per OCPP 1.6 spec; alphanumeric, dash, underscore.
 *                 SteVe also accepts ':' but we refuse it client-side because
 *                 nothing in EVON issues colon-bearing tags and they break URL
 *                 encoding in some downstream tools.
 *  - idempotencyKey (optional): used by callers to dedupe retries. The controller
 *    threads it through ChargePointCommand for audit; on a duplicate key we
 *    return the original outcome instead of re-dispatching.
 */
class RemoteStartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Default connectorId to 1 when the field is absent. The ocppTag stays
     * unset here so the controller can resolve it via OcppTagResolver after
     * validation (it needs to make a SteVe round-trip we don't want inside
     * FormRequest::rules()).
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('connectorId') || $this->input('connectorId') === null || $this->input('connectorId') === '') {
            $this->merge(['connectorId' => 1]);
        }
    }

    public function rules(): array
    {
        return [
            'connectorId'    => ['sometimes', 'integer', 'min:1', 'max:16'],
            'ocppTag'        => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'idempotencyKey' => ['sometimes', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'ocppTag.regex'        => 'ocppTag must contain only letters, digits, dashes, or underscores.',
            'idempotencyKey.regex' => 'idempotencyKey must contain only letters, digits, dashes, or underscores.',
        ];
    }
}

<?php

namespace App\Http\Requests\Ocpp;

use Illuminate\Foundation\Http\FormRequest;

class UnlockConnectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // connectorId is on the URL path, not the body, so no rules here.
        // Kept as a marker class so future per-payload rules (e.g., force, reason) have a home.
        return [];
    }
}

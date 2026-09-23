<?php

declare(strict_types=1);

namespace App\Http\Requests\Steve\OcppTag;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the body of POST /api/v1/steve/ocpp-tags.
 *
 * Mirrors SteVe's OcppTagForm.
 */
class StoreOcppTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idTag'                     => ['required', 'string', 'max:64'],
            'parentIdTag'               => ['nullable', 'string', 'max:64'],
            'expiryDate'                => ['nullable', 'date'],
            'maxActiveTransactionCount' => ['nullable', 'integer', 'min:0'],
            'note'                      => ['nullable', 'string'],
        ];
    }
}

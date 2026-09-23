<?php

declare(strict_types=1);

namespace App\Http\Requests\Steve\OcppTag;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the body of PUT /api/v1/steve/ocpp-tags/{ocppTagPk}.
 *
 * Same shape as OcppTagForm but idTag is optional on update (SteVe keys
 * lookups by ocppTagPk). Provide it only when renaming.
 */
class UpdateOcppTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idTag'                     => ['sometimes', 'string', 'max:64'],
            'parentIdTag'               => ['nullable', 'string', 'max:64'],
            'expiryDate'                => ['nullable', 'date'],
            'maxActiveTransactionCount' => ['nullable', 'integer', 'min:0'],
            'note'                      => ['nullable', 'string'],
        ];
    }
}

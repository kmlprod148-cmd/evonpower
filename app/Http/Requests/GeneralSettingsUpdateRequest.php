<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeneralSettingsUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Or implement authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Profile settings
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $this->user()->id,

            // Avatar upload
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',

            // Preferences
            'language' => 'sometimes|in:en,fr,es,de',
            'timezone' => 'sometimes|timezone',
            'theme' => 'sometimes|in:light,dark,system',

            // Notification preferences
            'email_notifications' => 'sometimes|boolean',
            'sms_notifications' => 'sometimes|boolean',
        ];
    }
}
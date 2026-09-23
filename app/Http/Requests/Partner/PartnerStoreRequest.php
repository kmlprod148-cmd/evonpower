<?php

namespace App\Http\Requests\Partner;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class PartnerStoreRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = auth()->user();
        if (!$user) {
            \Log::warning('PartnerStoreRequest: No authenticated user.');
            return false;
        }

        \Log::info('PartnerStoreRequest authorize - User roles: ' . implode(', ', $user->getRoleNames()->toArray()));
        \Log::info('PartnerStoreRequest authorize - Is user admin (hasRole check): ' . ($user->hasRole(['admin', 'Admin', 'super_admin', 'super_admin']) ? 'true' : 'false'));

        // Allow admin users to create partners
        if ($user->hasRole(['admin', 'Admin', 'super_admin', 'super_admin'])) {
            \Log::info('PartnerStoreRequest authorize - Admin user, granting access.');
            return true;
        }

        // Allow integrator users to create partners
        if ($user->hasRole(['integrator', 'Integrator'])) {
            \Log::info('PartnerStoreRequest authorize - Integrator user, granting access.');
            return true;
        }

        // For other roles, defer to the PartnerPolicy
        $canCreate = $user->can('create', \App\Models\Partner::class);
        \Log::info('PartnerStoreRequest authorize - Non-admin user, can create Partner via policy: ' . ($canCreate ? 'true' : 'false'));
        return $canCreate;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'type' => ['required', 'string', Rule::in(['Exploitant', 'Propriétaire', 'Intégrateur'])],
            'email' => 'required|email|max:255|unique:partners',
            'phone' => 'required|string|max:20',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'business_profile_id' => 'nullable|exists:business_profiles,id',
            'integrator_id' => 'nullable|exists:integrators,id',
            'website' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'stations_count' => 'nullable|integer|min:0',
            'currency' => 'required|string|size:3',
            'collection_mode' => ['nullable', 'string', Rule::in(['admin', 'integrator', 'partner'])],
            // Admin user account credentials
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
        ];
    }
}
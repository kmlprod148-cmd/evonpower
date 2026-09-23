@extends('layouts.app')

@section('title', 'Modifier Intégrateur' . ($integrator->name ? ' - ' . $integrator->name : ''))
@section('page-title', 'Modifier Intégrateur')

@push('styles')
<style>
    .gradient-bg {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .form-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        transition: all 0.3s ease;
    }
    
    .form-card:hover {
        border-color: #10b981;
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.15);
    }
    
    .form-input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
        background: white;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .form-input.error {
        border-color: #ef4444;
    }
    
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-error {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
    
    .action-btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    
    .action-btn-primary {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .action-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        color: white;
    }
    
    .action-btn-secondary {
        background: #f8fafc;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    
    .action-btn-secondary:hover {
        background: #e2e8f0;
        color: #374151;
    }
    
    .checkbox-container {
        display: flex;
        align-items: center;
        space-x: 8px;
    }
    
    .checkbox-input {
        width: 18px;
        height: 18px;
        accent-color: #10b981;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .status-inactive {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="gradient-bg text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <span class="text-2xl font-bold text-white">
                        {{ strtoupper(substr($integrator->name ?? 'IN', 0, 2)) }}
                    </span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold mb-2">Modifier Intégrateur</h1>
                    <p class="text-green-100">{{ $integrator->name ?? 'Intégrateur #' . $integrator->id }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span class="status-badge {{ $integrator->is_active ? 'status-active' : 'status-inactive' }}">
                    {{ $integrator->is_active ? 'Actif' : 'Inactif' }}
                </span>
                <a href="{{ route('integrators.show', $integrator) }}" class="action-btn action-btn-secondary">
                    <i class="fas fa-eye mr-2"></i>
                    Voir
                </a>
            </div>
        </div>
    </div>

    <!-- Formulaire d'édition -->
    <form method="POST" action="{{ route('integrators.update', $integrator) }}" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="form-card p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-edit text-green-500 mr-2"></i>
                Informations Générales
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nom -->
                <div>
                    <label for="name" class="form-label">Nom de l'intégrateur *</label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name', $integrator->name) }}"
                           class="form-input @error('name') error @enderror"
                           required>
                    @error('name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Email -->
                <div>
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           value="{{ old('email', $integrator->email) }}"
                           class="form-input @error('email') error @enderror"
                           required>
                    @error('email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Téléphone -->
                <div>
                    <label for="phone" class="form-label">Téléphone</label>
                    <input type="text" 
                           name="phone" 
                           id="phone" 
                           value="{{ old('phone', $integrator->phone) }}"
                           class="form-input @error('phone') error @enderror">
                    @error('phone')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Contact Name -->
                <div>
                    <label for="contact_name" class="form-label">Nom du contact</label>
                    <input type="text" 
                           name="contact_name" 
                           id="contact_name" 
                           value="{{ old('contact_name', $integrator->contact_name) }}"
                           class="form-input @error('contact_name') error @enderror">
                    @error('contact_name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Adresse -->
        <div class="form-card p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-map-marker-alt text-green-500 mr-2"></i>
                Adresse
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Adresse -->
                <div class="md:col-span-2">
                    <label for="address" class="form-label">Adresse</label>
                    <textarea name="address" 
                              id="address" 
                              rows="3"
                              class="form-input @error('address') error @enderror">{{ old('address', $integrator->address) }}</textarea>
                    @error('address')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Ville -->
                <div>
                    <label for="city" class="form-label">Ville</label>
                    <input type="text" 
                           name="city" 
                           id="city" 
                           value="{{ old('city', $integrator->city) }}"
                           class="form-input @error('city') error @enderror">
                    @error('city')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Code postal -->
                <div>
                    <label for="postal_code" class="form-label">Code postal</label>
                    <input type="text" 
                           name="postal_code" 
                           id="postal_code" 
                           value="{{ old('postal_code', $integrator->postal_code) }}"
                           class="form-input @error('postal_code') error @enderror">
                    @error('postal_code')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Pays -->
                <div>
                    <label for="country" class="form-label">Pays</label>
                    <input type="text" 
                           name="country" 
                           id="country" 
                           value="{{ old('country', $integrator->country) }}"
                           class="form-input @error('country') error @enderror">
                    @error('country')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Informations supplémentaires -->
        <div class="form-card p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-info-circle text-green-500 mr-2"></i>
                Informations Supplémentaires
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Site web -->
                <div>
                    <label for="website" class="form-label">Site web</label>
                    <input type="url" 
                           name="website" 
                           id="website" 
                           value="{{ old('website', $integrator->website) }}"
                           class="form-input @error('website') error @enderror"
                           placeholder="https://example.com">
                    @error('website')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Profil business -->
                <div>
                    <label for="business_profile_id" class="form-label">Profil business</label>
                    <select name="business_profile_id" 
                            id="business_profile_id" 
                            class="form-input @error('business_profile_id') error @enderror">
                        <option value="">Aucun profil</option>
                        @foreach($businessProfiles as $profile)
                            <option value="{{ $profile->id }}" 
                                    {{ old('business_profile_id', $integrator->business_profile_id) == $profile->id ? 'selected' : '' }}>
                                {{ $profile->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('business_profile_id')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" 
                              id="description" 
                              rows="4"
                              class="form-input @error('description') error @enderror"
                              placeholder="Description de l'intégrateur...">{{ old('description', $integrator->description) }}</textarea>
                    @error('description')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Statut -->
        <div class="form-card p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-cog text-green-500 mr-2"></i>
                Statut
            </h2>
            
            <div class="checkbox-container">
                <input type="checkbox" 
                       name="is_active" 
                       id="is_active" 
                       value="1"
                       class="checkbox-input"
                       {{ old('is_active', $integrator->is_active) ? 'checked' : '' }}>
                <label for="is_active" class="form-label mb-0 ml-2">
                    Intégrateur actif
                </label>
            </div>
            <p class="text-sm text-gray-600 mt-2">
                Décochez cette case pour désactiver l'intégrateur.
            </p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between bg-gray-50 rounded-lg p-4">
            <a href="{{ route('integrators.show', $integrator) }}" class="action-btn action-btn-secondary">
                <i class="fas fa-times mr-2"></i>
                Annuler
            </a>
            
            <div class="flex items-center space-x-3">
                <a href="{{ route('integrators.show', $integrator) }}" class="action-btn action-btn-secondary">
                    <i class="fas fa-eye mr-2"></i>
                    Voir
                </a>
                <button type="submit" class="action-btn action-btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Enregistrer
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
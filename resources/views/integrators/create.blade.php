@extends('layouts.app')

@section('title', 'Créer un Intégrateur - EVON')
@section('page-title', 'Créer un Intégrateur')

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
    
    .password-strength {
        margin-top: 4px;
        font-size: 12px;
    }
    
    .password-strength.weak {
        color: #ef4444;
    }
    
    .password-strength.medium {
        color: #f59e0b;
    }
    
    .password-strength.strong {
        color: #10b981;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');
    
    // Validation en temps réel du mot de passe
    password.addEventListener('input', function() {
        const strength = checkPasswordStrength(this.value);
        const strengthElement = document.getElementById('password-strength') || createStrengthElement();
        strengthElement.textContent = strength.text;
        strengthElement.className = 'password-strength ' + strength.class;
    });
    
    // Validation de la confirmation du mot de passe
    confirmPassword.addEventListener('input', function() {
        if (this.value !== password.value) {
            this.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            this.setCustomValidity('');
        }
    });
    
    function checkPasswordStrength(password) {
        if (password.length < 8) {
            return { text: 'Mot de passe trop court (minimum 8 caractères)', class: 'weak' };
        }
        
        let score = 0;
        if (password.match(/[a-z]/)) score++;
        if (password.match(/[A-Z]/)) score++;
        if (password.match(/[0-9]/)) score++;
        if (password.match(/[^a-zA-Z0-9]/)) score++;
        
        if (score < 2) {
            return { text: 'Mot de passe faible', class: 'weak' };
        } else if (score < 4) {
            return { text: 'Mot de passe moyen', class: 'medium' };
        } else {
            return { text: 'Mot de passe fort', class: 'strong' };
        }
    }
    
    function createStrengthElement() {
        const element = document.createElement('div');
        element.id = 'password-strength';
        element.className = 'password-strength';
        password.parentNode.appendChild(element);
        return element;
    }
});
</script>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="gradient-bg text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-2">
                    <i class="fas fa-user-plus mr-3"></i>
                    Créer un Intégrateur
                </h1>
                <p class="text-green-100">
                    Ajoutez un nouvel intégrateur à votre système
                </p>
            </div>
            <a href="{{ route('integrators.index') }}" class="action-btn action-btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour à la liste
            </a>
        </div>
    </div>

    <!-- Formulaire de création -->
    <form method="POST" action="{{ route('integrators.store') }}" class="space-y-6">
        @csrf
        
        <!-- Compte Utilisateur -->
        <div class="form-card p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-user text-green-500 mr-2"></i>
                Compte Utilisateur
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nom d'utilisateur -->
                <div>
                    <label for="user_name" class="form-label">Nom d'utilisateur *</label>
                    <input type="text" 
                           name="user_name" 
                           id="user_name" 
                           value="{{ old('user_name') }}"
                           class="form-input @error('user_name') error @enderror"
                           required>
                    @error('user_name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Email utilisateur -->
                <div>
                    <label for="user_email" class="form-label">Email de connexion *</label>
                    <input type="email" 
                           name="user_email" 
                           id="user_email" 
                           value="{{ old('user_email') }}"
                           class="form-input @error('user_email') error @enderror"
                           required>
                    @error('user_email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Mot de passe -->
                <div>
                    <label for="password" class="form-label">Mot de passe *</label>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           class="form-input @error('password') error @enderror"
                           required
                           minlength="8">
                    @error('password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-600 mt-1">Minimum 8 caractères</p>
                </div>
                
                <!-- Confirmation mot de passe -->
                <div>
                    <label for="password_confirmation" class="form-label">Confirmer le mot de passe *</label>
                    <input type="password" 
                           name="password_confirmation" 
                           id="password_confirmation" 
                           class="form-input @error('password_confirmation') error @enderror"
                           required
                           minlength="8">
                    @error('password_confirmation')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="form-card p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-edit text-green-500 mr-2"></i>
                Informations de l'Intégrateur
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nom de l'intégrateur -->
                <div>
                    <label for="name" class="form-label">Nom de l'intégrateur *</label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name') }}"
                           class="form-input @error('name') error @enderror"
                           required>
                    @error('name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Email de l'intégrateur -->
                <div>
                    <label for="email" class="form-label">Email de l'intégrateur *</label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           value="{{ old('email') }}"
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
                           value="{{ old('phone') }}"
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
                           value="{{ old('contact_name') }}"
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
                              class="form-input @error('address') error @enderror">{{ old('address') }}</textarea>
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
                           value="{{ old('city') }}"
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
                           value="{{ old('postal_code') }}"
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
                           value="{{ old('country') }}"
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
                           value="{{ old('website') }}"
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
                                    {{ old('business_profile_id') == $profile->id ? 'selected' : '' }}>
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
                              placeholder="Description de l'intégrateur...">{{ old('description') }}</textarea>
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
                       {{ old('is_active', true) ? 'checked' : '' }}>
                <label for="is_active" class="form-label mb-0 ml-2">
                    Intégrateur actif
                </label>
            </div>
            <p class="text-sm text-gray-600 mt-2">
                L'intégrateur sera créé en tant qu'actif par défaut.
            </p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between bg-gray-50 rounded-lg p-4">
            <a href="{{ route('integrators.index') }}" class="action-btn action-btn-secondary">
                <i class="fas fa-times mr-2"></i>
                Annuler
            </a>
            
            <div class="flex items-center space-x-3">
                <button type="submit" class="action-btn action-btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Créer l'intégrateur
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
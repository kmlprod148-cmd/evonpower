@extends('layouts.app')

@section('title', __('messages.new_user') ?? 'Créer un Utilisateur')

@push('styles')
<style>
    .create-user-content {
        padding-bottom: 120px;
    }
    
    @media (max-width: 768px) {
        .create-user-content {
            padding-bottom: 160px;
        }
    }
    
    .form-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: 1rem;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    
    .dark .form-card {
        background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        border-color: #374151;
    }
    
    .form-card:hover {
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    }
    
    .section-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-radius: 0.75rem;
        padding: 1.5rem;
        color: white;
        margin-bottom: 1.5rem;
    }
    
    .input-group-premium {
        position: relative;
    }
    
    .input-group-premium input,
    .input-group-premium select,
    .input-group-premium textarea {
        border: 2px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.875rem 1rem;
        width: 100%;
        transition: all 0.3s ease;
        background: white;
    }
    
    .dark .input-group-premium input,
    .dark .input-group-premium select,
    .dark .input-group-premium textarea {
        background: #1f2937;
        border-color: #374151;
        color: white;
    }
    
    .input-group-premium input:focus,
    .input-group-premium select:focus,
    .input-group-premium textarea:focus {
        border-color: #10b981;
        outline: none;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
    }
    
    .input-group-premium label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }
    
    .dark .input-group-premium label {
        color: #e5e7eb;
    }
    
    .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
    }
    
    .input-with-icon {
        padding-left: 2.75rem !important;
    }
    
    .role-card {
        border: 2px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
    }
    
    .dark .role-card {
        background: #1f2937;
        border-color: #374151;
    }
    
    .role-card:hover {
        border-color: #10b981;
        background: #f0fdf4;
    }
    
    .dark .role-card:hover {
        background: rgba(16, 185, 129, 0.1);
    }
    
    .role-card.selected {
        border-color: #10b981;
        background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    }
    
    .dark .role-card.selected {
        background: rgba(16, 185, 129, 0.2);
    }
    
    .role-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    
    .btn-primary-premium {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 0.875rem 2rem;
        border-radius: 0.75rem;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-primary-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
    }
    
    .btn-secondary-premium {
        background: #f3f4f6;
        color: #374151;
        padding: 0.875rem 2rem;
        border-radius: 0.75rem;
        font-weight: 600;
        transition: all 0.3s ease;
        border: 1px solid #e5e7eb;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .dark .btn-secondary-premium {
        background: #374151;
        color: #e5e7eb;
        border-color: #4b5563;
    }
    
    .btn-secondary-premium:hover {
        background: #e5e7eb;
    }
    
    .dark .btn-secondary-premium:hover {
        background: #4b5563;
    }
    
    .toggle-switch {
        position: relative;
        width: 56px;
        height: 28px;
        background: #e5e7eb;
        border-radius: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .toggle-switch.active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .toggle-switch::after {
        content: '';
        position: absolute;
        width: 24px;
        height: 24px;
        background: white;
        border-radius: 50%;
        top: 2px;
        left: 2px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .toggle-switch.active::after {
        left: 30px;
    }
</style>
@endpush

@section('content')
<div class="space-y-6 create-user-content" x-data="createUserForm()">
    <!-- Header Section -->
    <div class="section-header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">{{ __('messages.new_user') ?? 'Nouvel Utilisateur' }}</h1>
                    <p class="text-green-100 text-sm mt-1">{{ __('Créer un nouveau compte utilisateur dans le système') }}</p>
                </div>
            </div>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ __('Retour') }}
            </a>
        </div>
    </div>

    <!-- Error Messages -->
    @if($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/50 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-red-800 dark:text-red-200">{{ __('Erreurs de validation') }}</h4>
                    <ul class="mt-2 text-sm text-red-600 dark:text-red-300 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        
        <!-- Personal Information -->
        <div class="form-card p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-eco-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                {{ __('Informations personnelles') }}
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div class="input-group-premium">
                    <label for="name">{{ __('Nom complet') }} <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="input-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </span>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required 
                               class="input-with-icon" placeholder="Jean Dupont">
                    </div>
                </div>
                
                <!-- Email -->
                <div class="input-group-premium">
                    <label for="email">{{ __('Email') }} <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="input-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required 
                               class="input-with-icon" placeholder="email@exemple.com">
                    </div>
                </div>
                
                <!-- Phone -->
                <div class="input-group-premium">
                    <label for="phone">{{ __('Téléphone') }}</label>
                    <div class="relative">
                        <span class="input-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </span>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" 
                               class="input-with-icon" placeholder="+212 6XX XXX XXX">
                    </div>
                </div>
                
                <!-- Company -->
                <div class="input-group-premium">
                    <label for="company">{{ __('Entreprise') }}</label>
                    <div class="relative">
                        <span class="input-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </span>
                        <input type="text" id="company" name="company" value="{{ old('company') }}" 
                               class="input-with-icon" placeholder="Nom de l'entreprise">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security -->
        <div class="form-card p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-eco-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                {{ __('Sécurité') }}
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Password -->
                <div class="input-group-premium">
                    <label for="password">{{ __('Mot de passe') }} <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="input-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </span>
                        <input type="password" id="password" name="password" required 
                               class="input-with-icon" placeholder="••••••••">
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Minimum 8 caractères') }}</p>
                </div>
                
                <!-- Password Confirmation -->
                <div class="input-group-premium">
                    <label for="password_confirmation">{{ __('Confirmer le mot de passe') }} <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="input-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </span>
                        <input type="password" id="password_confirmation" name="password_confirmation" required 
                               class="input-with-icon" placeholder="••••••••">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Role Selection -->
        <div class="form-card p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-eco-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                {{ __('Rôle et permissions') }}
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Admin Role -->
                <label class="role-card" :class="{ 'selected': role === 'admin' }">
                    <input type="radio" name="role" value="admin" x-model="role" class="hidden" {{ old('role') == 'admin' ? 'checked' : '' }}>
                    <div class="flex flex-col items-center text-center">
                        <div class="role-icon bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ __('Administrateur') }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Accès complet') }}</span>
                    </div>
                </label>
                
                <!-- Integrator Role -->
                <label class="role-card" :class="{ 'selected': role === 'integrator' }">
                    <input type="radio" name="role" value="integrator" x-model="role" class="hidden" {{ old('role') == 'integrator' ? 'checked' : '' }}>
                    <div class="flex flex-col items-center text-center">
                        <div class="role-icon bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ __('Intégrateur') }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Gestion réseau') }}</span>
                    </div>
                </label>
                
                <!-- Partner Role -->
                <label class="role-card" :class="{ 'selected': role === 'partner' }">
                    <input type="radio" name="role" value="partner" x-model="role" class="hidden" {{ old('role') == 'partner' ? 'checked' : '' }}>
                    <div class="flex flex-col items-center text-center">
                        <div class="role-icon bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ __('Partenaire') }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Propriétaire bornes') }}</span>
                    </div>
                </label>
                
                <!-- Operator Role -->
                <label class="role-card" :class="{ 'selected': role === 'operator' }">
                    <input type="radio" name="role" value="operator" x-model="role" class="hidden" {{ old('role') == 'operator' ? 'checked' : '' }}>
                    <div class="flex flex-col items-center text-center">
                        <div class="role-icon bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ __('Opérateur') }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Exploitation') }}</span>
                    </div>
                </label>
            </div>
            
            <!-- Integrator Selection (for partner/operator) -->
            <div x-show="role === 'partner' || role === 'operator'" x-transition class="mt-6">
                <div class="input-group-premium">
                    <label for="integrator_id">{{ __('Intégrateur responsable') }} <span class="text-red-500">*</span></label>
                    <select id="integrator_id" name="integrator_id" :required="role === 'partner' || role === 'operator'">
                        <option value="">{{ __('Sélectionner un intégrateur') }}</option>
                        @foreach($integrators as $integrator)
                            <option value="{{ $integrator->id }}" {{ old('integrator_id') == $integrator->id ? 'selected' : '' }}>
                                {{ $integrator->name }} ({{ $integrator->user->name ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Sélectionnez l\'intégrateur qui sera responsable de cet utilisateur.') }}</p>
                </div>
            </div>
        </div>
        
        <!-- Status & Notes -->
        <div class="form-card p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-eco-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                {{ __('Statut et notes') }}
            </h3>
            
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl mb-6">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ __('Utilisateur actif') }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('L\'utilisateur pourra se connecter immédiatement') }}</p>
                </div>
                <label class="relative cursor-pointer" x-data="{ active: {{ old('is_active', true) ? 'true' : 'false' }} }">
                    <input type="hidden" name="is_active" :value="active ? '1' : '0'">
                    <div class="toggle-switch" :class="{ 'active': active }" @click="active = !active"></div>
                </label>
            </div>
            
            <div class="input-group-premium">
                <label for="notes">{{ __('Notes') }}</label>
                <textarea id="notes" name="notes" rows="3" placeholder="{{ __('Informations supplémentaires...') }}">{{ old('notes') }}</textarea>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row justify-end gap-4">
            <a href="{{ route('admin.users.index') }}" class="btn-secondary-premium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ __('Annuler') }}
            </a>
            <button type="submit" class="btn-primary-premium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ __('Créer l\'utilisateur') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function createUserForm() {
    return {
        role: '{{ old('role', '') }}',
        
        init() {
            // Password validation
            const password = document.getElementById('password');
            const passwordConfirmation = document.getElementById('password_confirmation');
            
            const validatePassword = () => {
                if (password.value !== passwordConfirmation.value) {
                    passwordConfirmation.setCustomValidity('Les mots de passe ne correspondent pas');
                } else {
                    passwordConfirmation.setCustomValidity('');
                }
            };
            
            password.addEventListener('change', validatePassword);
            passwordConfirmation.addEventListener('keyup', validatePassword);
        }
    }
}
</script>
@endpush

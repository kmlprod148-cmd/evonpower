@extends('layouts.app')

@section('title', __('messages.my_profile'))

@push('styles')
<style>
    .profile-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1.5rem;
    }
    
    .profile-header {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        border-radius: 1rem;
        padding: 2rem;
        color: white;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    
    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 300px;
        height: 100%;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.5;
    }
    
    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 4px solid rgba(255, 255, 255, 0.3);
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 700;
        text-transform: uppercase;
        position: relative;
        overflow: hidden;
    }
    
    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .profile-avatar-edit {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 32px;
        height: 32px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        transition: transform 0.2s;
    }
    
    .profile-avatar-edit:hover {
        transform: scale(1.1);
    }
    
    .profile-card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    
    .dark .profile-card {
        background: #1f2937;
    }
    
    .profile-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .dark .profile-card-header {
        border-color: #374151;
    }
    
    .profile-card-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .dark .profile-card-title {
        color: #f9fafb;
    }
    
    .profile-card-title svg {
        width: 1.25rem;
        height: 1.25rem;
        color: #059669;
    }
    
    .profile-card-body {
        padding: 1.5rem;
    }
    
    .profile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    
    .profile-field {
        margin-bottom: 1.25rem;
    }
    
    .profile-field:last-child {
        margin-bottom: 0;
    }
    
    .profile-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #6b7280;
        margin-bottom: 0.5rem;
    }
    
    .dark .profile-label {
        color: #9ca3af;
    }
    
    .profile-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.9375rem;
        color: #111827;
        background: #f9fafb;
        transition: all 0.2s;
    }
    
    .dark .profile-input {
        background: #374151;
        border-color: #4b5563;
        color: #f9fafb;
    }
    
    .profile-input:focus {
        outline: none;
        border-color: #059669;
        background: white;
        box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
    }
    
    .dark .profile-input:focus {
        background: #1f2937;
    }
    
    .profile-value {
        font-size: 0.9375rem;
        color: #111827;
        font-weight: 500;
    }
    
    .dark .profile-value {
        color: #f9fafb;
    }
    
    .profile-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }
    
    .profile-btn-primary {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        color: white;
    }
    
    .profile-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
    }
    
    .profile-btn-secondary {
        background: #f3f4f6;
        color: #374151;
    }
    
    .dark .profile-btn-secondary {
        background: #374151;
        color: #f9fafb;
    }
    
    .profile-btn-secondary:hover {
        background: #e5e7eb;
    }
    
    .dark .profile-btn-secondary:hover {
        background: #4b5563;
    }
    
    .profile-btn-danger {
        background: #fee2e2;
        color: #dc2626;
    }
    
    .profile-btn-danger:hover {
        background: #fecaca;
    }
    
    .profile-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .profile-badge-success {
        background: #d1fae5;
        color: #059669;
    }
    
    .dark .profile-badge-success {
        background: rgba(5, 150, 105, 0.2);
    }
    
    .profile-badge-warning {
        background: #fef3c7;
        color: #d97706;
    }
    
    .dark .profile-badge-warning {
        background: rgba(217, 119, 6, 0.2);
    }
    
    .profile-stat-card {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border-radius: 0.75rem;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .dark .profile-stat-card {
        background: linear-gradient(135deg, rgba(5, 150, 105, 0.1) 0%, rgba(5, 150, 105, 0.2) 100%);
    }
    
    .profile-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        background: #059669;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .profile-stat-icon svg {
        width: 24px;
        height: 24px;
        color: white;
    }
    
    .profile-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #059669;
    }
    
    .profile-stat-label {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .dark .profile-stat-label {
        color: #9ca3af;
    }
    
    .payment-method-card {
        border: 2px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .dark .payment-method-card {
        border-color: #374151;
    }
    
    .payment-method-card:hover {
        border-color: #059669;
    }
    
    .payment-method-card.active {
        border-color: #059669;
        background: #f0fdf4;
    }
    
    .dark .payment-method-card.active {
        background: rgba(5, 150, 105, 0.1);
    }
    
    .profile-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        padding-top: 1rem;
        border-top: 1px solid #e5e7eb;
        margin-top: 1.5rem;
    }
    
    .dark .profile-actions {
        border-color: #374151;
    }
    
    .profile-info-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem 0;
    }
    
    .profile-info-icon {
        width: 40px;
        height: 40px;
        border-radius: 0.5rem;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .dark .profile-info-icon {
        background: #374151;
    }
    
    .profile-info-icon svg {
        width: 20px;
        height: 20px;
        color: #6b7280;
    }
    
    .alert-success {
        background: #d1fae5;
        border: 1px solid #a7f3d0;
        color: #065f46;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .dark .alert-success {
        background: rgba(5, 150, 105, 0.2);
        border-color: rgba(5, 150, 105, 0.3);
        color: #34d399;
    }
    
    @media (max-width: 768px) {
        .profile-container {
            padding: 1rem;
        }
        
        .profile-header {
            padding: 1.5rem;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            font-size: 2rem;
        }
        
        .profile-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="profile-container">
    {{-- Success Messages --}}
    @if (session('status') === 'profile-updated')
        <div class="alert-success">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ __('messages.profile_updated_successfully') }}
        </div>
    @endif
    
    @if (session('status') === 'payment-method-updated')
        <div class="alert-success">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ __('messages.payment_method_updated_successfully') }}
        </div>
    @endif

    {{-- Profile Header --}}
    <div class="profile-header">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-6 relative z-10">
            <div class="profile-avatar">
                @if($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}">
                @else
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                @endif
            </div>
            
            <div class="flex-1">
                <h1 class="text-2xl font-bold mb-1">{{ $user->name }}</h1>
                <p class="text-white/80 mb-2">{{ $user->email }}</p>
                <div class="flex flex-wrap items-center gap-2">
                    @if($user->email_verified_at)
                        <span class="profile-badge profile-badge-success">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('messages.email_verified') }}
                        </span>
                    @else
                        <span class="profile-badge profile-badge-warning">
                            {{ __('messages.email_not_verified') }}
                        </span>
                    @endif
                    
                    @foreach($user->roles as $role)
                        <span class="profile-badge" style="background: rgba(255,255,255,0.2); color: white;">
                            {{ ucfirst($role->name) }}
                        </span>
                    @endforeach
                </div>
            </div>
            
            <div class="text-right">
                <p class="text-white/60 text-sm">{{ __('messages.member_since') }}</p>
                <p class="font-semibold">{{ $user->created_at->format('d M Y') }}</p>
            </div>
        </div>
    </div>

    {{-- Wallet Section for Clients --}}
    @if($user->hasRole('client') || ($user->hasRole('user') && !$user->hasAnyRole(['admin', 'super-admin', 'integrator', 'operator', 'partner'])))
    @php
        $wallet = $user->wallet ?? $user->getOrCreateWallet();
        $balance = $wallet->balance ?? 0;
        $formattedBalance = $wallet->getFormattedBalance() ?? number_format($balance, 2, ',', ' ') . ' MAD';
    @endphp
    <div class="profile-card">
        <div class="profile-card-header">
            <h2 class="profile-card-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                {{ __('messages.my_balance') }}
            </h2>
            <a href="{{ route('credit-recharge.index') }}" class="profile-btn profile-btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                {{ __('messages.recharge') }}
            </a>
        </div>
        <div class="profile-card-body">
            <div class="profile-stat-card">
                <div class="profile-stat-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="profile-stat-value">{{ $formattedBalance }}</div>
                    <div class="profile-stat-label">{{ __('messages.available_balance') }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="profile-grid">
        {{-- Personal Information --}}
        <div class="profile-card">
            <div class="profile-card-header">
                <h2 class="profile-card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    {{ __('messages.personal_info') }}
                </h2>
            </div>
            <div class="profile-card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('patch')
                    
                    <div class="profile-field">
                        <label class="profile-label" for="name">{{ __('messages.full_name') }}</label>
                        <input type="text" id="name" name="name" class="profile-input" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="profile-field">
                        <label class="profile-label" for="email">{{ __('messages.email') }}</label>
                        <input type="email" id="email" name="email" class="profile-input" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="profile-field">
                        <label class="profile-label" for="phone">{{ __('messages.phone') }}</label>
                        <input type="tel" id="phone" name="phone" class="profile-input" value="{{ old('phone', $user->phone) }}">
                        @error('phone')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="profile-actions">
                        <button type="submit" class="profile-btn profile-btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('messages.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Address Information --}}
        <div class="profile-card">
            <div class="profile-card-header">
                <h2 class="profile-card-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ __('messages.address') }}
                </h2>
            </div>
            <div class="profile-card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('patch')
                    
                    {{-- Hidden fields to preserve other data --}}
                    <input type="hidden" name="name" value="{{ $user->name }}">
                    <input type="hidden" name="email" value="{{ $user->email }}">
                    
                    <div class="profile-field">
                        <label class="profile-label" for="address">{{ __('messages.address') }}</label>
                        <input type="text" id="address" name="address" class="profile-input" value="{{ old('address', $user->address) }}">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="profile-field">
                            <label class="profile-label" for="city">{{ __('messages.city') }}</label>
                            <input type="text" id="city" name="city" class="profile-input" value="{{ old('city', $user->city) }}">
                        </div>
                        
                        <div class="profile-field">
                            <label class="profile-label" for="postal_code">{{ __('messages.postal_code') }}</label>
                            <input type="text" id="postal_code" name="postal_code" class="profile-input" value="{{ old('postal_code', $user->postal_code) }}">
                        </div>
                    </div>
                    
                    <div class="profile-field">
                        <label class="profile-label" for="country">{{ __('messages.country') }}</label>
                        <select id="country" name="country" class="profile-input">
                            <option value="">{{ __('messages.select') }}</option>
                            <option value="MA" {{ $user->country == 'MA' ? 'selected' : '' }}>{{ __('messages.morocco') }}</option>
                            <option value="FR" {{ $user->country == 'FR' ? 'selected' : '' }}>{{ __('messages.france') }}</option>
                            <option value="BE" {{ $user->country == 'BE' ? 'selected' : '' }}>{{ __('messages.belgium') }}</option>
                            <option value="CH" {{ $user->country == 'CH' ? 'selected' : '' }}>{{ __('messages.switzerland') }}</option>
                            <option value="CA" {{ $user->country == 'CA' ? 'selected' : '' }}>{{ __('messages.canada') }}</option>
                            <option value="DE" {{ $user->country == 'DE' ? 'selected' : '' }}>{{ __('messages.germany') }}</option>
                            <option value="ES" {{ $user->country == 'ES' ? 'selected' : '' }}>{{ __('messages.spain') }}</option>
                        </select>
                    </div>
                    
                    <div class="profile-actions">
                        <button type="submit" class="profile-btn profile-btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('messages.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Security Section --}}
    <div class="profile-card">
        <div class="profile-card-header">
            <h2 class="profile-card-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                {{ __('messages.security') }}
            </h2>
        </div>
        <div class="profile-card-body">
            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('put')
                
                <div class="profile-grid">
                    <div>
                        <div class="profile-field">
                            <label class="profile-label" for="current_password">{{ __('messages.current_password') }}</label>
                            <input type="password" id="current_password" name="current_password" class="profile-input" autocomplete="current-password">
                            @error('current_password', 'updatePassword')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <div>
                        <div class="profile-field">
                            <label class="profile-label" for="password">{{ __('messages.new_password') }}</label>
                            <input type="password" id="password" name="password" class="profile-input" autocomplete="new-password">
                            @error('password', 'updatePassword')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <div>
                        <div class="profile-field">
                            <label class="profile-label" for="password_confirmation">{{ __('messages.confirm_new_password') }}</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="profile-input" autocomplete="new-password">
                            @error('password_confirmation', 'updatePassword')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="profile-actions">
                    <button type="submit" class="profile-btn profile-btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        {{ __('messages.change_password') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Payment Methods for Clients --}}
    @if($user->hasRole('client') || ($user->hasRole('user') && !$user->hasAnyRole(['admin', 'super-admin', 'integrator', 'operator', 'partner'])))
    <div class="profile-card">
        <div class="profile-card-header">
            <h2 class="profile-card-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                {{ __('messages.default_payment_method') }}
            </h2>
        </div>
        <div class="profile-card-body">
            <form method="POST" action="{{ route('profile.payment-method.update') }}">
                @csrf
                @method('patch')
                
                @php
                    $currentMethod = $user->default_payment_method ?? 'cmi';
                    $paymentMethods = [
                        'cmi' => ['name' => 'CMI', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'desc' => __('messages.bank_card')],
                        'prepaid_credit' => ['name' => __('messages.prepaid_balance'), 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'desc' => __('messages.prepaid_balance')],
                    ];
                @endphp
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($paymentMethods as $methodId => $method)
                    <label class="payment-method-card {{ $currentMethod === $methodId ? 'active' : '' }}">
                        <input type="radio" name="default_payment_method" value="{{ $methodId }}" class="sr-only" {{ $currentMethod === $methodId ? 'checked' : '' }}>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $method['icon'] }}"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $method['name'] }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $method['desc'] }}</div>
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>
                
                <div class="profile-actions">
                    <button type="submit" class="profile-btn profile-btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('messages.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Delete Account --}}
    <div class="profile-card" style="border: 1px solid #fecaca;">
        <div class="profile-card-header" style="background: #fef2f2;">
            <h2 class="profile-card-title" style="color: #dc2626;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #dc2626;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                {{ __('messages.danger_zone') }}
            </h2>
        </div>
        <div class="profile-card-body">
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                {{ __('messages.account_deletion_warning') }}
            </p>
            
            <form method="POST" action="{{ route('profile.destroy') }}" x-data="{ confirmDelete: false }">
                @csrf
                @method('delete')
                
                <div x-show="confirmDelete" class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <p class="text-red-800 dark:text-red-300 text-sm mb-3">{{ __('messages.enter_password_to_confirm') }}</p>
                    <input type="password" name="password" class="profile-input" style="border-color: #fca5a5;" placeholder="{{ __('messages.password') }}">
                    @error('password', 'userDeletion')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="flex gap-3">
                    <button type="button" x-show="!confirmDelete" @click="confirmDelete = true" class="profile-btn profile-btn-danger">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        {{ __('messages.delete_my_account') }}
                    </button>
                    
                    <button type="submit" x-show="confirmDelete" class="profile-btn profile-btn-danger">
                        {{ __('messages.confirm_deletion') }}
                    </button>
                    
                    <button type="button" x-show="confirmDelete" @click="confirmDelete = false" class="profile-btn profile-btn-secondary">
                        {{ __('messages.cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Payment method card selection
        const paymentCards = document.querySelectorAll('.payment-method-card');
        paymentCards.forEach(card => {
            card.addEventListener('click', function() {
                paymentCards.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
    });
</script>
@endpush

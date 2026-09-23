@extends('layouts.app')

@section('title', __('messages.my_profile'))
@section('page-title', __('messages.my_profile'))

@push('styles')
<style>
    .acct-header {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        border-radius: 1rem;
        padding: 2rem;
        color: white;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    .acct-header::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        pointer-events: none;
    }
    .acct-avatar {
        width: 80px; height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,.2);
        border: 3px solid rgba(255,255,255,.4);
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; font-weight: 700; flex-shrink: 0;
    }
    .acct-card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .dark .acct-card { background: #1f2937; }
    .acct-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f3f4f6;
        display: flex; align-items: center; gap: .75rem;
        font-weight: 600; font-size: 1rem; color: #111827;
    }
    .dark .acct-card-header { border-color: #374151; color: #f9fafb; }
    .acct-card-header svg { color: #059669; width: 1.25rem; height: 1.25rem; }
    .acct-card-body { padding: 1.5rem; }
    .field-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem; }
    .field-label {
        display: block; font-size: .8125rem; font-weight: 500;
        color: #6b7280; margin-bottom: .4rem;
    }
    .dark .field-label { color: #9ca3af; }
    .field-input {
        width: 100%; padding: .6875rem 1rem;
        border: 1px solid #e5e7eb; border-radius: .5rem;
        font-size: .9375rem; color: #111827; background: #f9fafb;
        transition: border-color .2s, box-shadow .2s;
    }
    .dark .field-input { background: #374151; border-color: #4b5563; color: #f9fafb; }
    .field-input:focus {
        outline: none; border-color: #059669; background: white;
        box-shadow: 0 0 0 3px rgba(5,150,105,.1);
    }
    .dark .field-input:focus { background: #1f2937; }
    .field-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z' clip-rule='evenodd'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right .75rem center; background-size: 1.25rem; padding-right: 2.5rem; }
    .btn-primary {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .6875rem 1.5rem; border-radius: .5rem;
        background: #059669; color: white; font-weight: 600;
        font-size: .875rem; border: none; cursor: pointer;
        transition: background .2s, transform .1s;
    }
    .btn-primary:hover { background: #047857; transform: translateY(-1px); }
    .btn-danger {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .6875rem 1.5rem; border-radius: .5rem;
        background: #ef4444; color: white; font-weight: 600;
        font-size: .875rem; border: none; cursor: pointer;
        transition: background .2s;
    }
    .btn-danger:hover { background: #dc2626; }
    .alert {
        padding: .875rem 1rem; border-radius: .5rem;
        margin-bottom: 1rem; font-size: .9rem;
        display: flex; align-items: flex-start; gap: .75rem;
    }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .dark .alert-success { background: #064e3b; color: #a7f3d0; }
    .dark .alert-error   { background: #7f1d1d; color: #fca5a5; }
    .tab-nav {
        display: flex; gap: .5rem; margin-bottom: 1.5rem;
        border-bottom: 2px solid #e5e7eb;
    }
    .dark .tab-nav { border-color: #374151; }
    .tab-btn {
        padding: .75rem 1.25rem; font-weight: 500; font-size: .875rem;
        color: #6b7280; border: none; background: none; cursor: pointer;
        border-bottom: 2px solid transparent; margin-bottom: -2px;
        transition: color .2s, border-color .2s;
    }
    .tab-btn.active { color: #059669; border-bottom-color: #059669; }
    .tab-btn:hover { color: #374151; }
    .dark .tab-btn { color: #9ca3af; }
    .dark .tab-btn.active { color: #34d399; border-bottom-color: #34d399; }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }
    .badge-status {
        display: inline-flex; align-items: center; gap: .375rem;
        padding: .25rem .75rem; border-radius: 9999px;
        font-size: .75rem; font-weight: 600;
    }
    .badge-verified  { background: #d1fae5; color: #065f46; }
    .badge-unverified{ background: #fef3c7; color: #92400e; }
    .badge-active    { background: #dbeafe; color: #1e40af; }
    .badge-inactive  { background: #fee2e2; color: #991b1b; }
</style>
@endpush

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl  = $locale === 'ar';
    $initials = strtoupper(substr($user->first_name ?? $user->name ?? 'C', 0, 1) . substr($user->name ?? '', 0, 1));
@endphp
<div class="profile-container" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">

    {{-- ── Header ── --}}
    <div class="acct-header">
        <div style="display:flex;align-items:center;gap:1.5rem;position:relative;z-index:1">
            <div class="acct-avatar">{{ $initials }}</div>
            <div>
                <h1 style="font-size:1.5rem;font-weight:700;margin:0 0 .25rem">
                    {{ $user->getFullNameAttribute() ?: ($user->name ?? $user->email) }}
                </h1>
                <p style="opacity:.85;margin:0;font-size:.9rem">{{ $user->email }}</p>
                <div style="display:flex;gap:.5rem;margin-top:.75rem;flex-wrap:wrap">
                    @if($user instanceof \App\Models\ClientUser)
                        <span class="badge-status {{ $user->isVerified() ? 'badge-verified' : 'badge-unverified' }}">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                            {{ $user->isVerified() ? __('messages.verified') : __('messages.not_verified') }}
                        </span>
                        <span class="badge-status {{ $user->isActive() ? 'badge-active' : 'badge-inactive' }}">
                            {{ $user->isActive() ? __('messages.active') : __('messages.inactive') }}
                        </span>
                    @endif
                    <span class="badge-status" style="background:rgba(255,255,255,.2);color:white">
                        {{ $wallet->getFormattedBalance() }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Flash alerts ── --}}
    @if(session('success'))
        <div class="alert alert-success">
            <svg style="width:20px;height:20px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            <svg style="width:20px;height:20px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── Tab navigation ── --}}
    <div class="tab-nav">
        <button class="tab-btn active" data-tab="personal">
            <svg style="width:16px;height:16px;display:inline;margin-right:6px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            {{ __('messages.personal_info') }}
        </button>
        <button class="tab-btn" data-tab="password">
            <svg style="width:16px;height:16px;display:inline;margin-right:6px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            {{ __('messages.change_password') }}
        </button>
        <button class="tab-btn" data-tab="preferences">
            <svg style="width:16px;height:16px;display:inline;margin-right:6px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            {{ __('messages.preferences') }}
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- TAB: Personal information                                       --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="tab-panel active" id="tab-personal">
        <div class="acct-card">
            <div class="acct-card-header">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                {{ __('messages.personal_info') }}
            </div>
            <div class="acct-card-body">
                <form method="POST" action="{{ route('client.account.profile.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="field-grid">
                        <div>
                            <label class="field-label">{{ __('messages.first_name') }}</label>
                            <input type="text" name="first_name" class="field-input"
                                   value="{{ old('first_name', $user->first_name) }}"
                                   placeholder="{{ __('messages.first_name') }}">
                        </div>
                        <div>
                            <label class="field-label">{{ __('messages.last_name') }} <span style="color:#ef4444">*</span></label>
                            <input type="text" name="name" class="field-input" required
                                   value="{{ old('name', $user->name) }}"
                                   placeholder="{{ __('messages.last_name') }}">
                        </div>
                        <div>
                            <label class="field-label">{{ __('messages.email') }} <span style="color:#ef4444">*</span></label>
                            <input type="email" name="email" class="field-input" required
                                   value="{{ old('email', $user->email) }}"
                                   placeholder="email@exemple.com">
                        </div>
                        <div>
                            <label class="field-label">{{ __('messages.phone') }}</label>
                            <input type="tel" name="phone" class="field-input"
                                   value="{{ old('phone', $user->phone) }}"
                                   placeholder="+212 6 00 00 00 00">
                        </div>
                        <div>
                            <label class="field-label">{{ __('messages.address') }}</label>
                            <input type="text" name="address" class="field-input"
                                   value="{{ old('address', $user->address) }}"
                                   placeholder="{{ __('messages.address') }}">
                        </div>
                        <div>
                            <label class="field-label">{{ __('messages.city') }}</label>
                            <input type="text" name="city" class="field-input"
                                   value="{{ old('city', $user->city) }}"
                                   placeholder="{{ __('messages.city') }}">
                        </div>
                        <div>
                            <label class="field-label">{{ __('messages.postal_code') }}</label>
                            <input type="text" name="postal_code" class="field-input"
                                   value="{{ old('postal_code', $user->postal_code) }}"
                                   placeholder="10000">
                        </div>
                        <div>
                            <label class="field-label">{{ __('messages.country') }}</label>
                            <input type="text" name="country" class="field-input"
                                   value="{{ old('country', $user->country) }}"
                                   placeholder="{{ __('messages.country') }}">
                        </div>
                    </div>

                    <div style="margin-top:1.5rem;display:flex;justify-content:flex-end">
                        <button type="submit" class="btn-primary">
                            <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('messages.save_changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Quick links --}}
        <div style="display:flex;gap:1rem;flex-wrap:wrap">
            <a href="{{ route('client.account.vehicles') }}"
               style="display:inline-flex;align-items:center;gap:.5rem;padding:.6875rem 1.25rem;border-radius:.5rem;background:#f3f4f6;color:#374151;font-weight:500;font-size:.875rem;text-decoration:none;transition:background .2s"
               onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                <svg style="width:18px;height:18px;color:#059669" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h8l2-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 8h3l3 4v4h-6V8z"/></svg>
                {{ __('messages.my_vehicles') }}
            </a>
            <a href="{{ route('reservations.index') }}"
               style="display:inline-flex;align-items:center;gap:.5rem;padding:.6875rem 1.25rem;border-radius:.5rem;background:#f3f4f6;color:#374151;font-weight:500;font-size:.875rem;text-decoration:none;transition:background .2s"
               onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                <svg style="width:18px;height:18px;color:#059669" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ __('messages.my_reservations') }}
            </a>
            <a href="{{ route('balances.index') }}"
               style="display:inline-flex;align-items:center;gap:.5rem;padding:.6875rem 1.25rem;border-radius:.5rem;background:#f3f4f6;color:#374151;font-weight:500;font-size:.875rem;text-decoration:none;transition:background .2s"
               onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                <svg style="width:18px;height:18px;color:#059669" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                {{ __('messages.my_balance') }}
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- TAB: Change password                                            --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="tab-panel" id="tab-password">
        <div class="acct-card">
            <div class="acct-card-header">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                {{ __('messages.change_password') }}
            </div>
            <div class="acct-card-body">
                <form method="POST" action="{{ route('client.account.password.change') }}" id="password-form">
                    @csrf

                    <div style="max-width:480px">
                        <div class="profile-field" style="margin-bottom:1.25rem">
                            <label class="field-label">{{ __('messages.current_password') }} <span style="color:#ef4444">*</span></label>
                            <div style="position:relative">
                                <input type="password" name="current_password" id="current_password" class="field-input"
                                       required autocomplete="current-password"
                                       placeholder="{{ __('messages.current_password') }}">
                                <button type="button" class="pwd-toggle" data-target="current_password"
                                        style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af">
                                    <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="profile-field" style="margin-bottom:1.25rem">
                            <label class="field-label">{{ __('messages.new_password') }} <span style="color:#ef4444">*</span></label>
                            <div style="position:relative">
                                <input type="password" name="password" id="new_password" class="field-input"
                                       required autocomplete="new-password" minlength="8"
                                       placeholder="{{ __('messages.new_password') }}">
                                <button type="button" class="pwd-toggle" data-target="new_password"
                                        style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af">
                                    <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                            <p style="font-size:.75rem;color:#9ca3af;margin-top:.4rem">{{ __('messages.password_min_length') }}</p>
                        </div>

                        <div class="profile-field" style="margin-bottom:1.5rem">
                            <label class="field-label">{{ __('messages.confirm_password') }} <span style="color:#ef4444">*</span></label>
                            <div style="position:relative">
                                <input type="password" name="password_confirmation" id="confirm_password" class="field-input"
                                       required autocomplete="new-password"
                                       placeholder="{{ __('messages.confirm_password') }}">
                                <button type="button" class="pwd-toggle" data-target="confirm_password"
                                        style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af">
                                    <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary">
                            <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            {{ __('messages.change_password') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- TAB: Preferences                                                --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="tab-panel" id="tab-preferences">
        <div class="acct-card">
            <div class="acct-card-header">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ __('messages.preferences') }}
            </div>
            <div class="acct-card-body">
                <form method="POST" action="{{ route('client.account.profile.update') }}">
                    @csrf
                    @method('PATCH')
                    {{-- Keep existing values unchanged --}}
                    <input type="hidden" name="name"       value="{{ $user->name }}">
                    <input type="hidden" name="email"      value="{{ $user->email }}">
                    <input type="hidden" name="first_name" value="{{ $user->first_name }}">
                    <input type="hidden" name="phone"      value="{{ $user->phone }}">
                    <input type="hidden" name="address"    value="{{ $user->address }}">
                    <input type="hidden" name="city"       value="{{ $user->city }}">
                    <input type="hidden" name="postal_code"value="{{ $user->postal_code }}">
                    <input type="hidden" name="country"    value="{{ $user->country }}">

                    <div style="max-width:360px">
                        <div style="margin-bottom:1.25rem">
                            <label class="field-label">{{ __('messages.language') }}</label>
                            <select name="language" class="field-input field-select">
                                <option value="fr" {{ ($user->language ?? 'fr') === 'fr' ? 'selected' : '' }}>🇫🇷 Français</option>
                                <option value="en" {{ ($user->language ?? 'fr') === 'en' ? 'selected' : '' }}>🇬🇧 English</option>
                                <option value="ar" {{ ($user->language ?? 'fr') === 'ar' ? 'selected' : '' }}>🇲🇦 العربية</option>
                            </select>
                        </div>

                        {{-- Account info (read-only) --}}
                        @if($user instanceof \App\Models\ClientUser && $user->last_login_at)
                        <div style="margin-top:1.5rem;padding:1rem;background:#f9fafb;border-radius:.5rem">
                            <p style="font-size:.8125rem;color:#6b7280;margin:0 0 .25rem">{{ __('messages.last_login') }}</p>
                            <p style="font-size:.9375rem;font-weight:500;color:#111827;margin:0">
                                {{ $user->last_login_at->diffForHumans() }}
                            </p>
                        </div>
                        @endif

                        <div style="margin-top:1.5rem">
                            <button type="submit" class="btn-primary">
                                <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ __('messages.save_changes') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
// ── Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});

// ── Open password tab if there are password errors
@if($errors->hasAny(['current_password','password','password_confirmation']))
document.querySelector('[data-tab="password"]').click();
@endif

// ── Password visibility toggle
document.querySelectorAll('.pwd-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        input.type = input.type === 'password' ? 'text' : 'password';
    });
});
</script>
@endpush

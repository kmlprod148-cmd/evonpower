@extends('layouts.app')

@section('title', __('messages.my_vehicles'))
@section('page-title', __('messages.my_vehicles'))

@push('styles')
<style>
    .veh-header {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        border-radius: 1rem;
        padding: 1.75rem 2rem;
        color: white;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .btn-white {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .625rem 1.25rem; border-radius: .5rem;
        background: white; color: #059669; font-weight: 600;
        font-size: .875rem; border: none; cursor: pointer;
        transition: transform .15s, box-shadow .15s;
        text-decoration: none;
    }
    .btn-white:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.15); }
    .btn-primary {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .6875rem 1.5rem; border-radius: .5rem;
        background: #059669; color: white; font-weight: 600;
        font-size: .875rem; border: none; cursor: pointer;
        transition: background .2s, transform .1s;
    }
    .btn-primary:hover { background: #047857; transform: translateY(-1px); }
    .btn-sm {
        padding: .4375rem .875rem; font-size: .8125rem;
        display: inline-flex; align-items: center; gap: .375rem;
        border-radius: .375rem; font-weight: 500; cursor: pointer;
        border: none; transition: background .2s;
    }
    .btn-edit   { background: #dbeafe; color: #1e40af; }
    .btn-edit:hover   { background: #bfdbfe; }
    .btn-danger { background: #fee2e2; color: #991b1b; }
    .btn-danger:hover { background: #fecaca; }
    .btn-star   { background: #fef3c7; color: #92400e; }
    .btn-star:hover   { background: #fde68a; }
    .btn-star-on{ background: #d1fae5; color: #065f46; }
    .btn-star-on:hover{ background: #a7f3d0; }
    .veh-card {
        background: white; border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
        overflow: hidden; transition: transform .2s, box-shadow .2s;
        display: flex; flex-direction: column;
    }
    .dark .veh-card { background: #1f2937; }
    .veh-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px -4px rgba(0,0,0,.12); }
    .veh-card-top {
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        border-bottom: 1px solid #d1fae5;
        position: relative;
    }
    .dark .veh-card-top { background: linear-gradient(135deg, #064e3b, #065f46); border-color: #047857; }
    .veh-icon {
        width: 52px; height: 52px; border-radius: .75rem;
        background: #059669; color: white;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: .75rem;
    }
    .primary-badge {
        position: absolute; top: 1rem; right: 1rem;
        background: #059669; color: white;
        padding: .25rem .75rem; border-radius: 9999px;
        font-size: .75rem; font-weight: 600;
        display: flex; align-items: center; gap: .375rem;
    }
    .veh-card-body { padding: 1.25rem 1.5rem; flex: 1; }
    .veh-info-row {
        display: flex; align-items: center; gap: .5rem;
        font-size: .875rem; color: #6b7280; margin-bottom: .5rem;
    }
    .dark .veh-info-row { color: #9ca3af; }
    .veh-info-row svg { width: 16px; height: 16px; flex-shrink: 0; color: #059669; }
    .veh-info-row span { color: #111827; font-weight: 500; }
    .dark .veh-info-row span { color: #f3f4f6; }
    .veh-card-footer {
        padding: 1rem 1.5rem;
        background: #f9fafb;
        border-top: 1px solid #f3f4f6;
        display: flex; gap: .5rem; flex-wrap: wrap;
    }
    .dark .veh-card-footer { background: #111827; border-color: #374151; }
    .veh-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }
    .empty-state {
        text-align: center; padding: 4rem 2rem;
        background: white; border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
    }
    .dark .empty-state { background: #1f2937; }
    .empty-icon {
        width: 80px; height: 80px; border-radius: 50%;
        background: #f3f4f6; display: flex; align-items: center;
        justify-content: center; margin: 0 auto 1rem;
    }
    .modal-backdrop {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.5); z-index: 50;
        align-items: center; justify-content: center;
    }
    .modal-backdrop.open { display: flex; }
    .modal {
        background: white; border-radius: 1rem;
        width: 100%; max-width: 560px;
        max-height: 90vh; overflow-y: auto;
        box-shadow: 0 20px 60px -10px rgba(0,0,0,.3);
        animation: modal-in .2s ease;
    }
    .dark .modal { background: #1f2937; }
    @keyframes modal-in { from { opacity:0; transform:scale(.95) translateY(10px); } }
    .modal-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f3f4f6;
        display: flex; align-items: center; justify-content: space-between;
        font-weight: 600; font-size: 1rem;
    }
    .dark .modal-header { border-color: #374151; color: #f9fafb; }
    .modal-body { padding: 1.5rem; }
    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #f3f4f6;
        display: flex; justify-content: flex-end; gap: .75rem;
    }
    .dark .modal-footer { border-color: #374151; }
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
        box-sizing: border-box;
    }
    .dark .field-input { background: #374151; border-color: #4b5563; color: #f9fafb; }
    .field-input:focus {
        outline: none; border-color: #059669; background: white;
        box-shadow: 0 0 0 3px rgba(5,150,105,.1);
    }
    .dark .field-input:focus { background: #1f2937; }
    .field-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236b7280'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z' clip-rule='evenodd'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right .75rem center; background-size: 1.25rem; padding-right: 2.5rem; }
    .field-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .alert {
        padding: .875rem 1rem; border-radius: .5rem;
        margin-bottom: 1rem; font-size: .9rem;
        display: flex; align-items: flex-start; gap: .75rem;
    }
    .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .btn-close {
        background: none; border: none; cursor: pointer;
        color: #9ca3af; padding: .25rem;
        border-radius: .25rem; transition: color .2s;
    }
    .btn-close:hover { color: #374151; }
</style>
@endpush

@section('content')
@php
    $connectorTypes = \App\Models\Vehicle::connectorTypes();
@endphp

{{-- ── Header ── --}}
<div class="veh-header">
    <div>
        <h1 style="font-size:1.375rem;font-weight:700;margin:0 0 .25rem">{{ __('messages.my_vehicles') }}</h1>
        <p style="opacity:.85;margin:0;font-size:.875rem">
            {{ $vehicles->count() }} {{ __('messages.vehicles_registered') }}
        </p>
    </div>
    <div style="display:flex;gap:.75rem;align-items:center">
        <a href="{{ route('client.account.profile') }}" class="btn-white" style="color:#059669">
            <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('messages.back_to_profile') }}
        </a>
        <button type="button" class="btn-white" onclick="openAddModal()">
            <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('messages.add_vehicle') }}
        </button>
    </div>
</div>

{{-- ── Flash alerts ── --}}
@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem">
        <svg style="width:20px;height:20px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div class="alert alert-error" style="margin-bottom:1rem">
        <svg style="width:20px;height:20px;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>@foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach</div>
    </div>
@endif

{{-- ── Vehicle grid ── --}}
@if($vehicles->isEmpty())
    <div class="empty-state">
        <div class="empty-icon">
            <svg style="width:36px;height:36px;color:#9ca3af" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h8l2-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 8h3l3 4v4h-6V8z"/></svg>
        </div>
        <h3 style="font-size:1.125rem;font-weight:600;color:#374151;margin:0 0 .5rem">{{ __('messages.no_vehicles') }}</h3>
        <p style="color:#6b7280;margin:0 0 1.5rem;font-size:.9rem">{{ __('messages.add_first_vehicle') }}</p>
        <button type="button" class="btn-primary" onclick="openAddModal()">
            <svg style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('messages.add_vehicle') }}
        </button>
    </div>
@else
    <div class="veh-grid">
        @foreach($vehicles as $vehicle)
        <div class="veh-card">
            <div class="veh-card-top">
                @if($vehicle->is_primary)
                    <div class="primary-badge">
                        <svg style="width:12px;height:12px" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        {{ __('messages.primary') }}
                    </div>
                @endif
                <div class="veh-icon">
                    <svg style="width:28px;height:28px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2 2h8l2-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 8h3l3 4v4h-6V8z"/></svg>
                </div>
                <h3 style="font-size:1.0625rem;font-weight:700;color:#065f46;margin:0">
                    {{ $vehicle->make }} {{ $vehicle->model }}
                </h3>
                @if($vehicle->year)
                    <p style="font-size:.8125rem;color:#059669;margin:.125rem 0 0">{{ $vehicle->year }}</p>
                @endif
            </div>
            <div class="veh-card-body">
                @if($vehicle->registration)
                <div class="veh-info-row">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    <span>{{ $vehicle->registration }}</span>
                </div>
                @endif
                @if($vehicle->connector_type)
                <div class="veh-info-row">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>{{ $vehicle->connector_type }}</span>
                </div>
                @endif
                @if($vehicle->battery_capacity)
                <div class="veh-info-row">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                    <span>{{ $vehicle->battery_capacity }} kWh</span>
                </div>
                @endif
                @if($vehicle->color)
                <div class="veh-info-row">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                    <span>{{ $vehicle->color }}</span>
                </div>
                @endif
            </div>
            <div class="veh-card-footer">
                {{-- Set primary --}}
                @unless($vehicle->is_primary)
                <form method="POST" action="{{ route('client.account.vehicles.primary', $vehicle) }}">
                    @csrf
                    <button type="submit" class="btn-sm btn-star">
                        <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        {{ __('messages.set_primary') }}
                    </button>
                </form>
                @else
                <span class="btn-sm btn-star-on" style="cursor:default">
                    <svg style="width:14px;height:14px" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    {{ __('messages.primary') }}
                </span>
                @endunless

                {{-- Edit --}}
                <button type="button" class="btn-sm btn-edit"
                        onclick="openEditModal({{ $vehicle->id }}, {{ json_encode($vehicle->only(['make','model','registration','year','color','battery_capacity','connector_type','vin','notes'])) }})">
                    <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    {{ __('messages.edit') }}
                </button>

                {{-- Delete --}}
                <form method="POST" action="{{ route('client.account.vehicles.destroy', $vehicle) }}"
                      onsubmit="return confirm('{{ __('messages.confirm_delete_vehicle') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-sm btn-danger">
                        <svg style="width:14px;height:14px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        {{ __('messages.delete') }}
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
@endif

{{-- ═══════════════════════════ ADD MODAL ═══════════════════════════ --}}
<div class="modal-backdrop" id="add-modal">
    <div class="modal">
        <div class="modal-header">
            <span>{{ __('messages.add_vehicle') }}</span>
            <button class="btn-close" onclick="closeModal('add-modal')">
                <svg style="width:20px;height:20px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('client.account.vehicles.store') }}">
            @csrf
            <div class="modal-body">
                <div class="field-grid-2" style="margin-bottom:1rem">
                    <div>
                        <label class="field-label">{{ __('messages.make') }} <span style="color:#ef4444">*</span></label>
                        <input type="text" name="make" class="field-input" required
                               placeholder="Tesla, Renault…" value="{{ old('make') }}">
                    </div>
                    <div>
                        <label class="field-label">{{ __('messages.model') }} <span style="color:#ef4444">*</span></label>
                        <input type="text" name="model" class="field-input" required
                               placeholder="Model 3, Zoe…" value="{{ old('model') }}">
                    </div>
                </div>
                <div class="field-grid-2" style="margin-bottom:1rem">
                    <div>
                        <label class="field-label">{{ __('messages.registration') }}</label>
                        <input type="text" name="registration" class="field-input"
                               placeholder="AB-123-CD" value="{{ old('registration') }}">
                    </div>
                    <div>
                        <label class="field-label">{{ __('messages.year') }}</label>
                        <input type="number" name="year" class="field-input"
                               min="1990" max="{{ date('Y') + 1 }}" placeholder="{{ date('Y') }}"
                               value="{{ old('year') }}">
                    </div>
                </div>
                <div class="field-grid-2" style="margin-bottom:1rem">
                    <div>
                        <label class="field-label">{{ __('messages.color') }}</label>
                        <input type="text" name="color" class="field-input"
                               placeholder="{{ __('messages.color') }}" value="{{ old('color') }}">
                    </div>
                    <div>
                        <label class="field-label">{{ __('messages.battery_capacity_kwh') }}</label>
                        <input type="number" name="battery_capacity" class="field-input"
                               step="0.1" min="0" max="500" placeholder="75"
                               value="{{ old('battery_capacity') }}">
                    </div>
                </div>
                <div style="margin-bottom:1rem">
                    <label class="field-label">{{ __('messages.connector_type') }}</label>
                    <select name="connector_type" class="field-input field-select">
                        <option value="">— {{ __('messages.select') }} —</option>
                        @foreach($connectorTypes as $key => $label)
                            <option value="{{ $key }}" {{ old('connector_type') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom:1rem">
                    <label class="field-label">{{ __('messages.vin') }}</label>
                    <input type="text" name="vin" class="field-input" maxlength="17"
                           placeholder="VIN 17 caractères" value="{{ old('vin') }}">
                </div>
                <div>
                    <label class="field-label">{{ __('messages.notes') }}</label>
                    <textarea name="notes" class="field-input" rows="2"
                              placeholder="{{ __('messages.optional_notes') }}" style="resize:vertical">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm btn-danger" onclick="closeModal('add-modal')">
                    {{ __('messages.cancel') }}
                </button>
                <button type="submit" class="btn-primary">
                    <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('messages.add_vehicle') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════════════════ EDIT MODAL ══════════════════════════ --}}
<div class="modal-backdrop" id="edit-modal">
    <div class="modal">
        <div class="modal-header">
            <span>{{ __('messages.edit_vehicle') }}</span>
            <button class="btn-close" onclick="closeModal('edit-modal')">
                <svg style="width:20px;height:20px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" id="edit-form" action="">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="field-grid-2" style="margin-bottom:1rem">
                    <div>
                        <label class="field-label">{{ __('messages.make') }} <span style="color:#ef4444">*</span></label>
                        <input type="text" name="make" id="edit-make" class="field-input" required>
                    </div>
                    <div>
                        <label class="field-label">{{ __('messages.model') }} <span style="color:#ef4444">*</span></label>
                        <input type="text" name="model" id="edit-model" class="field-input" required>
                    </div>
                </div>
                <div class="field-grid-2" style="margin-bottom:1rem">
                    <div>
                        <label class="field-label">{{ __('messages.registration') }}</label>
                        <input type="text" name="registration" id="edit-registration" class="field-input">
                    </div>
                    <div>
                        <label class="field-label">{{ __('messages.year') }}</label>
                        <input type="number" name="year" id="edit-year" class="field-input"
                               min="1990" max="{{ date('Y') + 1 }}">
                    </div>
                </div>
                <div class="field-grid-2" style="margin-bottom:1rem">
                    <div>
                        <label class="field-label">{{ __('messages.color') }}</label>
                        <input type="text" name="color" id="edit-color" class="field-input">
                    </div>
                    <div>
                        <label class="field-label">{{ __('messages.battery_capacity_kwh') }}</label>
                        <input type="number" name="battery_capacity" id="edit-battery" class="field-input" step="0.1" min="0" max="500">
                    </div>
                </div>
                <div style="margin-bottom:1rem">
                    <label class="field-label">{{ __('messages.connector_type') }}</label>
                    <select name="connector_type" id="edit-connector" class="field-input field-select">
                        <option value="">— {{ __('messages.select') }} —</option>
                        @foreach($connectorTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom:1rem">
                    <label class="field-label">{{ __('messages.vin') }}</label>
                    <input type="text" name="vin" id="edit-vin" class="field-input" maxlength="17">
                </div>
                <div>
                    <label class="field-label">{{ __('messages.notes') }}</label>
                    <textarea name="notes" id="edit-notes" class="field-input" rows="2" style="resize:vertical"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm btn-danger" onclick="closeModal('edit-modal')">
                    {{ __('messages.cancel') }}
                </button>
                <button type="submit" class="btn-primary">
                    <svg style="width:16px;height:16px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('messages.save_changes') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const BASE_UPDATE_URL = '{{ url('account/vehicles') }}/';

function openAddModal() {
    document.getElementById('add-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function openEditModal(id, data) {
    document.getElementById('edit-form').action = BASE_UPDATE_URL + id;
    document.getElementById('edit-make').value         = data.make         ?? '';
    document.getElementById('edit-model').value        = data.model        ?? '';
    document.getElementById('edit-registration').value = data.registration ?? '';
    document.getElementById('edit-year').value         = data.year         ?? '';
    document.getElementById('edit-color').value        = data.color        ?? '';
    document.getElementById('edit-battery').value      = data.battery_capacity ?? '';
    document.getElementById('edit-connector').value    = data.connector_type   ?? '';
    document.getElementById('edit-vin').value          = data.vin          ?? '';
    document.getElementById('edit-notes').value        = data.notes        ?? '';
    document.getElementById('edit-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}

// Close on backdrop click
document.querySelectorAll('.modal-backdrop').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) closeModal(el.id); });
});

// Open add modal if validation errors exist (from a failed store attempt)
@if($errors->any() && old('make'))
openAddModal();
@endif
</script>
@endpush

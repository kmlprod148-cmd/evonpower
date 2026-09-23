@props([
    'chargingPoint',
    'connectors' => [],
    'showReset' => true,
    'showClearCache' => true,
])

@php
    $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;
    $isConfigured = !empty($chargeBoxId);
@endphp

<div 
    class="ocpp-control-panel card"
    data-ocpp-control-panel
    data-charging-point-id="{{ $chargingPoint->id }}"
    {{ $attributes }}
>
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <svg class="me-2" style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            {{ __('messages.ocpp_control') }}
        </h5>
        
        @if($isConfigured)
            <span class="badge bg-success">
                <svg style="width: 12px; height: 12px;" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="4"></circle>
                </svg>
                {{ __('messages.connected') }}
            </span>
        @else
            <span class="badge bg-warning">{{ __('messages.not_configured') }}</span>
        @endif
    </div>

    <div class="card-body">
        @if(!$isConfigured)
            <div class="alert alert-warning mb-0">
                <svg class="me-2" style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                {{ __('messages.charging_point_not_configured_for_steve') }}
            </div>
        @else
            {{-- Charge Point Level Controls --}}
            <div class="mb-4">
                <h6 class="text-muted mb-3">{{ __('messages.charge_point_operations') }}</h6>
                
                <div class="d-flex flex-wrap gap-2">
                    {{-- Enable/Disable Charge Point --}}
                    <x-ocpp.availability-toggle 
                        :charging-point-id="$chargingPoint->id"
                        :connector-id="0"
                        :current-status="$chargingPoint->status === 'online' ? 'Operative' : 'Inoperative'"
                        :show-label="false"
                    />

                    @if($showReset)
                        {{-- Soft Reset --}}
                        <button 
                            type="button"
                            class="btn btn-outline-warning"
                            data-action="reset"
                            data-hard="false"
                            title="{{ __('messages.soft_reset') }}"
                        >
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span class="ms-1">{{ __('messages.soft_reset') }}</span>
                        </button>

                        {{-- Hard Reset --}}
                        <button 
                            type="button"
                            class="btn btn-outline-danger"
                            data-action="reset"
                            data-hard="true"
                            title="{{ __('messages.hard_reset') }}"
                        >
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span class="ms-1">{{ __('messages.hard_reset') }}</span>
                        </button>
                    @endif

                    @if($showClearCache)
                        {{-- Clear Cache --}}
                        <button 
                            type="button"
                            class="btn btn-outline-secondary"
                            data-action="clear-cache"
                            title="{{ __('messages.clear_cache') }}"
                        >
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <span class="ms-1">{{ __('messages.clear_cache') }}</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Connector Level Controls --}}
            @if(count($connectors) > 0)
                <div>
                    <h6 class="text-muted mb-3">{{ __('messages.connector_operations') }}</h6>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.connector') }}</th>
                                    <th>{{ __('messages.type') }}</th>
                                    <th>{{ __('messages.status') }}</th>
                                    <th>{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($connectors as $connector)
                                    @php
                                        $connectorStatus = $connector->status ?? 'Available';
                                        $isAvailable = in_array($connectorStatus, ['Available', 'Operative']);
                                        $ocppStatus = $isAvailable ? 'Operative' : 'Inoperative';
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-medium">#{{ $connector->connector_id }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $connector->type ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span 
                                                class="badge {{ $isAvailable ? 'bg-success' : 'bg-secondary' }}"
                                                data-connector-status="{{ $connector->connector_id }}"
                                            >
                                                {{ $connector->status_label ?? $connectorStatus }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <x-ocpp.availability-toggle 
                                                    :charging-point-id="$chargingPoint->id"
                                                    :connector-id="$connector->connector_id"
                                                    :current-status="$ocppStatus"
                                                    size="sm"
                                                    :show-label="false"
                                                />
                                                
                                                <button 
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    data-action="unlock"
                                                    data-connector-id="{{ $connector->connector_id }}"
                                                    title="{{ __('messages.unlock_connector') }}"
                                                >
                                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="alert alert-info mb-0">
                    {{ __('messages.no_connectors_found') }}
                    <a href="#" class="alert-link" onclick="document.querySelector('[data-action=sync-connectors]')?.click()">
                        {{ __('messages.sync_connectors') }}
                    </a>
                </div>
            @endif
        @endif
    </div>
</div>

@pushOnce('scripts')
<script src="{{ asset('js/ocpp-operations.js') }}"></script>
@endPushOnce

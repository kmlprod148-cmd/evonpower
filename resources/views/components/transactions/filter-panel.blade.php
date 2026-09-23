@props([
    'filters' => [],
    'transactionTypes' => [],
    'users' => [],
    'chargingPoints' => [],
    'paymentMethods' => []
])

<div class="card border-0 shadow-sm mb-6">
    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-filter me-2 text-primary"></i>{{ __('Filters') }}
            </h5>
            <button type="button" class="btn btn-sm btn-ghost-primary" data-bs-toggle="collapse"
                    data-bs-target="#filterPanel" aria-controls="filterPanel" aria-expanded="true"
                    aria-label="{{ __('Toggle Filter Panel') }}">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
    </div>
    <div class="collapse show" id="filterPanel">
        <div class="card-body">
            <form method="GET" action="{{ route('transactions.index') }}" id="filterForm">
                @csrf
                <div class="row g-3">
                    {{-- Search --}}
                    <div class="col-lg-4 col-md-6">
                        <label for="search" class="form-label small fw-semibold text-uppercase text-muted">{{ __('Search') }}</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" id="search"
                                   class="form-control form-control-sm" placeholder="{{ __('Search by ID, user, or charging point...') }}"
                                   value="{{ $filters['search'] }}">
                            <button type="button" class="btn btn-outline-secondary" id="searchClear" aria-label="{{ __('Clear search') }}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    {{-- User Filter --}}
                    <div class="col-lg-4 col-md-6">
                        <label for="user_id" class="form-label small fw-semibold text-uppercase text-muted">{{ __('User') }}</label>
                        <select name="user_id" id="user_id" class="form-select form-select-sm">
                            <option value="">{{ __('All Users') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected($filters['user_id'] == $user->id)>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Charging Point Filter --}}
                    <div class="col-lg-4 col-md-6">
                        <label for="charging_point_id" class="form-label small fw-semibold text-uppercase text-muted">{{ __('Charging Point') }}</label>
                        <select name="charging_point_id" id="charging_point_id" class="form-select form-select-sm">
                            <option value="">{{ __('All Charging Points') }}</option>
                            @foreach($chargingPoints as $cp)
                                <option value="{{ $cp->id }}" @selected($filters['charging_point_id'] == $cp->id)>
                                    {{ $cp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status Filter --}}
                    <div class="col-lg-3 col-md-6">
                        <label for="status" class="form-label small fw-semibold text-uppercase text-muted">{{ __('Status') }}</label>
                        <select name="status" id="status" class="form-select form-select-sm">
                            <option value="">{{ __('All Status') }}</option>
                            @foreach($transactionTypes as $type)
                                <option value="{{ $type }}" @selected($filters['status'] == $type)>
                                    {{ __(ucfirst($type)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Payment Method Filter --}}
                    <div class="col-lg-3 col-md-6">
                        <label for="payment_method" class="form-label small fw-semibold text-uppercase text-muted">{{ __('Payment Method') }}</label>
                        <select name="payment_method" id="payment_method" class="form-select form-select-sm">
                            <option value="">{{ __('All Payment Methods') }}</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method }}" @selected($filters['payment_method'] == $method)>
                                    {{ __(ucfirst($method)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div class="col-lg-3 col-md-6">
                        <label for="date_from" class="form-label small fw-semibold text-uppercase text-muted">{{ __('From Date') }}</label>
                        <input type="date" name="date_from" id="date_from"
                               class="form-control form-control-sm" value="{{ $filters['date_from'] }}">
                    </div>

                    {{-- Date To --}}
                    <div class="col-lg-3 col-md-6">
                        <label for="date_to" class="form-label small fw-semibold text-uppercase text-muted">{{ __('To Date') }}</label>
                        <input type="date" name="date_to" id="date_to"
                               class="form-control form-control-sm" value="{{ $filters['date_to'] }}">
                        <div id="dateRangeFeedback" class="invalid-feedback" style="display:none;">
                            {{ __('"From Date" cannot be after "To Date".') }}
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @php
                                $quickRanges = [
                                    'today' => __('Today'),
                                    '7' => __('Last 7 days'),
                                    '30' => __('Last 30 days'),
                                ];
                            @endphp
                            @foreach($quickRanges as $range => $label)
                                <button type="button" class="btn btn-outline-secondary btn-xs quick-range" data-range="{{ $range }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-2"></i>{{ __('Apply Filters') }}
                            </button>
                            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-2"></i>{{ __('Clear All') }}
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="transaction-history">
    <!-- En-tête avec statistiques -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Historique des Transactions
                    </h5>
                    <div class="btn-group">
                        <button wire:click="exportCsv" 
                                class="btn btn-outline-success btn-sm" 
                                @if($isExporting) disabled @endif
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="exportCsv">
                                <i class="fas fa-download me-1"></i> CSV
                            </span>
                            <span wire:loading wire:target="exportCsv">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Export...
                            </span>
                        </button>
                        <button wire:click="exportExcel" 
                                class="btn btn-outline-primary btn-sm"
                                @if($isExporting) disabled @endif
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="exportExcel">
                                <i class="fas fa-file-excel me-1"></i> Excel
                            </span>
                            <span wire:loading wire:target="exportExcel">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Export...
                            </span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Statistiques rapides -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <div class="stat-content">
                                    <h6 class="stat-title">Total Transactions</h6>
                                    <h4 class="stat-value">{{ number_format($filterStats['total_transactions'] ?? 0) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-euro-sign"></i>
                                </div>
                                <div class="stat-content">
                                    <h6 class="stat-title">Montant Total</h6>
                                    <h4 class="stat-value">{{ number_format($filterStats['total_amount'] ?? 0, 2) }} <small class="text-muted">(Toutes devises)</small></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="stat-content">
                                    <h6 class="stat-title">Frais Totaux</h6>
                                    <h4 class="stat-value">{{ number_format($filterStats['total_fees'] ?? 0, 2) }} <small class="text-muted">(Toutes devises)</small></h4>
                                    @if(isset($filterStats['total_admin_share']) || isset($filterStats['total_integrator_share']))
                                        <small class="text-muted d-block mt-1">
                                            Admin: {{ number_format($filterStats['total_admin_share'] ?? 0, 2) }} | 
                                            Intégrateur: {{ number_format($filterStats['total_integrator_share'] ?? 0, 2) }}
                                        </small>
                                        @if(isset($filterStats['total_operator_share']) && $filterStats['total_operator_share'] > 0)
                                        <small class="text-muted d-block">
                                            Opérateur: {{ number_format($filterStats['total_operator_share'] ?? 0, 2) }}
                                        </small>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="stat-icon bg-info">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="stat-content">
                                    <h6 class="stat-title">Moyenne</h6>
                                    <h4 class="stat-value">{{ number_format(($filterStats['total_amount'] ?? 0) / max(1, $filterStats['total_transactions'] ?? 1), 2) }} <small class="text-muted">(Toutes devises)</small></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-filter me-2"></i>
                        Filtres
                        <button wire:click="clearFilters" class="btn btn-sm btn-outline-secondary float-end">
                            <i class="fas fa-times me-1"></i> Effacer
                        </button>
                    </h6>
                </div>
                <div class="card-body">
                    @if($isLoading)
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="text-muted mt-2">Chargement des transactions...</p>
                    </div>
                    @endif
                    <div class="row g-3" wire:loading.class="opacity-50" wire:target="render">
                        <!-- Statut -->
                        <div class="col-md-3">
                            <label class="form-label">Statut</label>
                            <select wire:model.live="status" class="form-select" @if($isLoading) disabled @endif>
                                <option value="">Tous les statuts</option>
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Type de transaction -->
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select wire:model.live="type" class="form-select">
                                <option value="">Tous les types</option>
                                @foreach($transactionTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date de début -->
                        <div class="col-md-3">
                            <label class="form-label">Date de début</label>
                            <input wire:model.live="dateFrom" type="date" class="form-control">
                        </div>

                        <!-- Date de fin -->
                        <div class="col-md-3">
                            <label class="form-label">Date de fin</label>
                            <input wire:model.live="dateTo" type="date" class="form-control">
                        </div>

                        <!-- Recherche -->
                        <div class="col-md-4">
                            <label class="form-label">Recherche</label>
                            <div class="input-group">
                                <input wire:model.live.debounce.500ms="search" type="text" class="form-control" placeholder="Référence, description, utilisateur..." @if($isLoading) disabled @endif>
                                @if($search)
                                <button wire:click="$set('search', '')" class="btn btn-outline-secondary" type="button" title="Effacer">
                                    <i class="fas fa-times"></i>
                                </button>
                                @endif
                            </div>
                        </div>

                        <!-- Localisation -->
                        <div class="col-md-4">
                            <label class="form-label">Localisation</label>
                            <input wire:model.live="location" type="text" class="form-control" placeholder="Ville, adresse...">
                        </div>

                        <!-- Devise -->
                        <div class="col-md-4">
                            <label class="form-label">Devise</label>
                            <select wire:model.live="currency" class="form-select">
                                <option value="">Toutes les devises</option>
                                @foreach($currencies as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Montant minimum -->
                        <div class="col-md-4">
                            <label class="form-label">Montant minimum</label>
                            <input wire:model.live="amountMin" type="number" step="0.01" class="form-control" placeholder="0.00">
                        </div>

                        <!-- Montant maximum -->
                        <div class="col-md-4">
                            <label class="form-label">Montant maximum</label>
                            <input wire:model.live="amountMax" type="number" step="0.01" class="form-control" placeholder="999999.99">
                        </div>

                        <!-- Utilisateur -->
                        @if($user->role === 'admin' || $user->role === 'integrator')
                        <div class="col-md-4">
                            <label class="form-label">Utilisateur</label>
                            <select wire:model.live="userId" class="form-select">
                                <option value="">Tous les utilisateurs</option>
                                @foreach($accessibleUsers as $accessibleUser)
                                    <option value="{{ $accessibleUser->id }}">
                                        {{ $accessibleUser->name }} ({{ ucfirst($accessibleUser->role) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <!-- Business Profile -->
                        @if($user->role === 'admin' || $user->role === 'integrator')
                        <div class="col-md-4">
                            <label class="form-label">Business Profile</label>
                            <select wire:model.live="businessProfileId" class="form-select">
                                <option value="">Tous les profils</option>
                                @foreach($accessibleBusinessProfiles as $profile)
                                    <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <!-- Options d'affichage -->
                        <div class="col-md-4">
                            <label class="form-label">Options</label>
                            <div class="form-check">
                                <input wire:model.live="includeFeeDetails" class="form-check-input" type="checkbox" id="includeFeeDetails" checked>
                                <label class="form-check-label" for="includeFeeDetails">
                                    <i class="fas fa-info-circle text-primary me-1" title="Afficher les parts depuis Business Profiles"></i>
                                    Afficher les détails des parts (Business Profiles)
                                </label>
                                <small class="d-block text-muted mt-1">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    Données réelles depuis TransactionDetail
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des transactions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Transactions ({{ $transactions->total() }} résultats)</h6>
                    <div class="d-flex align-items-center">
                        <label class="form-label me-2 mb-0">Par page:</label>
                        <select wire:model.live="perPage" class="form-select form-select-sm" style="width: auto;">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($isLoading && !$transactions->count())
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="text-muted">Chargement des transactions...</p>
                    </div>
                    @endif
                    <div class="table-responsive" wire:loading.class="opacity-50" wire:target="render">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th wire:click="sortBy('created_at')" class="sortable">
                                        Date
                                        @if($sortBy === 'created_at')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @else
                                            <i class="fas fa-sort ms-1 text-muted"></i>
                                        @endif
                                    </th>
                                    <th wire:click="sortBy('transaction_reference')" class="sortable">
                                        Référence
                                        @if($sortBy === 'transaction_reference')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @else
                                            <i class="fas fa-sort ms-1 text-muted"></i>
                                        @endif
                                    </th>
                                    <th wire:click="sortBy('transaction_type')" class="sortable">
                                        Type / Crédit/Débit
                                        @if($sortBy === 'transaction_type')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @else
                                            <i class="fas fa-sort ms-1 text-muted"></i>
                                        @endif
                                    </th>
                                    <th>Parties</th>
                                    <th wire:click="sortBy('amount')" class="sortable">
                                        Montant
                                        @if($sortBy === 'amount')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @else
                                            <i class="fas fa-sort ms-1 text-muted"></i>
                                        @endif
                                    </th>
                                    @if($user->role === 'admin')
                                    <th>Part Admin</th>
                                    @endif
                                    @if($user->role === 'admin' || $user->role === 'integrator')
                                    <th>Part Intégrateur</th>
                                    @endif
                                    <th>Part Opérateur</th>
                                    <th wire:click="sortBy('status')" class="sortable">
                                        Statut
                                        @if($sortBy === 'status')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @else
                                            <i class="fas fa-sort ms-1 text-muted"></i>
                                        @endif
                                    </th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <small class="text-muted">{{ $transaction->created_at->format('d/m/Y') }}</small>
                                                <small>{{ $transaction->created_at->format('H:i') }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="text-primary">{{ $transaction->transaction_reference }}</code>
                                        </td>
                                        <td>
                                            @php
                                                $isReservation = in_array($transaction->transaction_type, ['reservation', 'reservation_payment']);
                                            @endphp
                                            @if($isReservation)
                                                <div class="d-flex flex-column">
                                                    <span class="badge {{ $this->isCreditTransaction($transaction) ? 'bg-success' : 'bg-danger' }}">
                                                        <i class="fas {{ $this->isCreditTransaction($transaction) ? 'fa-arrow-up' : 'fa-arrow-down' }} me-1"></i>
                                                        {{ $this->isCreditTransaction($transaction) ? 'Crédit' : 'Débit' }}
                                                    </span>
                                                    <small class="text-muted mt-1">Réservation</small>
                                                </div>
                                            @else
                                                <span class="badge bg-info">{{ $this->getTransactionTypeLabel($transaction->transaction_type) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="badge {{ $this->getRoleBadge($transaction->sourceUser->role ?? 'unknown')['class'] }} me-2">
                                                        {{ $this->getRoleBadge($transaction->sourceUser->role ?? 'unknown')['text'] }}
                                                    </span>
                                                    <small>{{ $transaction->sourceUser->name ?? 'N/A' }}</small>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-arrow-right me-2 text-muted"></i>
                                                    <span class="badge {{ $this->getRoleBadge($transaction->targetUser->role ?? 'unknown')['class'] }} me-2">
                                                        {{ $this->getRoleBadge($transaction->targetUser->role ?? 'unknown')['text'] }}
                                                    </span>
                                                    <small>{{ $transaction->targetUser->name ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold {{ $this->isCreditTransaction($transaction) ? 'text-success' : 'text-danger' }}">
                                                    {{ $this->isCreditTransaction($transaction) ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}
                                                </span>
                                                @if($transaction->description)
                                                    <small class="text-muted">{{ Str::limit($transaction->description, 30) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        @if($user->role === 'admin')
                                        <td>
                                            @php
                                                $transactionDetail = $transaction->transactionDetail ?? $transaction->cachedTransactionDetail ?? null;
                                                $adminShare = 0;
                                                if ($transactionDetail) {
                                                    $adminShare = (float) ($transactionDetail->admin_share_amount ?? 0);
                                                } else {
                                                    $shares = $transaction->shares ?? [];
                                                    $adminShare = (float) ($shares['admin_share'] ?? $transaction->admin_fee ?? 0);
                                                }
                                            @endphp
                                            @if($adminShare > 0)
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-user-shield me-1"></i>
                                                    {{ number_format($adminShare, 2) }} {{ $transaction->currency }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        @endif
                                        @if($user->role === 'admin' || $user->role === 'integrator')
                                        <td>
                                            @php
                                                $transactionDetail = $transaction->transactionDetail ?? $transaction->cachedTransactionDetail ?? null;
                                                $integratorShare = 0;
                                                if ($transactionDetail) {
                                                    $integratorShare = (float) ($transactionDetail->integrator_share_amount ?? 0);
                                                } else {
                                                    $shares = $transaction->shares ?? [];
                                                    $integratorShare = (float) ($shares['integrator_share'] ?? $transaction->integrator_fee ?? 0);
                                                }
                                            @endphp
                                            @if($integratorShare > 0)
                                                <span class="badge bg-info">
                                                    <i class="fas fa-user-tie me-1"></i>
                                                    {{ number_format($integratorShare, 2) }} {{ $transaction->currency }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        @endif
                                        <td>
                                            @php
                                                $transactionDetail = $transaction->transactionDetail ?? $transaction->cachedTransactionDetail ?? null;
                                                $operatorShare = 0;
                                                if ($transactionDetail) {
                                                    $operatorShare = (float) ($transactionDetail->operator_share_amount ?? 0);
                                                } else {
                                                    $shares = $transaction->shares ?? [];
                                                    $operatorShare = (float) ($shares['operator_share'] ?? $transaction->operator_fee ?? 0);
                                                }
                                            @endphp
                                            @if($operatorShare > 0)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-user-cog me-1"></i>
                                                    {{ number_format($operatorShare, 2) }} {{ $transaction->currency }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $this->getStatusBadgeClass($transaction->status) }}">
                                                {{ $this->getStatusLabel($transaction->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button wire:click="viewTransaction({{ $transaction->id }})" class="btn btn-outline-primary btn-sm" title="Voir dans le modal">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @if($user->role === 'admin')
                                                    @php
                                                        $detailsUrl = $this->getTransactionDetailsUrl($transaction);
                                                    @endphp
                                                    @if($detailsUrl)
                                                        <a href="{{ $detailsUrl }}" class="btn btn-outline-info btn-sm" title="Voir les détails complets">
                                                            <i class="fas fa-info-circle"></i>
                                                        </a>
                                                    @endif
                                                @endif
                                                @if($this->canCancelTransaction($transaction))
                                                    <button wire:click="cancelTransaction({{ $transaction->id }})" class="btn btn-outline-danger btn-sm" title="Annuler la transaction">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        @php
                                            $colspan = 7;
                                            if ($user->role === 'admin') $colspan++;
                                            if ($user->role === 'admin' || $user->role === 'integrator') $colspan++;
                                        @endphp
                                        <td colspan="{{ $colspan }}" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <p>Aucune transaction trouvée avec les filtres actuels.</p>
                                                <button wire:click="clearFilters" class="btn btn-outline-primary">
                                                    Effacer les filtres
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour les détails de transaction -->
    <div class="modal fade" id="transactionModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-receipt me-2"></i>
                        @if($selectedTransaction)
                            Transaction #{{ $selectedTransaction->id }}
                        @else
                            Détails de la Transaction
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="closeTransactionModal"></button>
                </div>
                <div class="modal-body">
                    @if($selectedTransaction)
                    <!-- Informations principales -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Référence</h6>
                            <p class="fw-bold">{{ $selectedTransaction->transaction_reference }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Statut</h6>
                            <span class="badge {{ $this->getStatusBadgeClass($selectedTransaction->status) }}">
                                {{ $this->getStatusLabel($selectedTransaction->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Type et date -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Type</h6>
                            <span class="badge bg-info">
                                {{ $this->getTransactionTypeLabel($selectedTransaction->transaction_type) }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Date</h6>
                            <p>{{ $selectedTransaction->created_at->format('d/m/Y à H:i:s') }}</p>
                        </div>
                    </div>

                    <!-- Montant -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted mb-2">Montant</h6>
                            <h4 class="fw-bold {{ $this->isCreditTransaction($selectedTransaction) ? 'text-success' : 'text-danger' }}">
                                {{ $this->isCreditTransaction($selectedTransaction) ? '+' : '-' }}{{ number_format($selectedTransaction->amount, 2) }} {{ $selectedTransaction->currency }}
                            </h4>
                            @if($selectedTransaction->total_amount && $selectedTransaction->total_amount != $selectedTransaction->amount)
                                <small class="text-muted">Total: {{ number_format($selectedTransaction->total_amount, 2) }} {{ $selectedTransaction->currency }}</small>
                            @endif
                        </div>
                    </div>

                    <!-- Parties impliquées -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Source</h6>
                            @if($selectedTransaction->sourceUser)
                                <div class="d-flex align-items-center">
                                    <span class="badge {{ $this->getRoleBadge($selectedTransaction->sourceUser->role)['class'] }} me-2">
                                        {{ $this->getRoleBadge($selectedTransaction->sourceUser->role)['text'] }}
                                    </span>
                                    <span>{{ $selectedTransaction->sourceUser->name }}</span>
                                </div>
                                <small class="text-muted">{{ $selectedTransaction->sourceUser->email }}</small>
                            @else
                                <p class="text-muted">N/A</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Destination</h6>
                            @if($selectedTransaction->targetUser)
                                <div class="d-flex align-items-center">
                                    <span class="badge {{ $this->getRoleBadge($selectedTransaction->targetUser->role)['class'] }} me-2">
                                        {{ $this->getRoleBadge($selectedTransaction->targetUser->role)['text'] }}
                                    </span>
                                    <span>{{ $selectedTransaction->targetUser->name }}</span>
                                </div>
                                <small class="text-muted">{{ $selectedTransaction->targetUser->email }}</small>
                            @else
                                <p class="text-muted">N/A</p>
                            @endif
                        </div>
                    </div>

                    <!-- Business Profile -->
                    @if($selectedTransaction->businessProfile)
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted mb-2">Business Profile</h6>
                            <p>{{ $selectedTransaction->businessProfile->name }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Frais -->
                    @php
                        // Utiliser l'accesseur shares qui priorise TransactionDetail
                        $shares = $selectedTransaction->shares ?? [];
                        $source = $shares['source'] ?? 'enhanced_transaction';
                        
                        // Calculer les montants avec priorité TransactionDetail
                        $adminShare = $source === 'transaction_detail'
                            ? ($shares['admin_share'] ?? 0)
                            : ($selectedTransaction->admin_fee ?? 0);
                        
                        $integratorShare = $source === 'transaction_detail'
                            ? ($shares['integrator_share'] ?? 0)
                            : ($selectedTransaction->integrator_fee ?? 0);
                        
                        $operatorShare = $source === 'transaction_detail'
                            ? ($shares['operator_share'] ?? 0)
                            : ($selectedTransaction->operator_fee ?? 0);
                        
                        $totalFees = $adminShare + $integratorShare;
                    @endphp
                    @if($totalFees > 0 || $operatorShare > 0)
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted mb-2">
                                Répartition des frais
                                <small class="text-muted">(Source: {{ $source === 'transaction_detail' ? 'TransactionDetail' : 'EnhancedTransaction' }})</small>
                            </h6>
                            <div class="list-group">
                                @if($adminShare > 0)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span>Part Admin</span>
                                        @if($source === 'transaction_detail' && isset($shares['admin_percentage']))
                                            <small class="text-muted d-block">({{ number_format($shares['admin_percentage'], 2) }}%)</small>
                                        @endif
                                    </div>
                                    <span class="badge bg-danger">{{ number_format($adminShare, 2) }} {{ $selectedTransaction->currency }}</span>
                                </div>
                                @endif
                                @if($integratorShare > 0)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span>Part Intégrateur</span>
                                        @if($source === 'transaction_detail' && isset($shares['integrator_percentage']))
                                            <small class="text-muted d-block">({{ number_format($shares['integrator_percentage'], 2) }}%)</small>
                                        @endif
                                    </div>
                                    <span class="badge bg-warning">{{ number_format($integratorShare, 2) }} {{ $selectedTransaction->currency }}</span>
                                </div>
                                @endif
                                @if($operatorShare > 0)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Part Opérateur</span>
                                    <span class="badge bg-info">{{ number_format($operatorShare, 2) }} {{ $selectedTransaction->currency }}</span>
                                </div>
                                @endif
                                <div class="list-group-item d-flex justify-content-between align-items-center fw-bold bg-light">
                                    <span>Total des frais (Admin + Intégrateur)</span>
                                    <span>{{ number_format($totalFees, 2) }} {{ $selectedTransaction->currency }}</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Montant net Opérateur</span>
                                    <span class="fw-bold text-success">{{ number_format($operatorShare, 2) }} {{ $selectedTransaction->currency }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <small>Aucun frais calculé pour cette transaction.</small>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Description -->
                    @if($selectedTransaction->description)
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted mb-2">Description</h6>
                            <p>{{ $selectedTransaction->description }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Métadonnées -->
                    @if($selectedTransaction->metadata)
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted mb-2">Informations supplémentaires</h6>
                            <pre class="bg-light p-2 rounded" style="font-size: 0.875rem;">{{ json_encode($selectedTransaction->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                    @endif

                    <!-- Dates importantes -->
                    <div class="row">
                        <div class="col-md-4">
                            @if($selectedTransaction->processed_at)
                            <small class="text-muted">Traité le: {{ $selectedTransaction->processed_at->format('d/m/Y H:i') }}</small>
                            @endif
                        </div>
                        <div class="col-md-4">
                            @if($selectedTransaction->completed_at)
                            <small class="text-muted">Terminé le: {{ $selectedTransaction->completed_at->format('d/m/Y H:i') }}</small>
                            @endif
                        </div>
                        <div class="col-md-4">
                            @if($selectedTransaction->canceled_at)
                            <small class="text-muted">Annulé le: {{ $selectedTransaction->canceled_at->format('d/m/Y H:i') }}</small>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="closeTransactionModal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    color: white;
}

.stat-content {
    flex: 1;
}

.stat-title {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.sortable {
    cursor: pointer;
    user-select: none;
}

.sortable:hover {
    background-color: #f8f9fa;
}

.table-responsive {
    max-height: 600px;
    overflow-y: auto;
}

.badge {
    font-size: 0.75rem;
}
</style>

<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('export-requested', (data) => {
        // Gérer l'export
        console.log('Export requested:', data);
    });

    // Ouvrir le modal Bootstrap quand l'événement est émis
    Livewire.on('open-transaction-modal', () => {
        const modalElement = document.getElementById('transactionModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    });

    // Fermer le modal quand on clique sur le backdrop
    document.getElementById('transactionModal')?.addEventListener('hidden.bs.modal', function () {
        @this.call('closeTransactionModal');
    });

    // Gérer les alertes
    Livewire.on('show-alert', (data) => {
        const alertType = data.type || 'info';
        const message = data.message || 'Opération effectuée';
        
        // Utiliser toastr ou votre système de notification préféré
        if (typeof toastr !== 'undefined') {
            toastr[alertType](message);
        } else {
            alert(message);
        }
    });

    // Gérer la completion d'export
    Livewire.on('export-completed', () => {
        @this.call('handleExportCompleted');
    });
});
</script>

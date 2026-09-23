@extends('layouts.admin')

@section('title', 'Toutes les Transactions Clients - Vue Complète')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="fas fa-exchange-alt"></i>
                    Toutes les Transactions Clients
                </h1>
                <div class="btn-group">
                    <a href="{{ route('admin.transactions.export', request()->query()) }}" 
                       class="btn btn-success">
                        <i class="fas fa-download"></i> Exporter CSV
                    </a>
                    <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" 
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.transactions.export', array_merge(request()->query(), ['format' => 'csv'])) }}">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('admin.transactions.export', array_merge(request()->query(), ['format' => 'excel'])) }}">
                                <i class="fas fa-file-excel"></i> Excel
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter"></i> Filtres
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.transactions.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Recherche</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="ID, Client, Email, Réservation..." 
                                   value="{{ $filters['search'] ?? '' }}">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Client</label>
                            <select name="user_id" class="form-select">
                                <option value="">Tous les clients</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ ($filters['user_id'] ?? '') == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }} ({{ $client->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Méthode de Paiement</label>
                            <select name="payment_method" class="form-select">
                                <option value="">Toutes</option>
                                @foreach($paymentMethods as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['payment_method'] ?? '') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="">Tous</option>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['status'] ?? '') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">ID Réservation</label>
                            <input type="text" name="reservation_id" class="form-control" 
                                   placeholder="ID Réservation" 
                                   value="{{ $filters['reservation_id'] ?? '' }}">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Date Début</label>
                            <input type="date" name="start_date" class="form-control" 
                                   value="{{ $filters['start_date'] ?? '' }}">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Date Fin</label>
                            <input type="date" name="end_date" class="form-control" 
                                   value="{{ $filters['end_date'] ?? '' }}">
                        </div>
                        
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filtrer
                            </button>
                            <a href="{{ route('admin.transactions.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    @if(isset($statistics))
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Transactions</h6>
                            <h3 class="mb-0">{{ number_format($statistics['total_transactions'] ?? 0) }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Montant</h6>
                            <h3 class="mb-0">{{ number_format($statistics['total_amount'] ?? 0, 2) }} €</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-euro-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Complétées</h6>
                            <h3 class="mb-0">{{ number_format($statistics['completed_count'] ?? 0) }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">En Attente</h6>
                            <h3 class="mb-0">{{ number_format($statistics['pending_count'] ?? 0) }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Table des Transactions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-table"></i> Liste des Transactions
                        <span class="badge bg-primary">{{ $transactions->total() }} transactions</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="transactionsTable" class="table table-striped table-hover mb-0" style="width:100%">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 80px;" data-sortable="true">ID</th>
                                    <th style="width: 120px;" data-sortable="true">Transaction ID</th>
                                    <th style="width: 200px;" data-sortable="true">Client</th>
                                    <th style="width: 100px;" data-sortable="true">ID Réservation</th>
                                    <th style="width: 120px;" data-sortable="true">Méthode Paiement</th>
                                    <th style="width: 100px;" data-sortable="true">Statut</th>
                                    <th style="width: 120px;" data-sortable="true">Montant Total</th>
                                    <th style="width: 150px;" data-sortable="true">Point de Charge</th>
                                    <th style="width: 120px;" data-sortable="true">Date</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr data-transaction-id="{{ $transaction->id }}">
                                    <td data-order="{{ $transaction->id }}">
                                        <span class="badge bg-secondary">#{{ $transaction->id }}</span>
                                    </td>
                                    <td>
                                        <code class="text-primary">{{ $transaction->transaction_id ?? 'N/A' }}</code>
                                    </td>
                                    <td>
                                        @if($transaction->user)
                                            <div>
                                                <strong>{{ $transaction->user->name ?? 'N/A' }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $transaction->user->email ?? 'N/A' }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td data-order="{{ $transaction->reservation_id ?? 0 }}">
                                        @if($transaction->reservation_id)
                                            <a href="{{ route('reservations.show', $transaction->reservation_id) }}" 
                                               class="badge bg-info text-decoration-none" 
                                               title="Voir la réservation">
                                                #{{ $transaction->reservation_id }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            // Récupérer la méthode de paiement depuis plusieurs sources possibles
                                            $paymentMethod = null;
                                            
                                            // 1. Depuis la réservation (source principale)
                                            if ($transaction->reservation && $transaction->reservation->payment_method) {
                                                $paymentMethod = $transaction->reservation->payment_method;
                                            }
                                            // 2. Depuis les métadonnées de la transaction
                                            elseif (isset($transaction->metadata['payment_method'])) {
                                                $paymentMethod = $transaction->metadata['payment_method'];
                                            }
                                            // 3. Depuis payment_data dans les métadonnées
                                            elseif (isset($transaction->metadata['payment_data']['payment_method'])) {
                                                $paymentMethod = $transaction->metadata['payment_data']['payment_method'];
                                            }
                                            // 4. Depuis payment_data direct
                                            elseif (isset($transaction->payment_data['payment_method'])) {
                                                $paymentMethod = $transaction->payment_data['payment_method'];
                                            }
                                            
                                            $paymentMethod = $paymentMethod ?? 'N/A';
                                            $paymentMethodLabel = $paymentMethods[$paymentMethod] ?? ucfirst($paymentMethod);
                                            
                                            // Couleur selon la méthode
                                            $paymentMethodColors = [
                                                'stripe' => 'info',
                                                'cmi' => 'success',
                                                'offline' => 'secondary',
                                                'credit' => 'primary',
                                                'prepaid_credit' => 'primary',
                                                'on_site_card' => 'warning',
                                                'online_card' => 'info'
                                            ];
                                            $paymentMethodColor = $paymentMethodColors[$paymentMethod] ?? 'primary';
                                        @endphp
                                        <span class="badge bg-{{ $paymentMethodColor }}" data-payment-method="{{ $paymentMethodLabel }}">
                                            <i class="fas fa-credit-card"></i> {{ $paymentMethodLabel }}
                                        </span>
                                        @if($paymentMethod !== 'N/A' && $transaction->reservation)
                                            <br>
                                            <small class="text-muted">
                                                Type: {{ $transaction->reservation->payment_type ?? 'N/A' }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($transaction->status) {
                                                'completed' => 'success',
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'failed' => 'danger',
                                                'cancelled' => 'secondary',
                                                default => 'secondary'
                                            };
                                            $statusLabel = $statuses[$transaction->status] ?? ucfirst($transaction->status);
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}" data-status="{{ $transaction->status }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td data-order="{{ $transaction->amount }}">
                                        <div>
                                            <strong class="text-success fs-6">
                                                {{ number_format($transaction->amount, 2) }} {{ $transaction->currency ?? 'EUR' }}
                                            </strong>
                                            @if($transaction->price_total && $transaction->price_total != $transaction->amount)
                                                <br>
                                                <small class="text-muted">
                                                    Total: {{ number_format($transaction->price_total, 2) }} €
                                                </small>
                                            @endif
                                        </div>
                                        @if($transaction->transactionDetail)
                                            <div class="mt-1">
                                                <small class="text-info d-block">
                                                    <i class="fas fa-user-shield"></i> Admin: {{ number_format($transaction->transactionDetail->admin_share_amount ?? 0, 2) }} €
                                                </small>
                                                @if($transaction->transactionDetail->integrator_share_amount > 0)
                                                    <small class="text-primary d-block">
                                                        <i class="fas fa-building"></i> Intégrateur: {{ number_format($transaction->transactionDetail->integrator_share_amount, 2) }} €
                                                    </small>
                                                @endif
                                                @if($transaction->transactionDetail->operator_share_amount > 0)
                                                    <small class="text-secondary d-block">
                                                        <i class="fas fa-user-cog"></i> Opérateur: {{ number_format($transaction->transactionDetail->operator_share_amount, 2) }} €
                                                    </small>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($transaction->chargingPoint)
                                            <div>
                                                <strong>{{ $transaction->chargingPoint->name ?? 'N/A' }}</strong>
                                                @if($transaction->chargingPoint->id)
                                                    <br>
                                                    <small class="text-muted">
                                                        ID: #{{ $transaction->chargingPoint->id }}
                                                    </small>
                                                @endif
                                                @if($transaction->chargingPoint->businessProfile)
                                                    <br>
                                                    <small class="text-info">
                                                        <i class="fas fa-building"></i> {{ $transaction->chargingPoint->businessProfile->name ?? '' }}
                                                    </small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td data-order="{{ $transaction->created_at->timestamp }}">
                                        <div>
                                            <strong>{{ $transaction->created_at->format('d/m/Y') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $transaction->created_at->format('H:i:s') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.transactions.show', $transaction->id) }}" 
                                               class="btn btn-outline-primary" 
                                               title="Voir les détails"
                                               data-bs-toggle="tooltip">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Aucune transaction trouvée</p>
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
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset("vendor/datatables/css/dataTables.bootstrap5.min.css") }}">
<link rel="stylesheet" href="{{ asset("vendor/datatables/css/buttons.bootstrap5.min.css") }}">
<style>
    .table th {
        cursor: pointer;
        user-select: none;
    }
    .table th:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    .table th.sorting::after {
        content: " ⇅";
        opacity: 0.5;
    }
    .table th.sorting_asc::after {
        content: " ↑";
        opacity: 1;
    }
    .table th.sorting_desc::after {
        content: " ↓";
        opacity: 1;
    }
    .badge {
        font-size: 0.75em;
        padding: 0.35em 0.65em;
    }
    code {
        font-size: 0.85em;
        background-color: rgba(0, 0, 0, 0.05);
        padding: 2px 6px;
        border-radius: 3px;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset("vendor/jquery/jquery.min.js") }}"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script>
$(document).ready(function() {
    // Initialiser DataTables avec toutes les fonctionnalités
    const table = $('#transactionsTable').DataTable({
        order: [[8, 'desc']], // Trier par date par défaut (colonne 8)
        pageLength: 100,
        lengthMenu: [[25, 50, 100, 200, -1], [25, 50, 100, 200, "Tous"]],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json',
            search: "Rechercher:",
            lengthMenu: "Afficher _MENU_ transactions",
            info: "Affichage de _START_ à _END_ sur _TOTAL_ transactions",
            infoEmpty: "Aucune transaction",
            infoFiltered: "(filtrées sur _MAX_ au total)",
            zeroRecords: "Aucune transaction trouvée",
            paginate: {
                first: "Premier",
                last: "Dernier",
                next: "Suivant",
                previous: "Précédent"
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                filename: 'transactions_admin_' + new Date().toISOString().slice(0,10),
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            // Extraire le texte des badges et autres éléments HTML
                            if (node) {
                                const $node = $(node);
                                // Pour les badges, récupérer l'attribut data ou le texte
                                if ($node.find('.badge').length) {
                                    return $node.find('.badge').attr('data-payment-method') || 
                                           $node.find('.badge').attr('data-status') || 
                                           $node.find('.badge').text().trim();
                                }
                                // Pour les montants, récupérer la valeur numérique
                                if ($node.find('strong').length && $node.find('strong').text().includes('€')) {
                                    return $node.find('strong').text().replace(/[^\d,.-]/g, '').replace(',', '.');
                                }
                                return $node.text().trim();
                            }
                            return data;
                        }
                    }
                }
            },
            {
                extend: 'csvHtml5',
                text: '<i class="fas fa-file-csv"></i> CSV',
                className: 'btn btn-info btn-sm',
                filename: 'transactions_admin_' + new Date().toISOString().slice(0,10),
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            if (node) {
                                const $node = $(node);
                                if ($node.find('.badge').length) {
                                    return $node.find('.badge').attr('data-payment-method') || 
                                           $node.find('.badge').attr('data-status') || 
                                           $node.find('.badge').text().trim();
                                }
                                if ($node.find('strong').length && $node.find('strong').text().includes('€')) {
                                    return $node.find('strong').text().replace(/[^\d,.-]/g, '').replace(',', '.');
                                }
                                return $node.text().trim();
                            }
                            return data;
                        }
                    }
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Imprimer',
                className: 'btn btn-secondary btn-sm',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'colvis',
                text: '<i class="fas fa-columns"></i> Colonnes',
                className: 'btn btn-warning btn-sm',
                columns: ':not(.no-toggle)'
            }
        ],
        responsive: true,
        processing: false,
        serverSide: false,
        columnDefs: [
            {
                targets: [9], // Colonne Actions
                orderable: false,
                searchable: false,
                className: 'no-toggle'
            },
            {
                targets: [0, 1, 3], // ID, Transaction ID, Reservation ID
                className: 'text-center'
            },
            {
                targets: [6], // Montant
                className: 'text-end',
                type: 'num'
            },
            {
                targets: [8], // Date
                type: 'date'
            }
        ],
        initComplete: function() {
            // Personnaliser la barre de recherche
            $('.dataTables_filter input').attr('placeholder', 'Rechercher dans toutes les colonnes...');
            $('.dataTables_filter input').addClass('form-control form-control-sm');
            $('.dataTables_filter').addClass('mb-3');
            
            // Personnaliser le sélecteur de longueur
            $('.dataTables_length select').addClass('form-select form-select-sm');
            
            // Ajouter des tooltips
            if (typeof bootstrap !== 'undefined') {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        }
    });
    
    // Ajouter un indicateur de chargement
    table.on('processing.dt', function(e, settings, processing) {
        if (processing) {
            $('#transactionsTable_processing').show();
        } else {
            $('#transactionsTable_processing').hide();
        }
    });
    
    // Sauvegarder les préférences de l'utilisateur
    table.on('length.dt', function(e, settings, len) {
        localStorage.setItem('transactionsTable_length', len);
    });
    
    // Restaurer les préférences
    const savedLength = localStorage.getItem('transactionsTable_length');
    if (savedLength) {
        table.page.len(parseInt(savedLength)).draw();
    }
});
</script>
@endpush
@endsection


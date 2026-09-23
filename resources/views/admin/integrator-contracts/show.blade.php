@extends('layouts.app')

@section('title', 'Contrat ' . $contract->contract_number)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-file-contract me-2"></i>
                Contrat: {{ $contract->contract_number }}
            </h1>
            <p class="text-muted mb-0">{{ $contract->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.integrator-contracts.edit', $contract) }}" class="btn btn-warning">
                <i class="fas fa-edit me-1"></i>
                Modifier
            </a>
            <a href="{{ route('admin.integrator-contracts.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Retour
            </a>
        </div>
    </div>

    <!-- Status Badge -->
    <div class="mb-4">
        @switch($contract->status)
            @case('active')
                <span class="badge bg-success fs-5">Contrat Actif</span>
                @break
            @case('suspended')
                <span class="badge bg-warning fs-5">Contrat Suspendu</span>
                @break
            @case('terminated')
                <span class="badge bg-danger fs-5">Contrat Terminé</span>
                @break
            @default
                <span class="badge bg-secondary fs-5">Brouillon</span>
        @endswitch
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Contract Details -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Détails du contrat</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Intégrateur</dt>
                                <dd class="col-sm-8">
                                    @if($contract->integrator)
                                        <a href="{{ route('integrators.show', $contract->integrator) }}">
                                            {{ $contract->integrator->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </dd>
                                
                                <dt class="col-sm-4">Nom</dt>
                                <dd class="col-sm-8">{{ $contract->name }}</dd>
                                
                                <dt class="col-sm-4">Description</dt>
                                <dd class="col-sm-8">{{ $contract->description ?? '-' }}</dd>
                                
                                <dt class="col-sm-4">Devise</dt>
                                <dd class="col-sm-8">{{ $contract->currency }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Date début</dt>
                                <dd class="col-sm-8">{{ $contract->contract_start_date?->format('d/m/Y') ?? '-' }}</dd>
                                
                                <dt class="col-sm-4">Date fin</dt>
                                <dd class="col-sm-8">{{ $contract->contract_end_date?->format('d/m/Y') ?? '-' }}</dd>
                                
                                <dt class="col-sm-4">Renouvellement</dt>
                                <dd class="col-sm-8">
                                    @if($contract->auto_renewal)
                                        <span class="badge bg-success">Automatique</span>
                                    @else
                                        <span class="badge bg-secondary">Manuel</span>
                                    @endif
                                </dd>
                                
                                <dt class="col-sm-4">Créé le</dt>
                                <dd class="col-sm-8">{{ $contract->created_at->format('d/m/Y H:i') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing Configuration -->
            <div class="row">
                <!-- Maintenance Fee -->
                <div class="col-md-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-info text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-tools me-1"></i>
                                Frais maintenance
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($contract->maintenance_fee_enabled)
                                <dl class="row mb-0">
                                    <dt class="col-6">Montant</dt>
                                    <dd class="col-6 text-end">
                                        <strong>{{ number_format($contract->maintenance_fee_amount, 2) }} {{ $contract->currency }}</strong>
                                    </dd>
                                    
                                    <dt class="col-6">Période</dt>
                                    <dd class="col-6 text-end">
                                        @switch($contract->maintenance_fee_period)
                                            @case('monthly')
                                                Mensuel
                                                @break
                                            @case('quarterly')
                                                Trimestriel
                                                @break
                                            @case('yearly')
                                                Annuel
                                                @break
                                        @endswitch
                                    </dd>
                                    
                                    <dt class="col-6">Prochaine échéance</dt>
                                    <dd class="col-6 text-end">
                                        {{ $contract->maintenance_fee_next_due_date?->format('d/m/Y') ?? '-' }}
                                    </dd>
                                </dl>
                            @else
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-times-circle fa-2x mb-2"></i>
                                    <p class="mb-0">Désactivé</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Terminal Fee -->
                <div class="col-md-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-info text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-charging-station me-1"></i>
                                Frais borne active
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($contract->terminal_fee_enabled)
                                <dl class="row mb-0">
                                    <dt class="col-6">Montant/borne</dt>
                                    <dd class="col-6 text-end">
                                        <strong>{{ number_format($contract->terminal_fee_amount, 2) }} {{ $contract->currency }}</strong>
                                    </dd>
                                    
                                    <dt class="col-6">Période</dt>
                                    <dd class="col-6 text-end">
                                        @switch($contract->terminal_fee_period)
                                            @case('monthly')
                                                Mensuel
                                                @break
                                            @case('quarterly')
                                                Trimestriel
                                                @break
                                            @case('yearly')
                                                Annuel
                                                @break
                                        @endswitch
                                    </dd>
                                    
                                    <dt class="col-6">Bornes actives</dt>
                                    <dd class="col-6 text-end">
                                        <strong>{{ $activeTerminalCount }}</strong>
                                    </dd>
                                    
                                    <dt class="col-6">Bornes gratuites</dt>
                                    <dd class="col-6 text-end">{{ $contract->terminal_fee_free_count }}</dd>
                                    
                                    <dt class="col-6">Minimum</dt>
                                    <dd class="col-6 text-end">{{ $contract->terminal_fee_minimum }}</dd>
                                </dl>
                            @else
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-times-circle fa-2x mb-2"></i>
                                    <p class="mb-0">Désactivé</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Transaction Commission -->
                <div class="col-md-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-primary text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-percent me-1"></i>
                                Commission transaction
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($contract->transaction_commission_enabled)
                                <dl class="row mb-0">
                                    <dt class="col-6">Type</dt>
                                    <dd class="col-6 text-end">
                                        @switch($contract->transaction_commission_type)
                                            @case('percentage')
                                                Pourcentage
                                                @break
                                            @case('fixed')
                                                Montant fixe
                                                @break
                                            @case('combined')
                                                Combiné
                                                @break
                                        @endswitch
                                    </dd>
                                    
                                    @if(in_array($contract->transaction_commission_type, ['percentage', 'combined']))
                                        <dt class="col-6">Pourcentage</dt>
                                        <dd class="col-6 text-end">{{ $contract->transaction_commission_percentage }}%</dd>
                                    @endif
                                    
                                    @if(in_array($contract->transaction_commission_type, ['fixed', 'combined']))
                                        <dt class="col-6">Montant fixe</dt>
                                        <dd class="col-6 text-end">{{ number_format($contract->transaction_commission_fixed_amount, 2) }} {{ $contract->currency }}</dd>
                                    @endif
                                    
                                    <dt class="col-6">Min</dt>
                                    <dd class="col-6 text-end">{{ number_format($contract->transaction_commission_min_amount, 2) }} {{ $contract->currency }}</dd>
                                    
                                    @if($contract->transaction_commission_max_amount)
                                        <dt class="col-6">Max</dt>
                                        <dd class="col-6 text-end">{{ number_format($contract->transaction_commission_max_amount, 2) }} {{ $contract->currency }}</dd>
                                    @endif
                                </dl>
                            @else
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-times-circle fa-2x mb-2"></i>
                                    <p class="mb-0">Désactivé</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Invoices -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Factures récentes</h6>
                </div>
                <div class="card-body">
                    @if($invoices->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>N° Facture</th>
                                        <th>Type</th>
                                        <th>Période</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoices as $invoice)
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.integrator-invoices.show', $invoice) }}">
                                                    {{ $invoice->invoice_number }}
                                                </a>
                                            </td>
                                            <td>{{ $invoice->type_label }}</td>
                                            <td>{{ $invoice->formatted_period }}</td>
                                            <td>{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</td>
                                            <td>
                                                @switch($invoice->status)
                                                    @case('paid')
                                                        <span class="badge bg-success">Payée</span>
                                                        @break
                                                    @case('pending')
                                                        <span class="badge bg-warning">En attente</span>
                                                        @break
                                                    @case('overdue')
                                                        <span class="badge bg-danger">En retard</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge bg-secondary">Annulée</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-info">{{ $invoice->status }}</span>
                                                @endswitch
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.integrator-invoices.show', $invoice) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>Aucune facture</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Billing Summary -->
            @if($billingSummary['contract'])
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Résumé facturation</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Bornes actives</span>
                                <strong>{{ $billingSummary['active_terminals'] }}</strong>
                            </div>
                        </div>
                        
                        @if($billingSummary['maintenance_fee']['enabled'])
                            <div class="mb-3">
                                <small class="text-muted d-block">Frais maintenance</small>
                                <div class="d-flex justify-content-between">
                                    <span>{{ number_format($billingSummary['maintenance_fee']['amount'], 2) }} {{ $billingSummary['contract']['currency'] }}</span>
                                    <span class="badge bg-info">{{ $billingSummary['maintenance_fee']['period'] }}</span>
                                </div>
                            </div>
                        @endif

                        @if($billingSummary['terminal_fee']['enabled'])
                            <div class="mb-3">
                                <small class="text-muted d-block">Frais borne active</small>
                                <div class="d-flex justify-content-between">
                                    <span>{{ number_format($billingSummary['terminal_fee']['amount'], 2) }} {{ $billingSummary['contract']['currency'] }}</span>
                                    <span class="badge bg-info">par borne</span>
                                </div>
                            </div>
                        @endif

                        @if($billingSummary['transaction_commission']['enabled'])
                            <div class="mb-3">
                                <small class="text-muted d-block">Commission transaction</small>
                                @if($billingSummary['transaction_commission']['type'] === 'percentage')
                                    <div class="d-flex justify-content-between">
                                        <span>{{ $billingSummary['transaction_commission']['percentage'] }}%</span>
                                    </div>
                                @else
                                    <div class="d-flex justify-content-between">
                                        <span>{{ number_format($billingSummary['transaction_commission']['fixed_amount'], 2) }} {{ $billingSummary['contract']['currency'] }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <hr>
                        
                        <div class="d-flex justify-content-between">
                            <strong>Total période</strong>
                            <strong>{{ number_format($billingSummary['totals']['total'], 2) }} {{ $billingSummary['contract']['currency'] }}</strong>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Actions</h6>
                </div>
                <div class="card-body">
                    @if($contract->isActive())
                        <div class="d-grid gap-2">
                            <form action="{{ route('admin.integrator-contracts.process-billing', $contract) }}" 
                                  method="POST">
                                @csrf
                                <input type="hidden" name="billing_type" value="all">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-calculator me-1"></i>
                                    Calculer facturation
                                </button>
                            </form>
                            
                            <a href="{{ route('admin.integrator-contracts.edit', $contract) }}" 
                               class="btn btn-warning">
                                <i class="fas fa-edit me-1"></i>
                                Modifier le contrat
                            </a>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Le contrat doit être actif pour traiter la facturation.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Notes -->
            @if($contract->notes)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Notes</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">{{ $contract->notes }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

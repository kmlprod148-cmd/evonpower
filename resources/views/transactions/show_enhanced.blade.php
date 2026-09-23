@extends('layouts.app')

@section('title', 'Détails de la Transaction #' . $transaction->id)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- En-tête de la transaction -->
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="card-title">
                                <i class="fas fa-receipt text-primary"></i>
                                Transaction #{{ $transaction->id }}
                            </h3>
                            <p class="text-muted mb-0">
                                Créée le {{ $transaction->created_at->format('d/m/Y à H:i') }}
                                @if($transaction->updated_at && $transaction->updated_at != $transaction->created_at)
                                    - Modifiée le {{ $transaction->updated_at->format('d/m/Y à H:i') }}
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4 text-right">
                            <span class="badge badge-{{ $transaction->status == 'completed' ? 'success' : ($transaction->status == 'pending' ? 'warning' : 'danger') }} badge-lg">
                                {{ ucfirst($transaction->status ?? 'Non défini') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Informations principales -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-info-circle"></i> Informations Générales
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>ID Transaction:</strong></td>
                                            <td>{{ $transaction->transaction_id ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Type:</strong></td>
                                            <td>
                                                @switch($transaction->transaction_type ?? 'client')
                                                    @case('client')
                                                        <span class="badge badge-info">Transaction Client</span>
                                                        @break
                                                    @case('admin_integrator')
                                                        <span class="badge badge-warning">Admin → Intégrateur</span>
                                                        @break
                                                    @case('integrator_operator')
                                                        <span class="badge badge-secondary">Intégrateur → Opérateur</span>
                                                        @break
                                                    @default
                                                        <span class="badge badge-light">Autre</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Point de Charge:</strong></td>
                                            <td>
                                                @if($transaction->chargingPoint)
                                                    <a href="{{ route('charging-points.show', $transaction->chargingPoint->id) }}" class="text-primary">
                                                        {{ $transaction->chargingPoint->name }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Client:</strong></td>
                                            <td>
                                                @if($transaction->reservation && $transaction->reservation->user)
                                                    <a href="{{ route('admin.users.show', $transaction->reservation->user->id) }}" class="text-primary">
                                                        {{ $transaction->reservation->user->name }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Business Profile:</strong></td>
                                            <td>
                                                @if($transaction->businessProfile)
                                                    <a href="{{ route('business-profiles.show', $transaction->businessProfile->id) }}" class="text-primary">
                                                        {{ $transaction->businessProfile->name }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Devise:</strong></td>
                                            <td>{{ $transaction->currency ?? 'EUR' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Méthode de Paiement:</strong></td>
                                            <td>{{ $transaction->payment_method ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Description:</strong></td>
                                            <td>{{ $transaction->description ?? 'Aucune description' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Détails financiers -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-euro-sign"></i> Détails Financiers
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary">Composants du Prix</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Prix Énergie:</td>
                                            <td class="text-right">{{ number_format($transaction->price_energy ?? 0, 2) }} EUR</td>
                                        </tr>
                                        <tr>
                                            <td>Prix Temps:</td>
                                            <td class="text-right">{{ number_format($transaction->price_time ?? 0, 2) }} EUR</td>
                                        </tr>
                                        <tr>
                                            <td>Prix Service:</td>
                                            <td class="text-right">{{ number_format($transaction->price_service ?? 0, 2) }} EUR</td>
                                        </tr>
                                        <tr>
                                            <td>Taxes:</td>
                                            <td class="text-right">{{ number_format($transaction->price_tax ?? 0, 2) }} EUR</td>
                                        </tr>
                                        <tr>
                                            <td>Frais d'Activation:</td>
                                            <td class="text-right">{{ number_format($transaction->activation_fee ?? 0, 2) }} EUR</td>
                                        </tr>
                                        <tr class="table-active">
                                            <td><strong>Total:</strong></td>
                                            <td class="text-right"><strong>{{ number_format($transaction->price_total ?? $transaction->amount ?? 0, 2) }} EUR</strong></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-primary">Répartition des Revenus
                                        @if($transaction->transactionDetail)
                                            <span class="badge badge-success ml-2">
                                                <i class="fas fa-check-circle"></i> Selon Business Profiles
                                            </span>
                                        @endif
                                    </h6>
                                    @php
                                        // PRIORITÉ : TransactionDetail (source de vérité) > Variables du contrôleur
                                        if ($transaction->transactionDetail) {
                                            $adminShare = (float) ($transaction->transactionDetail->admin_share_amount ?? 0);
                                            $integratorShare = (float) ($transaction->transactionDetail->integrator_share_amount ?? 0);
                                            $operatorShare = (float) ($transaction->transactionDetail->operator_share_amount ?? 0);
                                            $totalFees = $adminShare + $integratorShare;
                                            $netAmount = $operatorShare;
                                            $hasRealData = true;
                                        } else {
                                            // Fallback sur les variables du contrôleur
                                            $adminShare = $adminShare ?? 0;
                                            $integratorShare = $integratorShare ?? 0;
                                            $operatorShare = $partnerShare ?? 0;
                                            $totalFees = $totalFees ?? 0;
                                            $netAmount = $netAmount ?? 0;
                                            $hasRealData = false;
                                        }
                                    @endphp
                                    <table class="table table-sm">
                                        @if($hasRealData && auth()->user()->role === 'admin')
                                        <tr>
                                            <td>Commission Admin:</td>
                                            <td class="text-right">
                                                <strong class="text-danger">{{ number_format($adminShare, 2) }} EUR</strong>
                                                @if($transaction->transactionDetail->admin_share_percentage)
                                                    <small class="text-muted">({{ number_format($transaction->transactionDetail->admin_share_percentage, 2) }}%)</small>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td>Commission Intégrateur:</td>
                                            <td class="text-right">
                                                <strong class="text-info">{{ number_format($integratorShare, 2) }} EUR</strong>
                                                @if($transaction->transactionDetail && $transaction->transactionDetail->integrator_share_percentage)
                                                    <small class="text-muted">({{ number_format($transaction->transactionDetail->integrator_share_percentage, 2) }}%)</small>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Commission Opérateur:</td>
                                            <td class="text-right">
                                                <strong class="text-success">{{ number_format($operatorShare, 2) }} EUR</strong>
                                            </td>
                                        </tr>
                                        <tr class="table-active">
                                            <td><strong>Total Commissions:</strong></td>
                                            <td class="text-right"><strong>{{ number_format($totalFees, 2) }} EUR</strong></td>
                                        </tr>
                                        <tr class="table-success">
                                            <td><strong>Montant Net Opérateur:</strong></td>
                                            <td class="text-right"><strong>{{ number_format($netAmount, 2) }} EUR</strong></td>
                                        </tr>
                                    </table>
                                    @if($hasRealData)
                                        <div class="alert alert-info mt-2 mb-0 p-2">
                                            <small>
                                                <i class="fas fa-info-circle"></i>
                                                Données calculées selon Business Profiles (TransactionDetail)
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations de session -->
                    @if($transaction->start_timestamp || $transaction->stop_timestamp)
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-clock"></i> Informations de Session
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Début de Session:</strong></td>
                                            <td>{{ $transaction->start_timestamp ? \Carbon\Carbon::parse($transaction->start_timestamp)->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Fin de Session:</strong></td>
                                            <td>{{ $transaction->stop_timestamp ? \Carbon\Carbon::parse($transaction->stop_timestamp)->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Durée:</strong></td>
                                            <td>
                                                @if($transaction->start_timestamp && $transaction->stop_timestamp)
                                                    {{ \Carbon\Carbon::parse($transaction->start_timestamp)->diffForHumans(\Carbon\Carbon::parse($transaction->stop_timestamp), true) }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Compteur Début:</strong></td>
                                            <td>{{ $transaction->meter_start ?? 'N/A' }} kWh</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Compteur Fin:</strong></td>
                                            <td>{{ $transaction->meter_stop ?? 'N/A' }} kWh</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Énergie Consommée:</strong></td>
                                            <td>
                                                @if($transaction->meter_start && $transaction->meter_stop)
                                                    {{ number_format($transaction->meter_stop - $transaction->meter_start, 2) }} kWh
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Notes et description -->
                    @if($transaction->notes || $transaction->description)
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-sticky-note"></i> Notes et Description
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($transaction->description)
                                <h6>Description:</h6>
                                <p class="text-muted">{{ $transaction->description }}</p>
                            @endif
                            
                            @if($transaction->notes)
                                <h6>Notes Internes:</h6>
                                <p class="text-muted">{{ $transaction->notes }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Panneau latéral -->
                <div class="col-lg-4">
                    <!-- Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-cogs"></i> Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                @can('update', $transaction)
                                    <a href="{{ route('transactions.edit', $transaction->id) }}" class="btn btn-warning">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                @endcan
                                
                                @if($transaction->status == 'pending')
                                    <form action="{{ route('transactions.complete', $transaction->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Marquer cette transaction comme terminée ?')">
                                            <i class="fas fa-check"></i> Terminer
                                        </button>
                                    </form>
                                @endif
                                
                                @can('delete', $transaction)
                                    <form action="{{ route('transactions.destroy', $transaction->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette transaction ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger w-100">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    </form>
                                @endcan
                                
                                <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Retour à la liste
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques rapides -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-chart-bar"></i> Statistiques
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="info-box bg-primary">
                                        <span class="info-box-icon">
                                            <i class="fas fa-euro-sign"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Montant Total</span>
                                            <span class="info-box-number">{{ number_format($transaction->price_total ?? $transaction->amount ?? 0, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="info-box bg-success">
                                        <span class="info-box-icon">
                                            <i class="fas fa-percentage"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Commissions</span>
                                            <span class="info-box-number">{{ number_format($totalFees, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Répartition détaillée -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-chart-pie"></i> Répartition des Revenus
                                @if($transaction->transactionDetail)
                                    <span class="badge badge-success ml-2">
                                        <i class="fas fa-check-circle"></i> Source: TransactionDetail
                                    </span>
                                @endif
                            </h5>
                        </div>
                        <div class="card-body">
                            @php
                                // Utiliser TransactionDetail comme source de vérité
                                if ($transaction->transactionDetail) {
                                    $adminShare = (float) ($transaction->transactionDetail->admin_share_amount ?? 0);
                                    $integratorShare = (float) ($transaction->transactionDetail->integrator_share_amount ?? 0);
                                    $operatorShare = (float) ($transaction->transactionDetail->operator_share_amount ?? 0);
                                    $totalFees = $adminShare + $integratorShare;
                                    $netAmount = $operatorShare;
                                } else {
                                    // Fallback sur les variables du contrôleur
                                    $adminShare = $adminShare ?? 0;
                                    $integratorShare = $integratorShare ?? 0;
                                    $operatorShare = $partnerShare ?? 0;
                                    $totalFees = $totalFees ?? 0;
                                    $netAmount = $netAmount ?? 0;
                                }
                                $totalAmount = $transaction->price_total ?? $transaction->amount ?? 0;
                            @endphp
                            
                            @if(auth()->user()->role === 'admin' && $adminShare > 0)
                            <div class="progress-group">
                                <div class="progress-group-header">
                                    <span><i class="fas fa-user-shield text-danger"></i> Admin</span>
                                    <span class="ml-auto">{{ number_format($adminShare, 2) }} EUR</span>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-danger" style="width: {{ $totalAmount > 0 ? ($adminShare / $totalAmount * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            @endif
                            
                            <div class="progress-group">
                                <div class="progress-group-header">
                                    <span><i class="fas fa-user-tie text-info"></i> Intégrateur</span>
                                    <span class="ml-auto">{{ number_format($integratorShare, 2) }} EUR</span>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-info" style="width: {{ $totalAmount > 0 ? ($integratorShare / $totalAmount * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            
                            <div class="progress-group">
                                <div class="progress-group-header">
                                    <span><i class="fas fa-user-cog text-success"></i> Opérateur</span>
                                    <span class="ml-auto">{{ number_format($operatorShare, 2) }} EUR</span>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-success" style="width: {{ $totalAmount > 0 ? ($operatorShare / $totalAmount * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between">
                                    <strong>Total:</strong>
                                    <strong>{{ number_format($totalAmount, 2) }} EUR</strong>
                                </div>
                                <div class="d-flex justify-content-between text-muted">
                                    <small>Frais totaux:</small>
                                    <small>{{ number_format($totalFees, 2) }} EUR</small>
                                </div>
                                <div class="d-flex justify-content-between text-success">
                                    <small><strong>Net Opérateur:</strong></small>
                                    <small><strong>{{ number_format($netAmount, 2) }} EUR</strong></small>
                                </div>
                            </div>
                            
                            @if($transaction->transactionDetail)
                                <div class="alert alert-success mt-3 mb-0 p-2">
                                    <small>
                                        <i class="fas fa-check-circle"></i>
                                        Répartition calculée selon Business Profiles (Source: TransactionDetail)
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

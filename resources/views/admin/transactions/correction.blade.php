@extends('layouts.admin')

@section('title', 'Correction des Transactions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tools mr-2"></i>
                        Correction des Anciennes Transactions
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" onclick="correctAllTransactions()">
                            <i class="fas fa-sync mr-2"></i>
                            Corriger Toutes les Transactions
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Rapport des transactions existantes -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-receipt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Transactions</span>
                                    <span class="info-box-number">{{ $report['total_transactions'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger">
                                    <i class="fas fa-user-shield"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Commission Admin</span>
                                    <span class="info-box-number">{{ number_format($report['commission_summary']['total_admin_commission'], 2) }} EUR</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-building"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Commission Intégrateur</span>
                                    <span class="info-box-number">{{ number_format($report['commission_summary']['total_integrator_commission'], 2) }} EUR</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-handshake"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Commission Partenaire</span>
                                    <span class="info-box-number">{{ number_format($report['commission_summary']['total_partner_commission'], 2) }} EUR</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Répartition par Business Profile -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Répartition par Business Profile</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Business Profile</th>
                                                    <th>Transactions</th>
                                                    <th>Montant Total</th>
                                                    <th>Commission Admin</th>
                                                    <th>Commission Intégrateur</th>
                                                    <th>Commission Partenaire</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($report['by_business_profile'] as $profile)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $profile['business_profile_name'] }}</strong><br>
                                                        <small class="text-muted">ID: {{ $profile['business_profile_id'] }}</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info">{{ $profile['transaction_count'] }}</span>
                                                    </td>
                                                    <td>
                                                        <strong>{{ number_format($profile['total_amount'], 2) }} EUR</strong>
                                                    </td>
                                                    <td>
                                                        <span class="text-danger">{{ number_format($profile['admin_commission_total'], 2) }} EUR</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-primary">{{ number_format($profile['integrator_commission_total'], 2) }} EUR</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-success">{{ number_format($profile['partner_commission_total'], 2) }} EUR</span>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-warning" 
                                                                onclick="correctBusinessProfile({{ $profile['business_profile_id'] }}, '{{ $profile['business_profile_name'] }}')">
                                                            <i class="fas fa-tools mr-1"></i>
                                                            Corriger
                                                        </button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations sur la correction -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h5><i class="icon fas fa-info"></i> Information sur la Correction</h5>
                                <p>Cette correction applique la nouvelle logique de commission :</p>
                                <ul>
                                    <li><strong>Intégrateur</strong> → reçoit sa part selon le Business Profile Intégrateur→Opérateur</li>
                                    <li><strong>Admin</strong> → déduit sa part de la part de l'Intégrateur (pas du total)</li>
                                    <li><strong>Opérateur</strong> → reçoit le montant net après déduction de la commission Intégrateur</li>
                                </ul>
                                <p class="mb-0"><strong>Note :</strong> Cette opération est irréversible. Assurez-vous de faire une sauvegarde avant de procéder.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmation de Correction</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
                <div class="alert alert-warning">
                    <strong>Attention :</strong> Cette opération est irréversible. Assurez-vous d'avoir fait une sauvegarde.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmButton">Confirmer la Correction</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let currentAction = null;

function correctAllTransactions() {
    currentAction = 'all';
    document.getElementById('confirmMessage').innerHTML = 
        'Êtes-vous sûr de vouloir corriger <strong>toutes les transactions</strong> ?<br>' +
        'Cette opération va appliquer la nouvelle logique de commission à {{ $report["total_transactions"] }} transactions.';
    const confirmModalEl = document.getElementById('confirmModal');
    const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
    confirmModal.show();
}

function correctBusinessProfile(businessProfileId, businessProfileName) {
    currentAction = 'business_profile';
    currentBusinessProfileId = businessProfileId;
    document.getElementById('confirmMessage').innerHTML = 
        'Êtes-vous sûr de vouloir corriger les transactions du Business Profile <strong>' + businessProfileName + '</strong> ?';
    const confirmModalEl = document.getElementById('confirmModal');
    const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmModalEl);
    confirmModal.show();
}

document.getElementById('confirmButton').addEventListener('click', function() {
    if (currentAction === 'all') {
        correctAllTransactionsConfirm();
    } else if (currentAction === 'business_profile') {
        correctBusinessProfileConfirm();
    }
});

function correctAllTransactionsConfirm() {
    const confirmModalEl = document.getElementById('confirmModal');
    const confirmModal = bootstrap.Modal.getInstance(confirmModalEl);
    if (confirmModal) confirmModal.hide();
    
    // Afficher un loader
    showLoader('Correction en cours...');
    
    fetch('{{ route("admin.transactions.correction.correct-all") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        
        if (data.success) {
            showNotification('Correction terminée avec succès !', 'success');
            // Recharger la page pour voir les résultats
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showNotification('Erreur lors de la correction: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoader();
        showNotification('Erreur lors de la correction: ' + error.message, 'error');
    });
}

function correctBusinessProfileConfirm() {
    const confirmModalEl = document.getElementById('confirmModal');
    const confirmModal = bootstrap.Modal.getInstance(confirmModalEl);
    if (confirmModal) confirmModal.hide();
    
    // Afficher un loader
    showLoader('Correction en cours...');
    
    fetch(`{{ route("admin.transactions.correction.correct-business-profile", ":id") }}`.replace(':id', currentBusinessProfileId), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        
        if (data.success) {
            showNotification('Correction du Business Profile terminée avec succès !', 'success');
            // Recharger la page pour voir les résultats
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showNotification('Erreur lors de la correction: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoader();
        showNotification('Erreur lors de la correction: ' + error.message, 'error');
    });
}

function showLoader(message) {
    // Créer un overlay avec loader
    const overlay = document.createElement('div');
    overlay.id = 'loader-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    `;
    
    overlay.innerHTML = `
        <div style="background: white; padding: 20px; border-radius: 5px; text-align: center;">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">${message}</p>
        </div>
    `;
    
    document.body.appendChild(overlay);
}

function hideLoader() {
    const overlay = document.getElementById('loader-overlay');
    if (overlay) {
        overlay.remove();
    }
}

function showNotification(message, type) {
    // Utiliser la notification système existante ou créer une simple
    alert(message);
}
</script>
@endsection

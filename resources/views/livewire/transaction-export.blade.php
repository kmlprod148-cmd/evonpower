<div class="transaction-export">
    <!-- Modal d'export -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-download me-2"></i>
                        Export des Transactions
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($isExporting)
                        <!-- Barre de progression -->
                        <div class="text-center">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <h6>{{ $exportStatus }}</h6>
                            <div class="progress mb-3">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: {{ $exportProgress }}%" 
                                     aria-valuenow="{{ $exportProgress }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    {{ $exportProgress }}%
                                </div>
                            </div>
                            <p class="text-muted">Veuillez patienter pendant la génération du fichier...</p>
                        </div>
                    @else
                        <!-- Formulaire d'export -->
                        <form wire:submit.prevent="export">
                            <div class="mb-3">
                                <label class="form-label">Format d'export</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" wire:model="format" value="excel" id="format-excel">
                                    <label class="btn btn-outline-primary" for="format-excel">
                                        <i class="fas fa-file-excel me-2"></i>Excel (.xlsx)
                                    </label>
                                    
                                    <input type="radio" class="btn-check" wire:model="format" value="csv" id="format-csv">
                                    <label class="btn btn-outline-success" for="format-csv">
                                        <i class="fas fa-file-csv me-2"></i>CSV (.csv)
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="includeFeeDetails" id="includeFeeDetails">
                                    <label class="form-check-label" for="includeFeeDetails">
                                        Inclure les détails des frais
                                    </label>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> L'export inclura toutes les transactions selon les filtres appliqués. 
                                Les détails des frais ne sont visibles que pour les administrateurs.
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                    <span wire:loading.remove>
                                        <i class="fas fa-download me-2"></i>
                                        Exporter les Transactions
                                    </span>
                                    <span wire:loading>
                                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                        Export en cours...
                                    </span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Toast pour les notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="exportToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-download text-primary me-2"></i>
                <strong class="me-auto">Export des Transactions</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                <!-- Contenu dynamique -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:init', () => {
    // Gérer le succès de l'export
    Livewire.on('export-success', (data) => {
        const toast = document.getElementById('exportToast');
        const toastBody = toast.querySelector('.toast-body');
        
        toastBody.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle text-success me-2"></i>
                <div>
                    <strong>Export réussi !</strong><br>
                    <small>${data.total_records} transactions exportées</small>
                </div>
            </div>
            <div class="mt-2">
                <a href="${data.download_url}" class="btn btn-success btn-sm" download="${data.filename}">
                    <i class="fas fa-download me-1"></i>
                    Télécharger le fichier
                </a>
            </div>
        `;
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
        if (modal) {
            modal.hide();
        }
    });

    // Gérer les erreurs d'export
    Livewire.on('export-error', (data) => {
        const toast = document.getElementById('exportToast');
        const toastBody = toast.querySelector('.toast-body');
        
        toastBody.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                <div>
                    <strong>Erreur d'export</strong><br>
                    <small>${data.message}</small>
                </div>
            </div>
        `;
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
        if (modal) {
            modal.hide();
        }
    });
});
</script>

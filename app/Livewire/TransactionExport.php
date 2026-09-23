<?php

namespace App\Livewire;

use App\Exports\TransactionExport;
use App\Models\EnhancedTransaction;
use App\Models\EnhancedUser;
use App\Models\EnhancedBusinessProfile;
use Livewire\Component;
use Livewire\WithFileDownloads;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class TransactionExport extends Component
{
    use WithFileDownloads;

    public $user;
    public $filters = [];
    public $includeFeeDetails = false;
    public $format = 'excel';
    public $isExporting = false;
    public $exportProgress = 0;
    public $exportStatus = '';

    protected $listeners = ['export-requested' => 'handleExportRequest'];

    public function mount()
    {
        $this->user = auth()->user();
    }

    public function handleExportRequest($data)
    {
        $this->filters = $data['filters'] ?? [];
        $this->format = $data['format'] ?? 'excel';
        $this->includeFeeDetails = $this->filters['include_fee_details'] ?? false;
        
        $this->export();
    }

    public function export()
    {
        // Vérifier les permissions d'export
        if ($this->user->role !== 'admin') {
            $this->dispatch('export-error', [
                'message' => 'Accès refusé. Seuls les administrateurs peuvent exporter les transactions.'
            ]);
            return;
        }

        $this->isExporting = true;
        $this->exportProgress = 0;
        $this->exportStatus = 'Préparation des données...';

        try {
            // Construire la requête avec les filtres
            $query = EnhancedTransaction::with([
                'sourceUser:id,name,email,role',
                'targetUser:id,name,email,role',
                'businessProfile:id,name'
            ]);

            // Appliquer les filtres
            $this->applyFilters($query);

            // Compter le total pour la progression
            $totalCount = $query->count();
            
            if ($totalCount === 0) {
                $this->dispatch('export-error', [
                    'message' => 'Aucune transaction trouvée avec les filtres appliqués.'
                ]);
                return;
            }

            $this->exportProgress = 25;
            $this->exportStatus = 'Génération du fichier...';

            // Générer le nom de fichier
            $filename = 'transactions_' . now()->format('Y-m-d_H-i-s') . '.' . ($this->format === 'excel' ? 'xlsx' : 'csv');

            // Créer l'export
            $export = new \App\Exports\TransactionExport(
                $query->orderBy('created_at', 'desc')->get(),
                $this->includeFeeDetails,
                $this->user
            );

            $this->exportProgress = 50;
            $this->exportStatus = 'Finalisation...';

            // Générer le fichier
            if ($this->format === 'excel') {
                Excel::store($export, $filename, 'public');
            } else {
                Excel::store($export, $filename, 'public', \Maatwebsite\Excel\Excel::CSV);
            }

            $this->exportProgress = 100;
            $this->exportStatus = 'Export terminé !';

            // Dispatcher l'événement de succès
            $this->dispatch('export-success', [
                'filename' => $filename,
                'download_url' => Storage::url($filename),
                'total_records' => $totalCount
            ]);

        } catch (\Exception $e) {
            $this->dispatch('export-error', [
                'message' => 'Erreur lors de l\'export: ' . $e->getMessage()
            ]);
        } finally {
            $this->isExporting = false;
        }
    }

    private function applyFilters($query): void
    {
        // Filtre par statut
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        // Filtre par type de transaction
        if (!empty($this->filters['type'])) {
            $query->where('transaction_type', $this->filters['type']);
        }

        // Filtre par plage de dates
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        // Filtre par utilisateur
        if (!empty($this->filters['user_id'])) {
            $query->where(function($q) {
                $q->where('source_user_id', $this->filters['user_id'])
                  ->orWhere('target_user_id', $this->filters['user_id']);
            });
        }

        // Filtre par business profile
        if (!empty($this->filters['business_profile_id'])) {
            $query->where('business_profile_id', $this->filters['business_profile_id']);
        }

        // Filtre par localisation
        if (!empty($this->filters['location'])) {
            $query->where('location', 'like', '%' . $this->filters['location'] . '%');
        }

        // Filtre par terme de recherche
        if (!empty($this->filters['search'])) {
            $query->where(function($q) {
                $q->where('transaction_reference', 'like', '%' . $this->filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $this->filters['search'] . '%')
                  ->orWhereHas('sourceUser', function($userQuery) {
                      $userQuery->where('name', 'like', '%' . $this->filters['search'] . '%')
                               ->orWhere('email', 'like', '%' . $this->filters['search'] . '%');
                  })
                  ->orWhereHas('targetUser', function($userQuery) {
                      $userQuery->where('name', 'like', '%' . $this->filters['search'] . '%')
                               ->orWhere('email', 'like', '%' . $this->filters['search'] . '%');
                  });
            });
        }

        // Filtre par montant minimum
        if (!empty($this->filters['amount_min'])) {
            $query->where('amount', '>=', $this->filters['amount_min']);
        }

        // Filtre par montant maximum
        if (!empty($this->filters['amount_max'])) {
            $query->where('amount', '<=', $this->filters['amount_max']);
        }

        // Filtre par devise
        if (!empty($this->filters['currency'])) {
            $query->where('currency', $this->filters['currency']);
        }
    }

    public function render()
    {
        return view('livewire.transaction-export');
    }
}

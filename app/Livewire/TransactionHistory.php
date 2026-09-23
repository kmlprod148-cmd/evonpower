<?php

namespace App\Livewire;

use App\Models\EnhancedTransaction;
use App\Models\EnhancedUser;
use App\Models\EnhancedBusinessProfile;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileDownloads;

class TransactionHistory extends Component
{
    use WithPagination, WithFileDownloads;

    // Constantes pour la configuration
    protected const CACHE_TTL_ACCESSIBLE_DATA = 3600; // 1 heure
    protected const CACHE_TTL_STATS = 300; // 5 minutes
    protected const DEFAULT_PER_PAGE = 15;
    protected const MAX_PER_PAGE = 100;

    // Propriétés de filtrage
    public $status = '';
    public $type = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $location = '';
    public $search = '';
    public $amountMin = '';
    public $amountMax = '';
    public $currency = '';
    public $userId = '';
    public $businessProfileId = '';
    
    // Propriétés d'affichage
    public $perPage = 15;
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';
    public $includeFeeDetails = true; // Afficher les détails par défaut
    
    // États de chargement
    public $isLoading = false;
    public $isExporting = false;
    
    // Propriétés de l'utilisateur
    public $user;
    public $accessibleUsers = [];
    public $accessibleBusinessProfiles = [];
    
    // Statistiques
    public $filterStats = [];
    
    // Détails de transaction pour le modal
    public $selectedTransaction = null;
    public $showTransactionModal = false;
    
    protected $queryString = [
        'status' => ['except' => ''],
        'type' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'location' => ['except' => ''],
        'search' => ['except' => ''],
        'amountMin' => ['except' => ''],
        'amountMax' => ['except' => ''],
        'currency' => ['except' => ''],
        'userId' => ['except' => ''],
        'businessProfileId' => ['except' => ''],
        'perPage' => ['except' => 15],
        'sortBy' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'includeFeeDetails' => ['except' => true] // Activé par défaut pour afficher les parts
    ];

    public function mount()
    {
        $this->user = auth()->user();
        $this->loadAccessibleData();
    }

    public function loadAccessibleData()
    {
        $cacheKey = "transaction_history_accessible_data:user_{$this->user->id}:role_{$this->user->role}";
        
        $data = Cache::remember($cacheKey, self::CACHE_TTL_ACCESSIBLE_DATA, function () {
            return $this->fetchAccessibleData();
        });
        
        $this->accessibleUsers = $data['users'];
        $this->accessibleBusinessProfiles = $data['business_profiles'];
    }

    /**
     * Récupère les données accessibles sans cache
     */
    private function fetchAccessibleData(): array
    {
        switch ($this->user->role) {
            case 'admin':
                return [
                    'users' => EnhancedUser::select('id', 'name', 'role')
                    ->orderBy('name')
                        ->get(),
                    'business_profiles' => EnhancedBusinessProfile::select('id', 'name')
                    ->orderBy('name')
                        ->get(),
                ];
                
            case 'integrator':
                $operatorIds = EnhancedUser::where('created_by', $this->user->id)
                    ->where('role', 'operator')
                    ->pluck('id')
                    ->toArray();
                
                $accessibleIds = array_merge([$this->user->id], $operatorIds);
                
                return [
                    'users' => EnhancedUser::whereIn('id', $accessibleIds)
                    ->select('id', 'name', 'role')
                    ->orderBy('name')
                        ->get(),
                    'business_profiles' => EnhancedBusinessProfile::whereIn('owner_id', $accessibleIds)
                    ->select('id', 'name')
                    ->orderBy('name')
                        ->get(),
                ];
                
            case 'operator':
                return [
                    'users' => collect([$this->user]),
                    'business_profiles' => EnhancedBusinessProfile::where('owner_id', $this->user->id)
                    ->orWhere('id', $this->user->business_profile_id)
                    ->select('id', 'name')
                    ->orderBy('name')
                        ->get(),
                ];
                
            default:
                return [
                    'users' => collect([$this->user]),
                    'business_profiles' => collect(),
                ];
        }
    }

    /**
     * Invalide le cache des données accessibles
     */
    public function invalidateAccessibleDataCache(): void
    {
        Cache::forget("transaction_history_accessible_data:user_{$this->user->id}:role_{$this->user->role}");
    }

    public function updated($property)
    {
        // Réinitialiser la pagination quand les filtres changent
        $filterProperties = [
            'status', 'type', 'dateFrom', 'dateTo', 'location', 
            'search', 'amountMin', 'amountMax', 'currency', 
            'userId', 'businessProfileId'
        ];
        
        if (in_array($property, $filterProperties)) {
            $this->resetPage();
            $this->isLoading = true;
        }
        
        // Validation du perPage
        if ($property === 'perPage') {
            $this->validatePerPage();
        }
    }

    /**
     * Valide le nombre d'éléments par page
     */
    private function validatePerPage(): void
    {
        if ($this->perPage < 5) {
            $this->perPage = 5;
        } elseif ($this->perPage > self::MAX_PER_PAGE) {
            $this->perPage = self::MAX_PER_PAGE;
        }
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters()
    {
        $this->status = '';
        $this->type = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->location = '';
        $this->search = '';
        $this->amountMin = '';
        $this->amountMax = '';
        $this->currency = '';
        $this->userId = '';
        $this->businessProfileId = '';
        $this->resetPage();
    }

    public function exportCsv()
    {
        $this->isExporting = true;
        $this->dispatch('export-requested', [
            'format' => 'csv',
            'filters' => $this->getFilters()
        ]);
        
        // Réinitialiser après un délai
        $this->dispatch('export-completed');
    }

    public function exportExcel()
    {
        $this->isExporting = true;
        $this->dispatch('export-requested', [
            'format' => 'excel',
            'filters' => $this->getFilters()
        ]);
        
        // Réinitialiser après un délai
        $this->dispatch('export-completed');
    }

    public function handleExportCompleted()
    {
        $this->isExporting = false;
    }

    private function getFilters(): array
    {
        return [
            'status' => $this->status,
            'type' => $this->type,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'location' => $this->location,
            'search' => $this->search,
            'amount_min' => $this->amountMin,
            'amount_max' => $this->amountMax,
            'currency' => $this->currency,
            'user_id' => $this->userId,
            'business_profile_id' => $this->businessProfileId,
            'include_fee_details' => $this->includeFeeDetails
        ];
    }

    public function render()
    {
        $this->isLoading = true;
        
        try {
            $query = $this->buildFilteredQuery();
            $transactions = $query->paginate($this->perPage);
            
            // Après pagination, charger les TransactionDetail pour chaque transaction
            // pour éviter les requêtes N+1 dans la vue
            $this->loadTransactionDetails($transactions);
            
            $this->filterStats = $this->calculateFilterStats($transactions);
            $this->isLoading = false;
            
        } catch (\Exception $e) {
            $this->isLoading = false;
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Erreur lors du chargement des transactions: ' . $e->getMessage()
            ]);
            
            // Retourner une collection vide en cas d'erreur
            $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                $this->perPage,
                1
            );
            $this->filterStats = $this->getEmptyStats();
        }

        return view('livewire.transaction-history', [
            'transactions' => $transactions,
            'transactionTypes' => $this->getTransactionTypes(),
            'statuses' => $this->getStatuses(),
            'currencies' => $this->getCurrencies()
        ]);
    }

    /**
     * Charge les TransactionDetail pour toutes les transactions après pagination
     * Optimise les requêtes en chargeant par batch
     */
    private function loadTransactionDetails($transactions): void
    {
        if ($transactions->isEmpty()) {
            return;
        }

        // Collecter toutes les références possibles
        $references = [];
        $transactionIds = [];
        $enhancedTransactionIds = [];
        
        foreach ($transactions as $transaction) {
            $enhancedTransactionIds[] = $transaction->id;
            
            if ($transaction->external_reference) {
                $references[] = $transaction->external_reference;
                if (is_numeric($transaction->external_reference)) {
                    $transactionIds[] = (int) $transaction->external_reference;
                }
            }
            if ($transaction->transaction_reference) {
                $references[] = $transaction->transaction_reference;
                if (is_numeric($transaction->transaction_reference)) {
                    $transactionIds[] = (int) $transaction->transaction_reference;
                }
            }
        }

        // Nettoyer les doublons
        $transactionIds = array_unique(array_filter($transactionIds));
        $references = array_unique(array_filter($references));
        
        // Charger tous les TransactionDetail en une seule requête batch
        $details = collect();
        
        if (!empty($transactionIds)) {
            // Chercher directement par transaction_id
            $detailsById = \App\Models\TransactionDetail::whereIn('transaction_id', $transactionIds)
                ->get()
                ->keyBy('transaction_id');
            $details = $details->merge($detailsById);
        }
        
        // Chercher via les références dans la table transactions
        if (!empty($references)) {
            $transactionMatches = \App\Models\Transaction::where(function($q) use ($references) {
                $q->whereIn('transaction_id', $references)
                  ->orWhereIn('reference_id', $references)
                  ->orWhereIn('id', $references);
            })->pluck('id');
            
            if ($transactionMatches->isNotEmpty()) {
                $detailsByRef = \App\Models\TransactionDetail::whereIn('transaction_id', $transactionMatches)
                    ->get()
                    ->keyBy('transaction_id');
                $details = $details->merge($detailsByRef);
            }
        }

        // Mapper les TransactionDetail aux EnhancedTransaction
        $detailsMap = [];
        foreach ($transactions as $transaction) {
            $detail = null;
            
            // Méthode 1: Chercher par external_reference (si c'est un ID de Transaction)
            if ($transaction->external_reference && is_numeric($transaction->external_reference)) {
                $detail = $details->get((int) $transaction->external_reference);
            }
            
            // Méthode 2: Chercher par transaction_reference (si c'est un ID de Transaction)
            if (!$detail && $transaction->transaction_reference && is_numeric($transaction->transaction_reference)) {
                $detail = $details->get((int) $transaction->transaction_reference);
            }
            
            // Méthode 3: Chercher directement via l'ID EnhancedTransaction dans TransactionDetail
            // (si l'ID EnhancedTransaction correspond à un ID Transaction)
            if (!$detail && $transaction->id) {
                $detail = $details->get($transaction->id);
            }
            
            // Si trouvé, stocker dans le map
            if ($detail) {
                $detailsMap[$transaction->id] = $detail;
            }
        }

        // Associer les TransactionDetail aux transactions EnhancedTransaction
        foreach ($transactions as $transaction) {
            if (isset($detailsMap[$transaction->id])) {
                $detail = $detailsMap[$transaction->id];
                // Mettre en cache pour éviter les requêtes répétées
                $transaction->cachedTransactionDetail = $detail;
                // Définir une relation virtuelle
                $transaction->transactionDetail = $detail;
            }
        }
    }

    /**
     * Construit la requête filtrée avec toutes les optimisations
     */
    private function buildFilteredQuery()
    {
        $query = EnhancedTransaction::accessibleByUser($this->user);

        // Appliquer les filtres avec méthodes dédiées pour meilleure lisibilité
        $this->applyStatusFilter($query);
        $this->applyTypeFilter($query);
        $this->applyDateFilters($query);
        $this->applyLocationFilter($query);
        $this->applySearchFilter($query);
        $this->applyAmountFilters($query);
        $this->applyCurrencyFilter($query);
        $this->applyUserFilter($query);
        $this->applyBusinessProfileFilter($query);

        // Appliquer le tri
        $this->applySorting($query);

        // Optimisation : eager loading avec sélection de colonnes
        $this->applyEagerLoading($query);

        return $query;
    }

    /**
     * Applique le filtre de statut
     */
    private function applyStatusFilter($query): void
    {
        if ($this->status) {
            $query->where('status', $this->status);
        }
        }

    /**
     * Applique le filtre de type
     */
    private function applyTypeFilter($query): void
    {
        if ($this->type) {
            $query->where('transaction_type', $this->type);
        }
        }

    /**
     * Applique les filtres de date
     */
    private function applyDateFilters($query): void
    {
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
        }

    /**
     * Applique le filtre de localisation
     */
    private function applyLocationFilter($query): void
    {
        if ($this->location) {
            $query->where('location', 'like', '%' . $this->location . '%');
        }
    }

    /**
     * Applique le filtre de recherche (optimisé)
     */
    private function applySearchFilter($query): void
    {
        if (!$this->search) {
            return;
        }

        $searchTerm = '%' . $this->search . '%';
        $query->where(function($q) use ($searchTerm) {
            $q->where('transaction_reference', 'like', $searchTerm)
              ->orWhere('description', 'like', $searchTerm)
              ->orWhereHas('sourceUser', function($userQuery) use ($searchTerm) {
                  $userQuery->where('name', 'like', $searchTerm)
                           ->orWhere('email', 'like', $searchTerm);
              })
              ->orWhereHas('targetUser', function($userQuery) use ($searchTerm) {
                  $userQuery->where('name', 'like', $searchTerm)
                           ->orWhere('email', 'like', $searchTerm);
                  });
            });
        }

    /**
     * Applique les filtres de montant
     */
    private function applyAmountFilters($query): void
    {
        if ($this->amountMin) {
            $query->where('amount', '>=', (float) $this->amountMin);
        }

        if ($this->amountMax) {
            $query->where('amount', '<=', (float) $this->amountMax);
        }
        }

    /**
     * Applique le filtre de devise
     */
    private function applyCurrencyFilter($query): void
    {
        if ($this->currency) {
            $query->where('currency', $this->currency);
        }
        }

    /**
     * Applique le filtre d'utilisateur
     */
    private function applyUserFilter($query): void
    {
        if ($this->userId) {
            $query->where(function($q) {
                $q->where('source_user_id', $this->userId)
                  ->orWhere('target_user_id', $this->userId);
            });
        }
        }

    /**
     * Applique le filtre de business profile
     */
    private function applyBusinessProfileFilter($query): void
    {
        if ($this->businessProfileId) {
            $query->where('business_profile_id', $this->businessProfileId);
        }
    }

    /**
     * Applique le tri
     */
    private function applySorting($query): void
    {
        // Validation du champ de tri pour éviter les injections SQL
        $allowedSortFields = [
            'created_at', 'transaction_reference', 'transaction_type', 
            'amount', 'status', 'currency'
        ];
        
        if (!in_array($this->sortBy, $allowedSortFields)) {
            $this->sortBy = 'created_at';
        }
        
        // Validation de la direction
        $this->sortDirection = strtolower($this->sortDirection) === 'asc' ? 'asc' : 'desc';
        
        $query->orderBy($this->sortBy, $this->sortDirection);
    }

    /**
     * Applique l'eager loading optimisé
     * Note: TransactionDetail est chargé dynamiquement via getTransactionDetail() pour éviter N+1
     */
    private function applyEagerLoading($query): void
    {
        $query->with([
            'sourceUser:id,name,role,email',
            'targetUser:id,name,role,email',
            'businessProfile:id,name',
            'feeLogs' => function($q) {
                if ($this->user->role !== 'admin') {
                    $q->where('fee_category', '!=', 'admin');
                }
                // Limiter le nombre de logs chargés
                $q->latest()->limit(10);
            }
        ]);
        
        // Note: TransactionDetail est chargé à la demande via getTransactionDetail()
        // pour éviter les problèmes de liaison entre EnhancedTransaction et Transaction
    }

    /**
     * Calcule les statistiques de filtrage de manière optimisée
     */
    private function calculateFilterStats($transactions): array
    {
        $totalAdminShare = 0;
        $totalIntegratorShare = 0;
        $totalOperatorShare = 0;
        $totalAmount = 0;
        
        // Parcourir uniquement les transactions de la page actuelle
        foreach ($transactions as $transaction) {
            $totalAmount += $transaction->amount ?? 0;
            
            $shares = $transaction->shares ?? [];
            
            if (($shares['source'] ?? '') === 'transaction_detail') {
                $totalAdminShare += $shares['admin_share'] ?? 0;
                $totalIntegratorShare += $shares['integrator_share'] ?? 0;
                $totalOperatorShare += $shares['operator_share'] ?? 0;
            } else {
                $totalAdminShare += $transaction->admin_fee ?? 0;
                $totalIntegratorShare += $transaction->integrator_fee ?? 0;
                $totalOperatorShare += $transaction->operator_fee ?? 0;
            }
        }
        
        return [
            'total_transactions' => $transactions->total(),
            'total_amount' => round($totalAmount, 2),
            'total_admin_share' => round($totalAdminShare, 2),
            'total_integrator_share' => round($totalIntegratorShare, 2),
            'total_operator_share' => round($totalOperatorShare, 2),
            'total_fees' => round($totalAdminShare + $totalIntegratorShare, 2),
            'by_status' => $transactions->groupBy('status')->map->count(),
            'by_type' => $transactions->groupBy('transaction_type')->map->count()
        ];
    }

    /**
     * Retourne des statistiques vides en cas d'erreur
     */
    private function getEmptyStats(): array
    {
        return [
            'total_transactions' => 0,
            'total_amount' => 0,
            'total_admin_share' => 0,
            'total_integrator_share' => 0,
            'total_operator_share' => 0,
            'total_fees' => 0,
            'by_status' => collect(),
            'by_type' => collect()
        ];
    }

    private function getTransactionTypes(): array
    {
        return [
            'admin_to_integrator' => 'Admin → Integrator',
            'integrator_to_operator' => 'Integrator → Operator',
            'recharge' => 'Recharge',
            'refund' => 'Remboursement',
            'commission' => 'Commission',
            'reservation' => 'Réservation',
            'reservation_payment' => 'Réservation (Paiement)',
            'wallet' => 'Wallet',
            'client' => 'Client',
            'admin' => 'Administration',
            'activation_fee' => 'Frais d\'activation',
            'admin_integrator' => 'Admin ↔ Intégrateur',
            'integrator_operator' => 'Intégrateur ↔ Opérateur',
        ];
    }

    private function getStatuses(): array
    {
        return [
            'completed' => 'Terminé',
            'pending' => 'En attente',
            'canceled' => 'Annulé',
            'failed' => 'Échoué'
        ];
    }

    private function getCurrencies(): array
    {
        return [
            'EUR' => 'EUR',
            'USD' => 'USD',
            'GBP' => 'GBP',
            'MAD' => 'MAD'
        ];
    }

    /**
     * Obtenir le label du type de transaction
     */
    public function getTransactionTypeLabel(string $type): string
    {
        $types = [
            'admin_to_integrator' => 'Admin → Integrator',
            'integrator_to_operator' => 'Integrator → Operator',
            'recharge' => 'Recharge',
            'refund' => 'Remboursement',
            'commission' => 'Commission',
            'reservation' => 'Réservation',
            'reservation_payment' => 'Réservation',
            'wallet' => 'Wallet',
            'client' => 'Client',
            'admin' => 'Administration',
            'activation_fee' => 'Frais d\'activation',
            'admin_integrator' => 'Admin ↔ Intégrateur',
            'integrator_operator' => 'Intégrateur ↔ Opérateur',
        ];
        
        return $types[$type] ?? ucfirst($type);
    }

    /**
     * Obtenir la classe CSS pour le badge de statut
     * Gère les chaînes et les enums TransactionStatus
     */
    public function getStatusBadgeClass($status): string
    {
        // Si c'est un enum, obtenir sa valeur
        if (is_object($status) && method_exists($status, 'value')) {
            $status = $status->value;
        } elseif (is_object($status) && method_exists($status, 'color')) {
            // Si c'est un enum avec méthode color(), utiliser directement
            $color = $status->color();
            return match($color) {
                'success' => 'bg-success',
                'warning' => 'bg-warning',
                'danger' => 'bg-danger',
                'info' => 'bg-info',
                default => 'bg-secondary'
            };
        }
        
        // Convertir en chaîne si nécessaire
        $statusStr = (string) $status;
        
        $classes = [
            'completed' => 'bg-success',
            'pending' => 'bg-warning',
            'canceled' => 'bg-danger',
            'cancelled' => 'bg-danger',
            'failed' => 'bg-danger',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'in_progress' => 'bg-info',
            'paid' => 'bg-success',
        ];
        
        return $classes[$statusStr] ?? 'bg-secondary';
    }

    /**
     * Obtenir le label du statut
     * Gère les chaînes et les enums TransactionStatus
     */
    public function getStatusLabel($status): string
    {
        // Si c'est un enum, essayer d'utiliser sa méthode label()
        if (is_object($status) && method_exists($status, 'label')) {
            return $status->label();
        }
        
        // Si c'est un enum avec value, obtenir la valeur
        if (is_object($status) && method_exists($status, 'value')) {
            $status = $status->value;
        }
        
        // Convertir en chaîne si nécessaire
        $statusStr = (string) $status;
        
        // Essayer de convertir en enum TransactionStatus si disponible
        try {
            if (class_exists(\App\Enums\TransactionStatus::class)) {
                $statusEnum = \App\Enums\TransactionStatus::from($statusStr);
                return $statusEnum->label();
            }
        } catch (\ValueError $e) {
            // Si la conversion échoue, continuer avec le mapping manuel
        }
        
        // Mapping manuel des labels
        $labels = [
            'completed' => 'Terminé',
            'pending' => 'En attente',
            'canceled' => 'Annulé',
            'cancelled' => 'Annulé',
            'failed' => 'Échoué',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'in_progress' => 'En cours',
            'paid' => 'Payé',
        ];
        
        return $labels[$statusStr] ?? ucfirst($statusStr);
    }

    /**
     * Obtenir les informations du badge pour un rôle
     */
    public function getRoleBadge(string $role): array
    {
        $badges = [
            'admin' => ['class' => 'bg-danger', 'text' => 'Admin'],
            'integrator' => ['class' => 'bg-warning', 'text' => 'Intégrateur'],
            'operator' => ['class' => 'bg-info', 'text' => 'Opérateur'],
            'partner' => ['class' => 'bg-primary', 'text' => 'Partenaire'],
            'client' => ['class' => 'bg-secondary', 'text' => 'Client'],
            'user' => ['class' => 'bg-secondary', 'text' => 'Utilisateur'],
        ];
        
        return $badges[$role] ?? ['class' => 'bg-dark', 'text' => ucfirst($role)];
    }

    /**
     * Obtenir l'URL vers la page de détails d'une transaction pour les admins
     */
    public function getTransactionDetailsUrl($transaction): ?string
    {
        if ($this->user->role !== 'admin') {
            return null;
        }

        // Trouver l'ID de la transaction réelle (Transaction) depuis EnhancedTransaction
        $realTransactionId = null;
        
        if ($transaction->external_reference && is_numeric($transaction->external_reference)) {
            $realTransactionId = (int) $transaction->external_reference;
        } elseif ($transaction->transaction_reference && is_numeric($transaction->transaction_reference)) {
            $realTransactionId = (int) $transaction->transaction_reference;
        } elseif ($transaction->id) {
            // Essayer avec l'ID EnhancedTransaction au cas où il correspondrait
            $realTransactionId = $transaction->id;
        }

        if (!$realTransactionId) {
            return null;
        }

        try {
            return route('transactions.show', $realTransactionId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Vérifier si la transaction est un crédit
     * Basé sur le montant et la position de l'utilisateur dans la transaction
     */
    public function isCreditTransaction($transaction): bool
    {
        // Si le montant est négatif, c'est un débit
        if ($transaction->amount < 0) {
            return false;
        }
        
        // Si le montant est positif ou nul, déterminer selon le rôle et la position
        // Une transaction est un crédit si l'utilisateur actuel est le destinataire
        if ($transaction->target_user_id === $this->user->id) {
            return true;
        }
        
        // Pour les types de transaction spécifiques
        $creditTypes = ['recharge', 'refund', 'commission'];
        if (in_array($transaction->transaction_type, $creditTypes)) {
            return true;
        }
        
        // Pour les transactions de type reservation, vérifier selon la position
        if (in_array($transaction->transaction_type, ['reservation', 'reservation_payment'])) {
            // Si l'utilisateur est la source (celui qui paie), c'est un débit
            if ($transaction->source_user_id === $this->user->id) {
                return false;
            }
            // Si l'utilisateur est la cible (celui qui reçoit), c'est un crédit
            if ($transaction->target_user_id === $this->user->id) {
                return true;
            }
            
            // Sinon, selon le rôle et les parts
            // Si l'utilisateur reçoit une part (admin, intégrateur ou opérateur), c'est un crédit
            $transactionDetail = $transaction->transactionDetail ?? $transaction->cachedTransactionDetail ?? null;
            if ($transactionDetail) {
                if ($this->user->role === 'admin' && ($transactionDetail->admin_share_amount ?? 0) > 0) {
                    return true;
                }
                if ($this->user->role === 'integrator' && ($transactionDetail->integrator_share_amount ?? 0) > 0) {
                    return true;
                }
                if (($this->user->role === 'operator' || $this->user->role === 'partner') && ($transactionDetail->operator_share_amount ?? 0) > 0) {
                    return true;
                }
            }
        }
        
        // Par défaut, si l'utilisateur n'est pas la source, considérer comme crédit
        return $transaction->source_user_id !== $this->user->id;
    }

    /**
     * Afficher les détails d'une transaction
     */
    public function viewTransaction($transactionId)
    {
        // L'admin peut toujours voir les détails
        if ($this->user->role !== 'admin' && !$this->canViewTransaction($transactionId)) {
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Vous n\'avez pas l\'autorisation de voir cette transaction'
            ]);
            return;
        }

        $transaction = EnhancedTransaction::with([
            'sourceUser:id,name,role,email',
            'targetUser:id,name,role,email',
            'businessProfile:id,name',
            'feeLogs'
        ])->find($transactionId);
        
        if (!$transaction) {
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Transaction non trouvée'
            ]);
            return;
        }

        $this->selectedTransaction = $transaction;
        $this->showTransactionModal = true;
        
        // Émettre un événement pour ouvrir le modal Bootstrap
        $this->dispatch('open-transaction-modal');
    }

    /**
     * Vérifier si l'utilisateur peut voir une transaction
     */
    public function canViewTransaction($transactionId): bool
    {
        // L'admin peut toujours voir toutes les transactions
        if ($this->user->role === 'admin') {
            return true;
        }

        $transaction = EnhancedTransaction::find($transactionId);
        
        if (!$transaction) {
            return false;
        }

        // Vérifier l'accès selon le rôle
        switch ($this->user->role) {
            case 'integrator':
                // L'intégrateur peut voir ses transactions + celles de ses opérateurs
                $operatorIds = EnhancedUser::where('created_by', $this->user->id)
                    ->where('role', 'operator')
                    ->pluck('id')
                    ->toArray();
                
                $accessibleIds = array_merge([$this->user->id], $operatorIds);
                return in_array($transaction->source_user_id, $accessibleIds) || 
                       in_array($transaction->target_user_id, $accessibleIds);
                
            case 'operator':
                // L'opérateur peut voir seulement ses propres transactions
                return $transaction->source_user_id === $this->user->id || 
                       $transaction->target_user_id === $this->user->id;
                
            default:
                return false;
        }
    }

    /**
     * Fermer le modal de transaction
     */
    public function closeTransactionModal()
    {
        $this->showTransactionModal = false;
        $this->selectedTransaction = null;
    }

    /**
     * Vérifier si une transaction peut être annulée
     */
    public function canCancelTransaction($transaction): bool
    {
        // Seuls les admins et intégrateurs peuvent annuler des transactions
        if (!in_array($this->user->role, ['admin', 'integrator'])) {
            return false;
        }
        
        // Seules les transactions en attente peuvent être annulées
        return $transaction->status === 'pending';
    }

    /**
     * Annuler une transaction
     */
    public function cancelTransaction($transactionId)
    {
        try {
            $transaction = EnhancedTransaction::findOrFail($transactionId);

            if (!$this->canCancelTransaction($transaction)) {
                $this->dispatch('show-alert', [
                    'type' => 'error',
                    'message' => 'Cette transaction ne peut pas être annulée'
                ]);
                return;
            }

            DB::beginTransaction();
            
            $transaction->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);

            // Invalider le cache si nécessaire
            $this->invalidateAccessibleDataCache();

            DB::commit();

            $this->dispatch('show-alert', [
                'type' => 'success',
                'message' => 'Transaction annulée avec succès'
            ]);
            
            // Rafraîchir la liste
            $this->dispatch('$refresh');
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Transaction non trouvée'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Erreur lors de l\'annulation de transaction', [
                'transaction_id' => $transactionId,
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('show-alert', [
                'type' => 'error',
                'message' => 'Erreur lors de l\'annulation: ' . $e->getMessage()
            ]);
        }
    }
}

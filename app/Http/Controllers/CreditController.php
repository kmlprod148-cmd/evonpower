<?php

namespace App\Http\Controllers;

use App\Services\CreditService;
use App\Services\CreditClientScopeService;
use App\Models\User;
use App\Models\ClientUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreditController extends Controller
{
    protected $creditService;

    public function __construct(CreditService $creditService)
    {
        $this->middleware('auth');
        $this->creditService = $creditService;
    }

    protected function getClientScopeService(): ?CreditClientScopeService
    {
        try {
            return app(CreditClientScopeService::class);
        } catch (\Throwable $e) {
            Log::warning('CreditClientScopeService unavailable', ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function canAccessClient(\Illuminate\Contracts\Auth\Authenticatable $manager, int $clientId): bool
    {
        if ($manager->hasRole(['admin', 'super_admin'])) {
            return true;
        }
        $service = $this->getClientScopeService();
        return $service ? $service->canAccessClient($manager, $clientId) : false;
    }

    protected function isCreditManager(?\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        return (bool) ($user
            && method_exists($user, 'hasRole')
            && $user->hasRole(['admin', 'super_admin', 'integrator', 'operator', 'partner']));
    }

    protected function isCreditClient(?\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user instanceof ClientUser) {
            return true;
        }

        if (!method_exists($user, 'getRoleNames')) {
            return false;
        }

        $roles = $user->getRoleNames()
            ->map(fn ($role) => strtolower((string) $role))
            ->toArray();

        $systemRoles = ['admin', 'super_admin', 'integrator', 'operator', 'partner'];
        $clientRoles = ['user', 'client'];

        $hasSystemRole = !empty(array_intersect($roles, $systemRoles));
        $hasClientRole = !empty(array_intersect($roles, $clientRoles));

        return !$hasSystemRole && ($hasClientRole || empty($roles));
    }

    protected function resolveCreditUserId(\Illuminate\Contracts\Auth\Authenticatable $user): ?int
    {
        if ($user instanceof ClientUser) {
            return $user->user_id ?? $user->user?->id;
        }

        return $user->id ?? null;
    }

    protected function getClientsForSelector(\Illuminate\Contracts\Auth\Authenticatable $user, bool $canManageCredits): \Illuminate\Support\Collection
    {
        if (!$canManageCredits) {
            return collect([]);
        }
        $service = $this->getClientScopeService();
        if ($service) {
            try {
                return $service->getRelatedClientsQuery($user)->get();
            } catch (\Throwable $e) {
                Log::warning('CreditClientScopeService getRelatedClientsQuery failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        // Fallback : tous les clients (user + client = utilisateurs finaux)
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', ['user', 'client']))
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtenir le solde de crédit de l'utilisateur connecté (API)
     * Utilise ClientBalanceService pour une source unique de vérité
     */
    public function getBalanceApi(Request $request)
    {
        $service = app(\App\Services\ClientBalanceService::class);
        $result = $service->toApiResponse();

        if ($result['success']) {
            return response()->json(array_merge($result, [
                'wallet_id' => Auth::user()?->getOrCreateWallet()?->id ?? null,
            ]));
        }

        return response()->json($result, ($result['message'] ?? '') === 'Non authentifié' ? 401 : 500);
    }

    /**
     * Afficher la page de gestion des crédits
     * Accessible aux clients (leur propre crédit) et aux admins/intégrateurs/opérateurs/partenaires (leurs clients)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            Log::warning('CreditController@index: No authenticated user, redirecting to login');
            return redirect()->route('login');
        }
        
        try {
            $canManageCredits = $this->isCreditManager($user);
            $isClient = $this->isCreditClient($user);
            
            if (!$canManageCredits && !$isClient) {
                abort(403, 'Accès non autorisé.');
            }

            // Load user relationship for ClientUser
            if ($user instanceof ClientUser) {
                $user->load('user');
            }

            $selectedUserId = null;
            $balance = 0;

            // Pour les clients (user/client) : leur propre solde et historique
            // Pour les gestionnaires : possibilité de sélectionner un client (filtré par portée)
            if ($isClient && !$canManageCredits) {
                try {
                    // Pour les ClientUser, utiliser le User associé pour les crédits
                    $creditUser = $user instanceof \App\Models\ClientUser ? $user->user : $user;
                    if ($creditUser) {
                        $wallet = $creditUser->getOrCreateWallet();
                        $wallet->refresh();
                        $balance = $wallet->balance ?? 0;
                        $selectedUserId = $creditUser->id;
                    } else {
                        $balance = 0;
                        $selectedUserId = null;
                    }
                } catch (\Throwable $e) {
                    Log::error('Error loading client wallet', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                    $balance = 0;
                    $selectedUserId = null;
                }
            } else {
                $selectedUserId = $request->input('user_id');
                if ($selectedUserId) {
                    $selectedUser = User::find($selectedUserId);
                    if ($selectedUser && $this->isCreditClient($selectedUser) && $this->canAccessClient($user, (int) $selectedUserId)) {
                        try {
                            $wallet = $selectedUser->wallet ?? $selectedUser->getOrCreateWallet();
                            $wallet->refresh();
                            $balance = $wallet->balance ?? 0;
                        } catch (\Throwable $e) {
                            Log::error('Error loading selected user wallet', [
                                'selected_user_id' => $selectedUserId,
                                'error' => $e->getMessage(),
                            ]);
                            $balance = 0;
                        }
                    } else {
                        $balance = 0;
                        $selectedUserId = null;
                    }
                } else {
                    $balance = 0;
                }
            }

            // Récupérer l'historique des crédits
            $filters = $request->only(['type', 'date_from', 'date_to', 'per_page', 'user_id']);
            try {
                $creditHistory = $this->creditService->getCreditHistory($selectedUserId, $filters);
                $statistics = $this->creditService->getCreditStatistics($selectedUserId);
            } catch (\Throwable $e) {
                Log::error('CreditService failed in index', [
                    'user_id' => $user->id,
                    'selected_user_id' => $selectedUserId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $creditHistory = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
                $statistics = ['total_credits' => 0, 'total_transactions' => 0, 'by_type' => []];
            }

            // Liste des clients pour le sélecteur (filtrée par portée pour intégrateur/opérateur/partenaire)
            $clients = $this->getClientsForSelector($user, $canManageCredits);

            $isAdmin = $canManageCredits; // Pour la vue : admin, intégrateur, opérateur, partenaire
            return view('credits.index', compact('balance', 'creditHistory', 'statistics', 'filters', 'isAdmin', 'isClient', 'clients', 'selectedUserId'));
        } catch (\Throwable $e) {
            Log::error('CreditController@index error', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Une erreur est survenue lors du chargement de la page.',
                    'message' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Une erreur est survenue. Veuillez réessayer.');
        }
    }

    /**
     * Ajouter du crédit
     * - Pour les clients : crée une demande de crédit (nécessite approbation admin)
     * - Pour les admins/intégrateurs : ajoute directement le crédit au client sélectionné
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $canManageCredits = $this->isCreditManager($user);
        $isClient = $this->isCreditClient($user);
        
        if (!$canManageCredits && !$isClient) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.'
            ], 403);
        }

        // Validation selon le rôle
        if ($canManageCredits) {
            $validated = $request->validate([
                'user_id' => [
                    'required',
                    'exists:users,id',
                    function ($attribute, $value, $fail) use ($user) {
                        $selectedUser = User::find($value);
                        if (!$selectedUser || !$this->isCreditClient($selectedUser)) {
                            $fail('Le crédit ne peut être ajouté qu\'à un client identifié.');
                        }
                        if (!$this->canAccessClient($user, (int) $value)) {
                            $fail('Vous n\'avez pas accès à ce client.');
                        }
                    },
                ],
                'amount' => 'required|numeric|min:0.01',
                'type' => 'required|in:bonus,manuel,automatique',
                'commentaire' => 'nullable|string|max:1000',
            ]);
        } else {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'type' => 'required|in:bonus,manuel,automatique',
                'commentaire' => 'nullable|string|max:1000',
            ]);
            
            $validated['user_id'] = $this->resolveCreditUserId($user);
        }

        try {
            if ($canManageCredits) {
                // Les admins ajoutent directement le crédit
                $result = $this->creditService->addCredit(
                    $validated['user_id'],
                    $validated['amount'],
                    $validated['type'],
                    $validated['commentaire'] ?? null,
                    $user->id
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Crédit ajouté avec succès.',
                    'data' => $result,
                ]);
            } else {
                // Les clients créent une demande
                if (!$validated['user_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Impossible de créer la demande de crédit - compte utilisateur non configuré.',
                    ], 400);
                }

                $creditRequestService = app(\App\Services\CreditRequestService::class);
                $result = $creditRequestService->createRequest(
                    $validated['user_id'],
                    $validated['amount'],
                    $validated['type'],
                    $validated['commentaire'] ?? null
                );

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'ajout/demande de crédit', [
                'error' => $e->getMessage(),
                'request' => $validated,
                'is_admin' => $canManageCredits,
            ]);

            return response()->json([
                'success' => false,
                'message' => $canManageCredits 
                    ? 'Erreur lors de l\'ajout du crédit: ' . $e->getMessage()
                    : 'Erreur lors de la création de la demande: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer l'historique des crédits (API)
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'super_admin', 'integrator', 'operator', 'partner'])) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.'
            ], 403);
        }

        $filters = $request->only(['user_id', 'type', 'created_by', 'date_from', 'date_to', 'per_page']);
        
        $creditHistory = $this->creditService->getCreditHistory($filters['user_id'] ?? null, $filters);

        return response()->json([
            'success' => true,
            'data' => $creditHistory,
        ]);
    }

    /**
     * Obtenir les statistiques des crédits (API)
     */
    public function statistics(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'super_admin', 'integrator', 'operator', 'partner'])) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.'
            ], 403);
        }

        $userId = $request->input('user_id');
        $statistics = $this->creditService->getCreditStatistics($userId);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Afficher la page de gestion des crédits pour le client connecté
     * Intégré dans le profil client
     */
    public function clientProfile(Request $request)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur est un client (user ou client)
        if (!$this->isCreditClient($user)) {
            abort(403, 'Accès non autorisé. Seuls les clients peuvent accéder à cette page.');
        }

        // Récupérer le wallet et le solde (refresh pour garantir cohérence)
        $creditUser = $user instanceof ClientUser ? $user->user : $user;
        if (!$creditUser) {
            abort(400, 'Compte utilisateur non configurÃ©.');
        }

        $wallet = $creditUser->wallet ?? $creditUser->getOrCreateWallet();
        $wallet->refresh();
        $balance = $wallet->balance ?? 0;

        // Récupérer l'historique des crédits du client
        $filters = $request->only(['type', 'date_from', 'date_to', 'per_page']);
        $creditHistory = $this->creditService->getCreditHistory($creditUser->id, $filters);
        
        // Récupérer les statistiques du client
        $statistics = $this->creditService->getCreditStatistics($creditUser->id);

        return view('profile.credits', compact('balance', 'creditHistory', 'statistics', 'filters'));
    }
}

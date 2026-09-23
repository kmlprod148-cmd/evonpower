<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use App\Models\User;
use App\Services\TransactionCalculator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TransactionControllerSimple extends Controller
{
    protected $transactionCalculator;

    public function __construct(TransactionCalculator $transactionCalculator)
    {
        $this->middleware('auth');
        $this->transactionCalculator = $transactionCalculator;
    }

    /**
     * Display a listing of the transactions.
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            
            // Vérifier que l'utilisateur est authentifié
            if (!$user) {
                return redirect()->route('login')->with('error', 'Vous devez être connecté pour accéder à cette page.');
            }
            
            $filters = $request->only([
                'transaction_type',
                'transaction_category',
                'business_profile_id',
                'business_profile_owner_type',
                'start_date',
                'end_date',
                'status',
                'per_page'
            ]);

            // Construire la requête de base
            $query = Transaction::query();

            // Appliquer les filtres selon le rôle de l'utilisateur
            if ($user->hasRole(['admin', 'super_admin'])) {
                // Admin voit toutes les transactions
            } elseif ($user->hasRole('integrator')) {
                // Integrators can view all transactions EXCEPT those for charging points belonging to other integrators
                $query->where(function($q) use ($user) {
                    // Exclure uniquement les transactions pour les charging points d'autres intégrateurs
                    $q->whereDoesntHave('chargingPoint', function($cpQuery) use ($user) {
                        $cpQuery->whereNotNull('integrator_id')
                               ->where('integrator_id', '!=', $user->integrator_id);
                    })
                    // OR transactions sans charging point associé (accessibles)
                    ->orWhereNull('charging_point_id');
                });
            } elseif ($user->hasRole('partner')) {
                $query->whereHas('chargingPoint', function ($q) use ($user) {
                    $q->where('partner_id', $user->partner_id);
                });
            } elseif ($user->hasRole('operator')) {
                // Operators can view transactions for their own charging points and their own transactions
                $query->where(function($q) use ($user) {
                    // Their own transactions (as user)
                    $q->where('user_id', $user->id)
                      // OR transactions for charging points created by this operator
                      ->orWhereHas('chargingPoint', function($cpQuery) use ($user) {
                          $cpQuery->where(function($cpSubQuery) use ($user) {
                              $cpSubQuery->where('created_by', $user->id)
                                        ->orWhere('created_by_id', $user->id)
                                        ->orWhere('user_id', $user->id);
                          });
                      });
                });
            } else {
                // Utilisateurs normaux voient seulement leurs transactions
                $query->whereHas('reservation', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            // Appliquer les filtres
            if (!empty($filters['transaction_type'])) {
                $query->where('transaction_type', $filters['transaction_type']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['business_profile_id'])) {
                $query->where('business_profile_id', $filters['business_profile_id']);
            }

            if (!empty($filters['start_date'])) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            }

            if (!empty($filters['end_date'])) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            }

            // Charger les relations nécessaires
            $query->with([
                'chargingPoint',
                'reservation.user',
                'businessProfile'
            ]);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Get business profiles for filters
            $businessProfiles = BusinessProfile::orderBy('name')->get();

            return view('transactions.index', compact('transactions', 'businessProfiles', 'filters', 'user'));
        } catch (\Exception $e) {
            Log::error('Error loading transactions: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement des transactions: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Vous devez être connecté pour accéder à cette page.');
            }

            $chargingPoints = ChargingPoint::orderBy('name')->get();
            $users = User::orderBy('name')->get();
            $businessProfiles = BusinessProfile::orderBy('name')->get();

            return view('transactions.create', compact('chargingPoints', 'users', 'businessProfiles'));
        } catch (\Exception $e) {
            Log::error('Error loading create form: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement du formulaire: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created transaction in storage.
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Vous devez être connecté pour accéder à cette page.');
            }

            $validatedData = $request->validate([
                'transaction_id' => 'required|string|max:255|unique:transactions,transaction_id',
                'transaction_type' => 'required|string|in:client,admin_integrator,integrator_operator',
                'charging_point_id' => 'required|exists:charging_points,id',
                'user_id' => 'nullable|exists:users,id',
                'price_total' => 'required|numeric|min:0',
                'price_energy' => 'nullable|numeric|min:0',
                'price_time' => 'nullable|numeric|min:0',
                'price_service' => 'nullable|numeric|min:0',
                'price_tax' => 'nullable|numeric|min:0',
                'activation_fee' => 'nullable|numeric|min:0',
                'amount' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|max:3',
                'business_profile_id' => 'nullable|exists:business_profiles,id',
                'status' => 'required|string|in:pending,completed,cancelled,failed',
                'admin_commission' => 'nullable|numeric|min:0',
                'integrator_commission' => 'nullable|numeric|min:0',
                'partner_commission' => 'nullable|numeric|min:0',
                'start_timestamp' => 'nullable|date',
                'stop_timestamp' => 'nullable|date',
                'meter_start' => 'nullable|numeric|min:0',
                'meter_stop' => 'nullable|numeric|min:0',
                'description' => 'nullable|string|max:1000',
                'notes' => 'nullable|string|max:1000',
            ]);

            $transaction = Transaction::create($validatedData);

            // Calculer et sauvegarder la répartition automatique
            try {
                $repartition = $this->transactionCalculator->calculateAndSave($transaction);
                Log::info('TransactionControllerSimple - répartition calculée', [
                    'transaction_id' => $transaction->id,
                    'repartition' => $repartition
                ]);
            } catch (\Exception $e) {
                Log::error('TransactionControllerSimple - erreur calcul répartition', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
            }

            return redirect()->route('transactions.show', $transaction->id)
                ->with('success', 'Transaction créée avec succès.');
        } catch (\Exception $e) {
            Log::error('Error creating transaction: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la création de la transaction: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified transaction.
     */
    public function show($id)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Vous devez être connecté pour accéder à cette page.');
            }

            $transaction = Transaction::with([
                'chargingPoint',
                'reservation.user',
                'businessProfile'
            ])->findOrFail($id);

            // Vérifier les permissions
            if (!$this->canViewTransaction($user, $transaction)) {
                return redirect()->route('transactions.index')
                    ->with('error', 'Vous n\'avez pas l\'autorisation de voir cette transaction.');
            }

            // Calculer les parts et frais pour la vue
            $adminShare = $transaction->admin_commission ?? 0;
            $integratorShare = $transaction->integrator_commission ?? 0;
            $partnerShare = $transaction->partner_commission ?? 0;
            $totalFees = $adminShare + $integratorShare + $partnerShare;
            $netAmount = ($transaction->price_total ?? $transaction->amount ?? 0) - $totalFees;
            $transactionFees = $adminShare + $integratorShare;
            $chargeFees = $partnerShare;
            $baseFees = 0;

            return view('transactions.show_enhanced', compact(
                'transaction', 
                'adminShare', 
                'integratorShare', 
                'partnerShare', 
                'totalFees', 
                'netAmount', 
                'transactionFees', 
                'chargeFees', 
                'baseFees'
            ));

        } catch (\Exception $e) {
            Log::error('Error showing transaction: ' . $e->getMessage());
            return redirect()->route('transactions.index')
                ->with('error', 'Erreur lors de l\'affichage de la transaction: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified transaction.
     */
    public function edit($id)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Vous devez être connecté pour accéder à cette page.');
            }

            $transaction = Transaction::findOrFail($id);

            if (!$this->canViewTransaction($user, $transaction)) {
                return redirect()->route('transactions.index')
                    ->with('error', 'Vous n\'avez pas l\'autorisation de modifier cette transaction.');
            }

            $chargingPoints = ChargingPoint::orderBy('name')->get();
            $users = User::orderBy('name')->get();
            $businessProfiles = BusinessProfile::orderBy('name')->get();

            return view('transactions.edit', compact('transaction', 'chargingPoints', 'users', 'businessProfiles'));
        } catch (\Exception $e) {
            Log::error('Error editing transaction: ' . $e->getMessage());
            return redirect()->route('transactions.index')
                ->with('error', 'Erreur lors du chargement de la transaction: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified transaction in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Vous devez être connecté pour accéder à cette page.');
            }

            $transaction = Transaction::findOrFail($id);

            if (!$this->canViewTransaction($user, $transaction)) {
                return redirect()->route('transactions.index')
                    ->with('error', 'Vous n\'avez pas l\'autorisation de modifier cette transaction.');
            }

            $validatedData = $request->validate([
                'transaction_id' => 'required|string|max:255|unique:transactions,transaction_id,' . $id,
                'transaction_type' => 'required|string|in:client,admin_integrator,integrator_operator',
                'charging_point_id' => 'required|exists:charging_points,id',
                'user_id' => 'nullable|exists:users,id',
                'price_total' => 'required|numeric|min:0',
                'status' => 'required|string|in:pending,completed,cancelled,failed',
                'description' => 'nullable|string|max:1000',
                'notes' => 'nullable|string|max:1000',
            ]);

            $transaction->update($validatedData);

            return redirect()->route('transactions.show', $transaction->id)
                ->with('success', 'Transaction mise à jour avec succès.');
        } catch (\Exception $e) {
            Log::error('Error updating transaction: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la mise à jour de la transaction: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified transaction from storage.
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Vous devez être connecté pour accéder à cette page.');
            }

            $transaction = Transaction::findOrFail($id);

            if (!$this->canViewTransaction($user, $transaction)) {
                return redirect()->route('transactions.index')
                    ->with('error', 'Vous n\'avez pas l\'autorisation de supprimer cette transaction.');
            }

            $transaction->delete();

            return redirect()->route('transactions.index')
                ->with('success', 'Transaction supprimée avec succès.');
        } catch (\Exception $e) {
            Log::error('Error deleting transaction: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression de la transaction: ' . $e->getMessage());
        }
    }

    /**
     * Vérifier si l'utilisateur peut voir/modifier une transaction
     */
    private function canViewTransaction($user, $transaction)
    {
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        if ($user->hasRole('integrator')) {
            return $transaction->chargingPoint && $transaction->chargingPoint->integrator_id == $user->integrator_id;
        }

        if ($user->hasRole('partner')) {
            return $transaction->chargingPoint && $transaction->chargingPoint->partner_id == $user->partner_id;
        }

        if ($user->hasRole('operator')) {
            return $transaction->chargingPoint && $transaction->chargingPoint->user_id == $user->id;
        }

        // Utilisateurs normaux
        return $transaction->reservation && $transaction->reservation->user_id == $user->id;
    }
}

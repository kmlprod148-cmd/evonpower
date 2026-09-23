<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class TransactionController extends Controller
{
    /**
     * Display a listing of all transactions.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', WalletTransaction::class);

        // Get filter parameters
        $filters = [
            'role' => $request->get('role'),
            'user' => $request->get('user'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'type' => $request->get('type'),
            'search' => $request->get('search'),
        ];

        // Build query
        $query = WalletTransaction::with(['wallet.owner'])
            ->join('wallets', 'wallet_transactions.wallet_id', '=', 'wallets.id')
            ->leftJoin('users', function($join) {
                $join->on('wallets.owner_id', '=', 'users.id')
                     ->where('wallets.owner_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('partners', function($join) {
                $join->on('wallets.owner_id', '=', 'partners.id')
                     ->where('wallets.owner_type', '=', 'App\\Models\\Partner');
            })
            ->leftJoin('integrators', function($join) {
                $join->on('wallets.owner_id', '=', 'integrators.id')
                     ->where('wallets.owner_type', '=', 'App\\Models\\Integrator');
            })
            ->select([
                'wallet_transactions.*',
                'wallets.owner_type',
                'wallets.owner_id',
                DB::raw('COALESCE(users.name, partners.name, integrators.name) as owner_name'),
                DB::raw('COALESCE(users.email, partners.email, integrators.email) as owner_email'),
            ]);

        // Apply hierarchical filtering for integrators
        $user = auth()->user();
        if ($user->hasRole('integrator')) {
            $integratorId = $user->integrator_id;
            
            // Get integrator model if integrator_id is not directly on user
            if (!$integratorId) {
                $integrator = \App\Models\Integrator::where('user_id', $user->id)->first();
                $integratorId = $integrator ? $integrator->id : null;
            }
            
            if ($integratorId) {
                // Filter transactions: own wallet, operators' wallets, or partners' wallets
                $query->where(function($q) use ($integratorId) {
                    // Integrator's own wallet
                    $q->where(function($subQ) use ($integratorId) {
                        $subQ->where('wallets.owner_type', 'App\\Models\\Integrator')
                             ->where('integrators.id', $integratorId);
                    })
                    // Operators' wallets
                    ->orWhere(function($subQ) use ($integratorId) {
                        $subQ->where('wallets.owner_type', 'App\\Models\\User')
                             ->where('users.integrator_id', $integratorId)
                             ->whereHas('users', function($userQuery) {
                                 $userQuery->whereHas('roles', function($roleQuery) {
                                     $roleQuery->where('name', 'operator');
                                 });
                             });
                    })
                    // Partners' wallets
                    ->orWhere(function($subQ) use ($integratorId) {
                        $subQ->where('wallets.owner_type', 'App\\Models\\Partner')
                             ->where('partners.integrator_id', $integratorId);
                    });
                });
            } else {
                // No integrator ID found, show nothing
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->hasRole('operator')) {
            // Operators see only their own transactions
            $query->where(function($q) use ($user) {
                $q->where('wallets.owner_type', 'App\\Models\\User')
                  ->where('users.id', $user->id);
            });
        } elseif ($user->hasRole('partner')) {
            // Partners see only their own transactions
            $partnerId = $user->partner_id;
            if ($partnerId) {
                $query->where(function($q) use ($partnerId) {
                    $q->where('wallets.owner_type', 'App\\Models\\Partner')
                      ->where('partners.id', $partnerId);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        // Admins see all (no additional filter)

        // Apply filters
        if ($filters['role']) {
            $query->whereHas('wallet.owner', function($q) use ($filters) {
                $q->whereHas('roles', function($roleQuery) use ($filters) {
                    $roleQuery->where('name', $filters['role']);
                });
            });
        }

        if ($filters['user']) {
            $query->where(function($q) use ($filters) {
                $q->where('users.id', $filters['user'])
                  ->orWhere('partners.id', $filters['user'])
                  ->orWhere('integrators.id', $filters['user']);
            });
        }

        if ($filters['date_from']) {
            $query->whereDate('wallet_transactions.created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('wallet_transactions.created_at', '<=', $filters['date_to']);
        }

        if ($filters['type']) {
            $query->where('wallet_transactions.type', $filters['type']);
        }

        if ($filters['search']) {
            $query->where(function($q) use ($filters) {
                $q->where('wallet_transactions.description', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('users.name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('partners.name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('integrators.name', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Get paginated results
        $transactions = $query->orderBy('wallet_transactions.created_at', 'desc')
            ->paginate(50)
            ->appends($filters);
        
        // Charger les Transaction correspondantes avec leurs TransactionDetail pour éviter les requêtes N+1
        $transactionIds = collect();
        foreach ($transactions as $wt) {
            if ($wt->metadata && isset($wt->metadata['transaction_id'])) {
                $transactionIds->push($wt->metadata['transaction_id']);
            } else {
                // Essayer aussi avec l'ID direct
                $transactionIds->push($wt->id);
            }
        }
        
        // Charger toutes les Transaction avec leurs TransactionDetail en une seule requête
        $relatedTransactions = \App\Models\Transaction::with([
            'transactionDetail',
            'transactionDetail.adminCreator',
            'transactionDetail.integratorCreator',
            'transactionDetail.operator'
        ])->whereIn('id', $transactionIds->unique()->filter())->get()->keyBy('id');
        
        // Attacher les Transaction aux WalletTransaction pour éviter les requêtes dans la vue
        foreach ($transactions as $wt) {
            $transactionId = $wt->metadata['transaction_id'] ?? $wt->id;
            if (isset($relatedTransactions[$transactionId])) {
                $wt->setRelation('relatedTransaction', $relatedTransactions[$transactionId]);
            }
        }

        // Get filter options (filtered by role)
        $roles = Role::all();
        
        if ($user->hasRole('integrator')) {
            $integratorId = $user->integrator_id;
            if (!$integratorId) {
                $integrator = \App\Models\Integrator::where('user_id', $user->id)->first();
                $integratorId = $integrator ? $integrator->id : null;
            }
            
            if ($integratorId) {
                // Only show operators and partners belonging to this integrator
                $users = User::where(function($q) use ($integratorId) {
                    $q->where('integrator_id', $integratorId)
                      ->orWhereHas('partner', function($partnerQuery) use ($integratorId) {
                          $partnerQuery->where('integrator_id', $integratorId);
                      });
                })->with('roles')->get();
            } else {
                $users = collect([]);
            }
        } elseif ($user->hasRole('operator')) {
            $users = collect([$user]);
        } elseif ($user->hasRole('partner')) {
            $users = collect([$user]);
        } else {
            $users = User::with('roles')->get();
        }
        
        $transactionTypes = ['credit', 'debit'];

        // Get statistics
        $stats = $this->getTransactionStats($filters);
        
        // Calculer les statistiques supplémentaires pour l'admin
        if (auth()->user()->hasRole(['admin', 'super_admin'])) {
            // Calculer le solde total de tous les wallets
            $totalWalletBalance = DB::table('wallets')
                ->sum('balance');
            
            // Calculer le nombre de wallets
            $totalWallets = DB::table('wallets')->count();
            
            // Calculer les statistiques par type de propriétaire
            $walletStatsByOwner = DB::table('wallets')
                ->select('owner_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(balance) as total_balance'))
                ->groupBy('owner_type')
                ->get()
                ->keyBy('owner_type');
            
            $stats['total_wallet_balance'] = $totalWalletBalance;
            $stats['total_wallets'] = $totalWallets;
            $stats['wallet_stats_by_owner'] = $walletStatsByOwner;
        }

        return view('admin.transactions.index', compact(
            'transactions',
            'roles',
            'users',
            'transactionTypes',
            'filters',
            'stats'
        ));
    }

    /**
     * Get transaction statistics
     */
    protected function getTransactionStats(array $filters = [])
    {
        $query = WalletTransaction::query();
        
        // Apply hierarchical filtering for integrators (same as index)
        $user = auth()->user();
        if ($user->hasRole('integrator')) {
            $integratorId = $user->integrator_id;
            if (!$integratorId) {
                $integrator = \App\Models\Integrator::where('user_id', $user->id)->first();
                $integratorId = $integrator ? $integrator->id : null;
            }
            
            if ($integratorId) {
                $query->whereHas('wallet', function($q) use ($integratorId) {
                    $q->where(function($subQ) use ($integratorId) {
                        // Integrator's own wallet
                        $subQ->where(function($iq) use ($integratorId) {
                            $iq->where('owner_type', 'App\\Models\\Integrator')
                               ->whereHas('owner', function($ownerQuery) use ($integratorId) {
                                   $ownerQuery->where('id', $integratorId);
                               });
                        })
                        // Operators' wallets
                        ->orWhere(function($oq) use ($integratorId) {
                            $oq->where('owner_type', 'App\\Models\\User')
                               ->whereHas('owner', function($ownerQuery) use ($integratorId) {
                                   $ownerQuery->where('integrator_id', $integratorId)
                                              ->whereHas('roles', function($roleQuery) {
                                                  $roleQuery->where('name', 'operator');
                                              });
                               });
                        })
                        // Partners' wallets
                        ->orWhere(function($pq) use ($integratorId) {
                            $pq->where('owner_type', 'App\\Models\\Partner')
                               ->whereHas('owner', function($ownerQuery) use ($integratorId) {
                                   $ownerQuery->where('integrator_id', $integratorId);
                               });
                        });
                    });
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->hasRole('operator')) {
            $query->whereHas('wallet', function($q) use ($user) {
                $q->where('owner_type', 'App\\Models\\User')
                  ->where('owner_id', $user->id);
            });
        } elseif ($user->hasRole('partner')) {
            $partnerId = $user->partner_id;
            if ($partnerId) {
                $query->whereHas('wallet', function($q) use ($partnerId) {
                    $q->where('owner_type', 'App\\Models\\Partner')
                      ->where('owner_id', $partnerId);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Apply same filters as main query
        if ($filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if ($filters['type']) {
            $query->where('type', $filters['type']);
        }

        $totalTransactions = $query->count();
        $totalCredits = $query->where('type', 'credit')->sum('amount');
        $totalDebits = $query->where('type', 'debit')->sum('amount');
        $netAmount = $totalCredits - $totalDebits;

        return [
            'total_transactions' => $totalTransactions,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'net_amount' => $netAmount,
            'credit_count' => $query->where('type', 'credit')->count(),
            'debit_count' => $query->where('type', 'debit')->count(),
        ];
    }

    /**
     * Show transaction details
     */
    public function show(WalletTransaction $transaction)
    {
        $this->authorize('view', $transaction);

        // Charger toutes les relations nécessaires
        $transaction->load([
            'wallet.owner',
            'wallet.owner.roles'
        ]);

        // Chercher la Transaction correspondante si elle existe (pour avoir TransactionDetail avec tous les détails)
        $transactionModel = $transaction->getTransactionModel();
        
        // Si pas trouvée via la méthode du modèle, essayer manuellement
        if (!$transactionModel) {
            // Méthode 1: Via metadata
            if ($transaction->metadata && isset($transaction->metadata['transaction_id'])) {
                $transactionModel = \App\Models\Transaction::with([
                    'transactionDetail',
                    'transactionDetail.adminCreator',
                    'transactionDetail.integratorCreator',
                    'transactionDetail.operator',
                    'chargingPoint',
                    'chargingPoint.businessProfile',
                    'chargingPoint.group',
                    'user',
                    'reservation'
                ])->find($transaction->metadata['transaction_id']);
            }
            
            // Méthode 2: Via ID direct si WalletTransaction->id correspond à Transaction->id
            if (!$transactionModel) {
                $transactionModel = \App\Models\Transaction::with([
                    'transactionDetail',
                    'transactionDetail.adminCreator',
                    'transactionDetail.integratorCreator',
                    'transactionDetail.operator',
                    'chargingPoint',
                    'chargingPoint.businessProfile',
                    'chargingPoint.group',
                    'user',
                    'reservation'
                ])->find($transaction->id);
            }
        }

        // Calculer les statistiques du wallet pour cette transaction
        $walletStats = null;
        if ($transaction->wallet) {
            $wallet = $transaction->wallet;
            $walletStats = [
                'current_balance' => $wallet->balance ?? 0,
                'total_credits' => $wallet->transactions()->where('type', 'credit')->sum('amount'),
                'total_debits' => $wallet->transactions()->where('type', 'debit')->sum('amount'),
                'total_transactions' => $wallet->transactions()->count(),
                'recent_transactions' => $wallet->transactions()->latest()->limit(10)->get()
            ];
        }

        return view('admin.transactions.show', compact('transaction', 'transactionModel', 'walletStats'));
    }

    /**
     * Export transactions to CSV
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', WalletTransaction::class);

        $filters = $request->only(['role', 'user', 'date_from', 'date_to', 'type']);

        // Build query (same as index)
        $query = WalletTransaction::with(['wallet.owner'])
            ->join('wallets', 'wallet_transactions.wallet_id', '=', 'wallets.id')
            ->leftJoin('users', function($join) {
                $join->on('wallets.owner_id', '=', 'users.id')
                     ->where('wallets.owner_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('partners', function($join) {
                $join->on('wallets.owner_id', '=', 'partners.id')
                     ->where('wallets.owner_type', '=', 'App\\Models\\Partner');
            })
            ->leftJoin('integrators', function($join) {
                $join->on('wallets.owner_id', '=', 'integrators.id')
                     ->where('wallets.owner_type', '=', 'App\\Models\\Integrator');
            })
            ->select([
                'wallet_transactions.*',
                'wallets.owner_type',
                'wallets.owner_id',
                DB::raw('COALESCE(users.name, partners.name, integrators.name) as owner_name'),
                DB::raw('COALESCE(users.email, partners.email, integrators.email) as owner_email'),
            ]);

        // Apply filters
        if ($filters['role']) {
            $query->whereHas('wallet.owner', function($q) use ($filters) {
                $q->whereHas('roles', function($roleQuery) use ($filters) {
                    $roleQuery->where('name', $filters['role']);
                });
            });
        }

        if ($filters['user']) {
            $query->where(function($q) use ($filters) {
                $q->where('users.id', $filters['user'])
                  ->orWhere('partners.id', $filters['user'])
                  ->orWhere('integrators.id', $filters['user']);
            });
        }

        if ($filters['date_from']) {
            $query->whereDate('wallet_transactions.created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('wallet_transactions.created_at', '<=', $filters['date_to']);
        }

        if ($filters['type']) {
            $query->where('wallet_transactions.type', $filters['type']);
        }

        $transactions = $query->orderBy('wallet_transactions.created_at', 'desc')->get();

        $filename = 'transactions_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID',
                'Owner Name',
                'Owner Email',
                'Owner Type',
                'Amount',
                'Type',
                'Description',
                'Current Balance',
                'Date',
            ]);

            // CSV data
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->id,
                    $transaction->owner_name,
                    $transaction->owner_email,
                    class_basename($transaction->wallet->owner_type),
                    $transaction->amount,
                    $transaction->type,
                    $transaction->description,
                    $transaction->current_balance,
                    $transaction->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get users for autocomplete
     */
    public function getUsers(Request $request)
    {
        $search = $request->get('q');
        
        $users = User::where('name', 'like', '%' . $search . '%')
            ->orWhere('email', 'like', '%' . $search . '%')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }
}

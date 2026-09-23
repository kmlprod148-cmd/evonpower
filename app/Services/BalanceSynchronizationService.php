<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BalanceSynchronizationService
{
    /**
     * Synchroniser le balance d'un utilisateur à partir des TransactionDetails approuvées
     * 
     * Cette méthode :
     * 1. Calcule le balance réel à partir des parts dans TransactionDetails
     * 2. Compare avec le balance actuel du wallet
     * 3. Crée les WalletTransactions manquantes si nécessaire
     * 4. Met à jour le wallet balance
     */
    public function synchronizeUserBalance(\Illuminate\Contracts\Auth\Authenticatable $user): array
    {
        $isAdmin = $user->hasRole(['admin', 'super_admin']);
        $isIntegrator = $user->hasRole('integrator');
        $isOperator = $user->hasRole(['operator', 'partner']);
        
        $wallet = $user->getOrCreateWallet();
        
        // Calculer le balance réel à partir des TransactionDetails approuvées
        $calculatedBalance = $this->calculateBalanceFromApprovedTransactions($user);
        
        // Calculer les totaux crédits et débits
        $totals = $this->calculateTotalsFromApprovedTransactions($user);
        
        // Obtenir le balance actuel du wallet
        $currentWalletBalance = (float) $wallet->balance;
        
        // Calculer les WalletTransactions attendues
        $expectedCredits = $totals['total_credits'];
        $expectedDebits = $totals['total_debits'];
        
        // Obtenir les WalletTransactions existantes
        $actualCredits = (float) $wallet->getTotalCredits();
        $actualDebits = (float) $wallet->getTotalDebits();
        
        // Calculer les différences
        $missingCredits = max(0, $expectedCredits - $actualCredits);
        $missingDebits = max(0, $expectedDebits - $actualDebits);
        
        $synchronized = false;
        $createdTransactions = [];
        
        // Créer les WalletTransactions manquantes pour les crédits
        // Cela garantit que "Money in" = total des WalletTransactions credits = total des parts dans TransactionDetails
        if ($missingCredits > 0.01 || abs($calculatedBalance - $currentWalletBalance) > 0.01) {
            try {
                DB::beginTransaction();
                
                // Créer les WalletTransactions pour chaque TransactionDetail approuvée qui n'a pas de WalletTransaction correspondante
                // Cette méthode crée les WalletTransactions manquantes pour chaque TransactionDetail
                $this->createMissingWalletTransactions($user, $wallet);
                
                $wallet->refresh();
                $actualCreditsAfter = (float) $wallet->getTotalCredits();
                
                // Si après création, il manque encore des crédits, créer une transaction de synchronisation
                if (abs($expectedCredits - $actualCreditsAfter) > 0.01 && $expectedCredits > $actualCreditsAfter) {
                    $remainingCredits = $expectedCredits - $actualCreditsAfter;
                    $creditTransaction = $wallet->credit(
                        $remainingCredits,
                        "Synchronisation balance - Crédits manquants depuis TransactionDetails approuvées",
                        [
                            'synchronization' => true,
                            'source' => 'transaction_details',
                            'calculated_balance' => $calculatedBalance,
                            'auto_created' => true
                        ]
                    );
                    $createdTransactions[] = $creditTransaction;
                }
                
                // Recalculer le balance après création des WalletTransactions
                $wallet->refresh();
                $newBalance = $this->calculateBalanceFromApprovedTransactions($user);
                
                // Mettre à jour le wallet balance pour qu'il corresponde exactement au calculé
                if (abs($wallet->balance - $newBalance) > 0.01) {
                    $wallet->update(['balance' => $newBalance]);
                    $wallet->refresh();
                }
                
                DB::commit();
                
                $synchronized = true;
                
                Log::info('Balance synchronisé avec WalletTransactions créées pour utilisateur', [
                    'user_id' => $user->id,
                    'role' => $isAdmin ? 'admin' : ($isIntegrator ? 'integrator' : ($isOperator ? 'operator' : 'other')),
                    'old_balance' => $currentWalletBalance,
                    'new_balance' => $newBalance,
                    'expected_credits' => $expectedCredits,
                    'actual_credits_before' => $actualCredits,
                    'actual_credits_after' => $wallet->getTotalCredits(),
                    'missing_credits' => $missingCredits,
                    'created_transactions' => count($createdTransactions)
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                
                Log::error('Erreur lors de la synchronisation du balance', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw $e;
            }
        } elseif (abs($calculatedBalance - $currentWalletBalance) > 0.01) {
            // Si pas de crédits manquants mais balance désynchronisé, juste mettre à jour le balance
            try {
                DB::beginTransaction();
                
                $wallet->update(['balance' => $calculatedBalance]);
                $wallet->refresh();
                
                DB::commit();
                
                $synchronized = true;
                
                Log::info('Balance synchronisé (pas de WalletTransactions à créer)', [
                    'user_id' => $user->id,
                    'old_balance' => $currentWalletBalance,
                    'new_balance' => $calculatedBalance
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } else {
            Log::debug('Balance déjà synchronisé pour utilisateur', [
                'user_id' => $user->id,
                'balance' => $currentWalletBalance,
                'calculated_balance' => $calculatedBalance,
                'expected_credits' => $expectedCredits,
                'actual_credits' => $actualCredits
            ]);
        }
        
        return [
            'synchronized' => $synchronized,
            'user_id' => $user->id,
            'old_balance' => $currentWalletBalance,
            'new_balance' => $calculatedBalance,
            'calculated_balance' => $calculatedBalance,
            'total_credits' => $expectedCredits,
            'total_debits' => $expectedDebits,
            'missing_credits' => $missingCredits,
            'missing_debits' => $missingDebits,
            'created_transactions' => $createdTransactions
        ];
    }
    
    /**
     * Calculer le balance à partir des TransactionDetails approuvées
     */
    public function calculateBalanceFromApprovedTransactions(\Illuminate\Contracts\Auth\Authenticatable $user): float
    {
        $isAdmin = $user->hasRole(['admin', 'super_admin']);
        $isIntegrator = $user->hasRole('integrator');
        $isOperator = $user->hasRole(['operator', 'partner']);
        
        // Calculer les parts depuis TransactionDetails
        $totalShare = 0;
        
        // CORRECTION : Vérifier d'abord si l'utilisateur est opérateur (priorité pour les balances)
        // car un opérateur peut aussi être intégrateur, mais sa balance doit être calculée comme opérateur
        if ($isOperator && !$isAdmin) {
            // Pour les opérateurs, chercher les TransactionDetails où :
            // 1. operator_id correspond à l'utilisateur
            // 2. OU la transaction est liée à une borne dont l'utilisateur est le propriétaire/créateur
            
            // Obtenir le wallet d'abord
            $wallet = $user->getOrCreateWallet();
            
            // MÉTHODE 1 : Direct par operator_id (plus simple et plus rapide)
            $totalShare1 = TransactionDetail::whereHas('transaction', function($q) {
                    $q->where(function($statusQuery) {
                        $statusQuery->whereIn('status', ['completed', 'confirmed'])
                            ->orWhereHas('reservation', function($resQuery) {
                                $resQuery->whereIn('status', ['confirmed', 'active', 'completed']);
                            });
                    })
                    ->where('status', '!=', 'pending');
                })
                ->where('operator_id', $user->id)
                ->where('operator_share_amount', '>', 0)
                ->sum('operator_share_amount');
            
            // MÉTHODE 2 : Via chargingPoint (fallback si méthode 1 retourne 0)
            $totalShare2 = 0;
            if ($totalShare1 <= 0.01) {
                // Chercher via les transactions liées aux bornes de l'utilisateur/partenaire
                $partnerId = $user->partner_id ?? null;
                $userTransactionIds = Transaction::where(function($statusQuery) {
                        $statusQuery->whereIn('status', ['completed', 'confirmed'])
                            ->orWhereHas('reservation', function($resQuery) {
                                $resQuery->whereIn('status', ['confirmed', 'active', 'completed']);
                            });
                    })
                    ->where('status', '!=', 'pending')
                    ->whereHas('chargingPoint', function($cpQuery) use ($user, $partnerId) {
                        $cpQuery->where(function($cpSubQuery) use ($user, $partnerId) {
                            $cpSubQuery->where('user_id', $user->id)
                                      ->orWhere('created_by_id', $user->id)
                                      ->orWhere('created_by', $user->id);
                            if ($partnerId) {
                                $cpSubQuery->orWhere('partner_id', $partnerId);
                            }
                        })
                        ->orWhereHas('group', function($groupQuery) use ($user, $partnerId) {
                            $groupQuery->where('user_id', $user->id);
                            if ($partnerId) {
                                $groupQuery->orWhere('partner_id', $partnerId);
                            }
                        });
                    })
                    ->pluck('id');
                
                if ($userTransactionIds->isNotEmpty()) {
                    $totalShare2 = TransactionDetail::whereIn('transaction_id', $userTransactionIds->toArray())
                        ->where('operator_share_amount', '>', 0)
                        ->sum('operator_share_amount');
                    
                    // Si on trouve des TransactionDetails, corriger les operator_id
                    if ($totalShare2 > 0.01) {
                        TransactionDetail::whereIn('transaction_id', $userTransactionIds->toArray())
                            ->where('operator_share_amount', '>', 0)
                            ->where('operator_id', '!=', $user->id)
                            ->update(['operator_id' => $user->id]);
                    }
                }
            }
            
            $totalShare = max($totalShare1, $totalShare2);
            $totalDebits = abs($wallet->debitTransactions()->sum('amount'));
            
            // Retourner directement la balance pour les opérateurs
            return round($totalShare - $totalDebits, 2);
        } elseif ($isAdmin) {
            // Pour l'admin :
            // Balance = part admin depuis TransactionDetail (admin_share_amount)
            // IMPORTANT: Pour un admin, inclure TOUTES les transactions avec admin_share_amount > 0
            // car il n'y a généralement qu'un seul admin dans le système
            // Les frais admin sont calculés selon Business Profiles et stockés dans admin_share_amount
            
            // CORRECTION OPTIMISÉE: Calcul dynamique et efficace du solde admin
            // IMPORTANT: Pour l'admin, inclure TOUTES les transactions avec admin_share_amount > 0
            // Peu importe le statut de la transaction ou admin_creator_id
            // car il n'y a généralement qu'un seul admin dans le système
            
            // MÉTHODE 1 : Calcul direct depuis admin_share_amount (le plus rapide et fiable)
            // Utiliser DB::raw pour garantir que la somme fonctionne correctement
            $totalShareFromAmount = (float) DB::table('transaction_details')
                ->where('admin_share_amount', '>', 0)
                ->sum('admin_share_amount');
            
            // Si la somme directe retourne 0 ou null, essayer avec la collection
            if ($totalShareFromAmount <= 0) {
                $totalShareFromAmount = (float) TransactionDetail::where('admin_share_amount', '>', 0)
                    ->get()
                    ->sum('admin_share_amount');
            }
            
            // MÉTHODE 2 : Vérifier calculation_details pour les TransactionDetails avec admin_share_amount = 0
            // mais avec admin_fees_amount > 0 dans calculation_details (cas rares mais possibles)
            $detailsWithCalculationOnly = TransactionDetail::where('admin_share_amount', '<=', 0)
                ->whereNotNull('calculation_details')
                ->get()
                ->filter(function($detail) {
                    $calculationDetails = $detail->calculation_details;
                    if (!$calculationDetails) {
                        return false;
                    }
                    
                    $adminFeesFromDetails = 0;
                    if (is_array($calculationDetails) && isset($calculationDetails['hierarchical_logic']['admin_fees_amount'])) {
                        $adminFeesFromDetails = (float) $calculationDetails['hierarchical_logic']['admin_fees_amount'];
                    } elseif (is_string($calculationDetails)) {
                        $decoded = json_decode($calculationDetails, true);
                        if (is_array($decoded) && isset($decoded['hierarchical_logic']['admin_fees_amount'])) {
                            $adminFeesFromDetails = (float) $decoded['hierarchical_logic']['admin_fees_amount'];
                        }
                    }
                    
                    return $adminFeesFromDetails > 0;
                })
                ->sum(function($detail) {
                    $calculationDetails = $detail->calculation_details;
                    $adminFeesFromDetails = 0;
                    
                    if (is_array($calculationDetails) && isset($calculationDetails['hierarchical_logic']['admin_fees_amount'])) {
                        $adminFeesFromDetails = (float) $calculationDetails['hierarchical_logic']['admin_fees_amount'];
                    } elseif (is_string($calculationDetails)) {
                        $decoded = json_decode($calculationDetails, true);
                        if (is_array($decoded) && isset($decoded['hierarchical_logic']['admin_fees_amount'])) {
                            $adminFeesFromDetails = (float) $decoded['hierarchical_logic']['admin_fees_amount'];
                        }
                    }
                    
                    return $adminFeesFromDetails;
                });
            
            // Total = somme directe + somme depuis calculation_details
            $totalShare = (float) $totalShareFromAmount + (float) $detailsWithCalculationOnly;
            
            // CORRECTION : Pour l'admin, pas de Total Debits (Money out) dans le contexte des transactions de réservations
            // L'admin reçoit seulement des parts admin (Money in), pas de débits liés aux transactions de réservations
            // Les débits wallet (retraits, etc.) ne sont pas comptabilisés dans Money out pour les transactions de réservations
            $totalDebits = 0; // Admin n'a pas de Money out
            
            // Retourner directement la balance pour les admins (pas de déduction de débits)
            return round($totalShare, 2);
                
        } elseif ($isOperator) {
            // CORRECTION : Vérifier les opérateurs AVANT les intégrateurs
            // car un utilisateur peut être à la fois opérateur et intégrateur
            // mais sa balance doit être calculée comme opérateur
            // CORRECTION: Pour les opérateurs, inclure TOUTES les transactions avec operator_share_amount > 0
            // peu importe le statut de la transaction, car si un TransactionDetail existe avec des parts opérateur,
            // la transaction doit être comptabilisée dans la balance opérateur
            // Pour les opérateurs, chercher les TransactionDetails où :
            // 1. operator_id correspond à l'utilisateur
            // 2. OU la transaction est liée à une borne dont l'utilisateur est le propriétaire/créateur
            
            // Obtenir le wallet d'abord
            $wallet = $user->getOrCreateWallet();
            
            // MÉTHODE 1 : Direct par operator_id (plus simple et plus rapide)
            $totalShare1 = TransactionDetail::whereHas('transaction')
                ->where('operator_id', $user->id)
                ->where('operator_share_amount', '>', 0)
                ->sum('operator_share_amount');
            
            // MÉTHODE 2 : Via chargingPoint (fallback si méthode 1 retourne 0)
            $totalShare2 = 0;
            if ($totalShare1 <= 0.01) {
                // Chercher via les transactions liées aux bornes de l'utilisateur
                $userTransactionIds = Transaction::whereHas('chargingPoint', function($cpQuery) use ($user) {
                        $cpQuery->where(function($cpSubQuery) use ($user) {
                            $cpSubQuery->where('user_id', $user->id)
                                      ->orWhere('created_by_id', $user->id)
                                      ->orWhere('created_by', $user->id);
                        })
                        ->orWhereHas('group', function($groupQuery) use ($user) {
                            $groupQuery->where('user_id', $user->id);
                        });
                    })
                    ->pluck('id');
                
                if ($userTransactionIds->isNotEmpty()) {
                    $totalShare2 = TransactionDetail::whereIn('transaction_id', $userTransactionIds->toArray())
                        ->where('operator_share_amount', '>', 0)
                        ->sum('operator_share_amount');
                    
                    // Si on trouve des TransactionDetails, corriger les operator_id
                    if ($totalShare2 > 0.01) {
                        TransactionDetail::whereIn('transaction_id', $userTransactionIds->toArray())
                            ->where('operator_share_amount', '>', 0)
                            ->where('operator_id', '!=', $user->id)
                            ->update(['operator_id' => $user->id]);
                    }
                }
            }
            
            $totalShare = max($totalShare1, $totalShare2);
            $totalDebits = abs($wallet->debitTransactions()->sum('amount'));
            
            // Retourner directement la balance pour les opérateurs
            return round($totalShare - $totalDebits, 2);

        } elseif ($isIntegrator) {
            // Pour les intégrateurs :
            // Balance = (part intégrateur BRUTE - part admin déduite)
            // = (integrator_fees_amount - admin_fees_amount)
            
            // Obtenir l'Integrator model associé à l'utilisateur
            $integratorModel = \App\Models\Integrator::where('user_id', $user->id)->first();
            $integratorModelId = $integratorModel ? $integratorModel->id : null;
            
            // CORRECTION: Pour l'intégrateur, inclure TOUTES les transactions avec integrator_share_amount > 0
            // peu importe le statut de la transaction, car si un TransactionDetail existe avec des parts intégrateur,
            // la transaction doit être comptabilisée dans la balance intégrateur
            // IMPORTANT : Chercher avec user->id ET integrator->id car integrator_creator_id peut être l'un ou l'autre
            $integratorDetails = TransactionDetail::whereHas('transaction')
                ->where(function($q) use ($user, $integratorModelId) {
                    $q->where('integrator_creator_id', $user->id);
                    if ($integratorModelId) {
                        $q->orWhere('integrator_creator_id', $integratorModelId);
                    }
                })
                ->get()
                ->filter(function($detail) {
                    // Inclure si integrator_share_amount > 0
                    return ($detail->integrator_share_amount ?? 0) > 0;
                });
            
            // Si aucun TransactionDetail trouvé avec integrator_creator_id, essayer de trouver via les transactions
            // liées aux bornes de l'intégrateur (pour les anciennes transactions)
            if ($integratorDetails->isEmpty()) {
                // Chercher via les transactions liées aux bornes de l'intégrateur
                // Build full hierarchical scope (operators + partners of this integrator)
                $allUserIds = \App\Models\User::where('integrator_id', $integratorModelId ?? 0)
                    ->orWhere('id', $user->id)
                    ->pluck('id')
                    ->toArray();
                $partnerIds = \App\Models\Partner::where('integrator_id', $integratorModelId ?? 0)
                    ->pluck('id')
                    ->toArray();

                $alternativeDetails = TransactionDetail::whereHas('transaction', function($q) use ($integratorModelId, $allUserIds, $partnerIds) {
                        $q->where(function($tQuery) use ($integratorModelId, $allUserIds, $partnerIds) {
                            // Transactions sur les bornes de l'intégrateur
                            $tQuery->whereHas('chargingPoint', function($cpQuery) use ($integratorModelId, $allUserIds, $partnerIds) {
                                $cpQuery->where(function($cpSubQuery) use ($integratorModelId, $allUserIds, $partnerIds) {
                                    if ($integratorModelId) {
                                        $cpSubQuery->where('integrator_id', $integratorModelId);
                                    }
                                    $cpSubQuery->orWhereIn('user_id', $allUserIds)
                                               ->orWhereIn('partner_id', $partnerIds);
                                });
                            })
                            // OU transactions d'opérateurs rattachés à l'intégrateur
                            ->orWhereHas('reservation.user', function($userQuery) use ($integratorModelId) {
                                if ($integratorModelId) {
                                    $userQuery->where('integrator_id', $integratorModelId);
                                }
                            });
                        });
                    })
                    ->where(function($q) {
                        // Inclure les TransactionDetails sans integrator_creator_id ou avec integrator_creator_id = 0
                        $q->whereNull('integrator_creator_id')
                          ->orWhere('integrator_creator_id', 0);
                    })
                    ->get()
                    ->filter(function($detail) {
                        // Inclure si integrator_share_amount > 0
                        return ($detail->integrator_share_amount ?? 0) > 0;
                    });

                // CORRECTION AUTOMATIQUE : Mettre à jour integrator_creator_id pour les TransactionDetails trouvés
                if ($alternativeDetails->isNotEmpty()) {
                    $updatedCount = TransactionDetail::whereIn('id', $alternativeDetails->pluck('id'))
                        ->update(['integrator_creator_id' => $integratorModelId ?? $user->id]);

                    Log::info('DEBUG: Correction automatique de integrator_creator_id (calculateBalanceFromApprovedTransactions)', [
                        'user_id' => $user->id,
                        'integrator_model_id' => $integratorModelId,
                        'transaction_details_updated' => $updatedCount
                    ]);
                    
                    // Recharger les TransactionDetails avec le bon integrator_creator_id
                    $integratorDetails = TransactionDetail::whereIn('id', $alternativeDetails->pluck('id'))->get()
                        ->filter(function($detail) {
                            return ($detail->integrator_share_amount ?? 0) > 0;
                        });
                } else {
                    $integratorDetails = $alternativeDetails;
                }
            }
            
            // CORRECTION : Pour les intégrateurs :
            // Money in = integrator_share_amount (part intégrateur nette)
            // Money out = admin_share_amount (part admin déduite)
            // Balance = integrator_share_amount (part nette)
            
            $totalCredits = 0; // Money in = somme des integrator_share_amount
            $totalDebits = 0; // Money out = somme des admin_share_amount
            
            foreach ($integratorDetails as $detail) {
                // PRIORITÉ 1 : Utiliser integrator_share_amount comme source de vérité pour Money in
                // car c'est la valeur réelle stockée dans TransactionDetail (part intégrateur nette)
                $integratorShareAmount = (float) ($detail->integrator_share_amount ?? 0);
                
                // PRIORITÉ 1 : Utiliser admin_share_amount comme source de vérité pour Money out
                // car c'est la valeur réelle stockée dans TransactionDetail et débitée de l'intégrateur
                $adminFees = (float) ($detail->admin_share_amount ?? 0);
                
                // PRIORITÉ 2 : Vérifier si admin_fees_amount dans calculation_details est différent
                // et utiliser la valeur la plus élevée (car admin_fees_amount devrait être >= admin_share_amount)
                $calculationDetails = $detail->calculation_details;
                if ($calculationDetails) {
                    $adminFeesFromDetails = 0;
                    if (is_array($calculationDetails) && isset($calculationDetails['hierarchical_logic']['admin_fees_amount'])) {
                        $adminFeesFromDetails = (float) $calculationDetails['hierarchical_logic']['admin_fees_amount'];
                    } elseif (is_string($calculationDetails)) {
                        $decoded = json_decode($calculationDetails, true);
                        if (is_array($decoded) && isset($decoded['hierarchical_logic']['admin_fees_amount'])) {
                            $adminFeesFromDetails = (float) $decoded['hierarchical_logic']['admin_fees_amount'];
                        }
                    }
                    
                    // Utiliser la valeur la plus élevée (normalement elles devraient être égales)
                    if ($adminFeesFromDetails > $adminFees) {
                        $adminFees = $adminFeesFromDetails;
                    }
                }
                
                // Money in = part intégrateur nette (integrator_share_amount)
                $totalCredits += $integratorShareAmount;
                
                // Money out = part admin déduite (admin_share_amount)
                $totalDebits += $adminFees;
            }
            
            // Balance = Money in - Money out = integrator_share_amount (qui est déjà net)
            // Mathématiquement : integrator_share_amount - admin_share_amount = integrator_share_amount
            // car integrator_share_amount = integrator_fees_amount - admin_fees_amount (déjà calculé comme net)
            $calculatedBalance = round($totalCredits, 2);
            
            // Vérification : la balance devrait être égale à la somme des integrator_share_amount
            $expectedBalance = $integratorDetails->sum(function($detail) {
                return (float) ($detail->integrator_share_amount ?? 0);
            });
            
            if (abs($calculatedBalance - $expectedBalance) > 0.01) {
                Log::warning('Incohérence dans le calcul de la balance intégrateur (calculateBalanceFromApprovedTransactions)', [
                    'user_id' => $user->id,
                    'calculated_balance' => $calculatedBalance,
                    'expected_balance_from_share_amount' => $expectedBalance,
                    'difference' => abs($calculatedBalance - $expectedBalance)
                ]);
                
                // Utiliser la valeur attendue si elle est différente
                $calculatedBalance = round($expectedBalance, 2);
            }
            
            // Pour les intégrateurs, la balance = integrator_share_amount (part nette)
            // Money in = integrator_share_amount
            // Money out = admin_share_amount
            // Balance = integrator_share_amount (déjà net)
            
            // Retourner directement la balance pour les intégrateurs
            return $calculatedBalance;
        }
        
        // Calculer les débits depuis les wallet transactions (pour les autres rôles)
        if (!isset($wallet)) {
            $wallet = $user->getOrCreateWallet();
        }
        if (!isset($totalDebits)) {
            $totalDebits = abs($wallet->debitTransactions()->sum('amount'));
        }
        
        // Pour les intégrateurs, utiliser $calculatedBalance si défini (plus précis)
        if ($isIntegrator && isset($calculatedBalance)) {
            return $calculatedBalance;
        }
        
        // Pour les autres rôles, calculer depuis totalShare - totalDebits
        return round($totalShare - $totalDebits, 2);
    }
    
    /**
     * Calculer les totaux crédits et débits depuis les TransactionDetails approuvées
     */
    public function calculateTotalsFromApprovedTransactions(\Illuminate\Contracts\Auth\Authenticatable $user): array
    {
        $isAdmin = $user->hasRole(['admin', 'super_admin']);
        $isIntegrator = $user->hasRole('integrator');
        $isOperator = $user->hasRole(['operator', 'partner']);
        
        // Vérifier d'abord combien de transactions approuvées existent
        $approvedTransactionsCount = Transaction::where(function($statusQuery) {
                $statusQuery->whereIn('status', ['completed', 'confirmed'])
                            ->orWhereHas('reservation', function($resQuery) {
                                $resQuery->whereIn('status', ['confirmed', 'active', 'completed']);
                            });
            })
            ->where('status', '!=', 'pending')
            ->count();

        $transactionsWithDetails = Transaction::where(function($statusQuery) {
                $statusQuery->whereIn('status', ['completed', 'confirmed'])
                            ->orWhereHas('reservation', function($resQuery) {
                                $resQuery->whereIn('status', ['confirmed', 'active', 'completed']);
                            });
            })
            ->where('status', '!=', 'pending')
            ->whereHas('transactionDetail')
            ->count();
        
        $totalCredits = 0;
        
        if ($isAdmin) {
            // Pour l'admin :
            // Money in (Total Credits) = part admin depuis TransactionDetail (admin_share_amount)
            // IMPORTANT: Pour un admin, inclure TOUTES les transactions avec admin_share_amount > 0
            // car il n'y a généralement qu'un seul admin dans le système
            // Les frais admin sont calculés selon Business Profiles et stockés dans admin_share_amount
            
            // CORRECTION OPTIMISÉE: Calcul dynamique et efficace du solde admin
            // IMPORTANT: Pour l'admin, inclure TOUTES les transactions avec admin_share_amount > 0
            // Peu importe le statut de la transaction ou admin_creator_id
            // car il n'y a généralement qu'un seul admin dans le système
            
            // MÉTHODE 1 : Calcul direct depuis admin_share_amount (le plus rapide et fiable)
            // Utiliser DB::raw pour garantir que la somme fonctionne correctement
            $totalCreditsFromAmount = (float) DB::table('transaction_details')
                ->where('admin_share_amount', '>', 0)
                ->sum('admin_share_amount');
            
            // Si la somme directe retourne 0 ou null, essayer avec la collection
            if ($totalCreditsFromAmount <= 0) {
                $totalCreditsFromAmount = (float) TransactionDetail::where('admin_share_amount', '>', 0)
                    ->get()
                    ->sum('admin_share_amount');
            }
            
            // MÉTHODE 2 : Vérifier calculation_details pour les TransactionDetails avec admin_share_amount = 0
            // mais avec admin_fees_amount > 0 dans calculation_details (cas rares mais possibles)
            $totalCreditsFromDetails = TransactionDetail::where('admin_share_amount', '<=', 0)
                ->whereNotNull('calculation_details')
                ->get()
                ->filter(function($detail) {
                    $calculationDetails = $detail->calculation_details;
                    if (!$calculationDetails) {
                        return false;
                    }
                    
                    $adminFeesFromDetails = 0;
                    if (is_array($calculationDetails) && isset($calculationDetails['hierarchical_logic']['admin_fees_amount'])) {
                        $adminFeesFromDetails = (float) $calculationDetails['hierarchical_logic']['admin_fees_amount'];
                    } elseif (is_string($calculationDetails)) {
                        $decoded = json_decode($calculationDetails, true);
                        if (is_array($decoded) && isset($decoded['hierarchical_logic']['admin_fees_amount'])) {
                            $adminFeesFromDetails = (float) $decoded['hierarchical_logic']['admin_fees_amount'];
                        }
                    }
                    
                    return $adminFeesFromDetails > 0;
                })
                ->sum(function($detail) {
                    $calculationDetails = $detail->calculation_details;
                    $adminFeesFromDetails = 0;
                    
                    if (is_array($calculationDetails) && isset($calculationDetails['hierarchical_logic']['admin_fees_amount'])) {
                        $adminFeesFromDetails = (float) $calculationDetails['hierarchical_logic']['admin_fees_amount'];
                    } elseif (is_string($calculationDetails)) {
                        $decoded = json_decode($calculationDetails, true);
                        if (is_array($decoded) && isset($decoded['hierarchical_logic']['admin_fees_amount'])) {
                            $adminFeesFromDetails = (float) $decoded['hierarchical_logic']['admin_fees_amount'];
                        }
                    }
                    
                    return $adminFeesFromDetails;
                });
            
            // Total = somme directe + somme depuis calculation_details
            $totalCredits = (float) $totalCreditsFromAmount + (float) $totalCreditsFromDetails;
            
            // Compter le nombre de TransactionDetails pour le log
            $detailsCount = TransactionDetail::where('admin_share_amount', '>', 0)->count();
            
            Log::info('DEBUG: calculateTotalsFromApprovedTransactions pour Admin (calcul dynamique optimisé)', [
                'user_id' => $user->id,
                'transaction_details_count' => $detailsCount,
                'total_from_admin_share_amount' => $totalCreditsFromAmount,
                'total_from_calculation_details' => $totalCreditsFromDetails,
                'total_credits' => $totalCredits
            ]);
                
        } elseif ($isIntegrator) {
            // Pour les intégrateurs :
            // Money in (Total Credits) = part intégrateur (integrator_share_amount)
            // Money out (Total Debits) = parts admin déduites de l'intégrateur (admin_share_amount)
            
            // Obtenir l'Integrator model associé à l'utilisateur
            $integratorModel = \App\Models\Integrator::where('user_id', $user->id)->first();
            $integratorModelId = $integratorModel ? $integratorModel->id : null;
            
            // Récupérer tous les TransactionDetails pour l'intégrateur
            // STRATÉGIE : Chercher d'abord avec integrator_creator_id, puis via les transactions liées aux bornes
            // et corriger automatiquement les integrator_creator_id manquants
            
            // CORRECTION: Pour l'intégrateur, inclure TOUTES les transactions avec integrator_share_amount > 0
            // peu importe le statut de la transaction, car si un TransactionDetail existe avec des parts intégrateur,
            // la transaction doit être comptabilisée dans la balance intégrateur
            // ÉTAPE 1 : Chercher avec integrator_creator_id
            $integratorDetails = TransactionDetail::whereHas('transaction')
                ->where(function($q) use ($user, $integratorModelId) {
                    $q->where('integrator_creator_id', $user->id);
                    if ($integratorModelId) {
                        $q->orWhere('integrator_creator_id', $integratorModelId);
                    }
                })
                ->get()
                ->filter(function($detail) {
                    // Inclure si integrator_share_amount > 0
                    return ($detail->integrator_share_amount ?? 0) > 0;
                });
            
            // ÉTAPE 2 : Si aucun trouvé, chercher via les transactions liées aux bornes de l'intégrateur
            if ($integratorDetails->isEmpty()) {
                Log::warning('DEBUG: Aucun TransactionDetail trouvé avec integrator_creator_id, recherche alternative', [
                    'user_id' => $user->id,
                    'integrator_model_id' => $integratorModelId
                ]);
                
                // Chercher via les transactions liées aux bornes de l'intégrateur
                // Build full hierarchical scope (operators + partners of this integrator)
                $allUserIds2 = \App\Models\User::where('integrator_id', $integratorModelId ?? 0)
                    ->orWhere('id', $user->id)
                    ->pluck('id')
                    ->toArray();
                $partnerIds2 = \App\Models\Partner::where('integrator_id', $integratorModelId ?? 0)
                    ->pluck('id')
                    ->toArray();

                $alternativeDetails = TransactionDetail::whereHas('transaction', function($q) use ($integratorModelId, $allUserIds2, $partnerIds2) {
                        $q->where(function($tQuery) use ($integratorModelId, $allUserIds2, $partnerIds2) {
                            // Transactions sur les bornes de l'intégrateur
                            $tQuery->whereHas('chargingPoint', function($cpQuery) use ($integratorModelId, $allUserIds2, $partnerIds2) {
                                $cpQuery->where(function($cpSubQuery) use ($integratorModelId, $allUserIds2, $partnerIds2) {
                                    if ($integratorModelId) {
                                        $cpSubQuery->where('integrator_id', $integratorModelId);
                                    }
                                    $cpSubQuery->orWhereIn('user_id', $allUserIds2)
                                               ->orWhereIn('partner_id', $partnerIds2);
                                });
                            })
                            // OU transactions d'opérateurs rattachés à l'intégrateur
                            ->orWhereHas('reservation.user', function($userQuery) use ($integratorModelId) {
                                if ($integratorModelId) {
                                    $userQuery->where('integrator_id', $integratorModelId);
                                }
                            });
                        });
                    })
                    ->where(function($q) {
                        // Inclure les TransactionDetails sans integrator_creator_id ou avec integrator_creator_id = 0
                        $q->whereNull('integrator_creator_id')
                          ->orWhere('integrator_creator_id', 0);
                    })
                    ->get()
                    ->filter(function($detail) {
                        // Inclure si integrator_share_amount > 0
                        return ($detail->integrator_share_amount ?? 0) > 0;
                    });

                // CORRECTION AUTOMATIQUE : Mettre à jour integrator_creator_id pour les TransactionDetails trouvés
                if ($alternativeDetails->isNotEmpty()) {
                    $updatedCount = TransactionDetail::whereIn('id', $alternativeDetails->pluck('id'))
                        ->update(['integrator_creator_id' => $integratorModelId ?? $user->id]);

                    Log::info('DEBUG: Correction automatique de integrator_creator_id', [
                        'user_id' => $user->id,
                        'integrator_model_id' => $integratorModelId,
                        'transaction_details_updated' => $updatedCount
                    ]);
                    
                    // Recharger les TransactionDetails avec le bon integrator_creator_id
                    $integratorDetails = TransactionDetail::whereIn('id', $alternativeDetails->pluck('id'))->get()
                        ->filter(function($detail) {
                            // Inclure si integrator_share_amount > 0
                            return ($detail->integrator_share_amount ?? 0) > 0;
                        });
                } else {
                    $integratorDetails = $alternativeDetails;
                }
                
                Log::info('DEBUG: TransactionDetails trouvés via recherche alternative', [
                    'user_id' => $user->id,
                    'transaction_details_count' => $integratorDetails->count()
                ]);
            }
            
            // Vérifier les montants avant de calculer
            $totalIntegratorShare = $integratorDetails->sum(function($d) {
                return (float) ($d->integrator_share_amount ?? 0);
            });
            $totalAdminShare = $integratorDetails->sum(function($d) {
                return (float) ($d->admin_share_amount ?? 0);
            });
            
            Log::info('DEBUG: TransactionDetails trouvés pour Intégrateur', [
                'user_id' => $user->id,
                'integrator_model_id' => $integratorModelId,
                'transaction_details_count' => $integratorDetails->count(),
                'transaction_detail_ids' => $integratorDetails->pluck('id')->toArray(),
                'total_integrator_share_amount' => $totalIntegratorShare,
                'total_admin_share_amount' => $totalAdminShare,
                'sample_integrator_creator_ids' => $integratorDetails->take(5)->pluck('integrator_creator_id')->toArray(),
                'sample_integrator_share_amounts' => $integratorDetails->take(5)->map(function($d) {
                    return [
                        'id' => $d->id,
                        'transaction_id' => $d->transaction_id,
                        'integrator_share_amount' => $d->integrator_share_amount,
                        'admin_share_amount' => $d->admin_share_amount,
                        'integrator_creator_id' => $d->integrator_creator_id,
                        'has_calculation_details' => !empty($d->calculation_details)
                    ];
                })->toArray()
            ]);
            
            // Avertissement si tous les montants sont à 0
            if ($totalIntegratorShare == 0 && $totalAdminShare == 0 && $integratorDetails->isNotEmpty()) {
                Log::warning('ATTENTION: TransactionDetails trouvés mais tous les montants sont à 0', [
                    'user_id' => $user->id,
                    'transaction_details_count' => $integratorDetails->count(),
                    'sample_transaction_ids' => $integratorDetails->take(5)->pluck('transaction_id')->toArray()
                ]);
            }
            
            // Calculer Money in = somme de integrator_share_amount (part intégrateur)
            // Calculer Money out = somme de admin_share_amount (parts admin déduites)
            $totalCredits = 0;
            $totalDebits = 0;
            
            foreach ($integratorDetails as $detail) {
                // PRIORITÉ 1 : Utiliser integrator_share_amount comme source de vérité
                // car c'est la valeur réelle stockée dans TransactionDetail
                $integratorShareAmount = (float) ($detail->integrator_share_amount ?? 0);
                
                // PRIORITÉ 1 : Utiliser admin_share_amount comme source de vérité
                // car c'est la valeur réelle stockée dans TransactionDetail et débitée de l'intégrateur
                $adminFees = (float) ($detail->admin_share_amount ?? 0);
                
                // PRIORITÉ 2 : Vérifier si admin_fees_amount dans calculation_details est différent
                // et utiliser la valeur la plus élevée (car admin_fees_amount devrait être >= admin_share_amount)
                $calculationDetails = $detail->calculation_details;
                if ($calculationDetails) {
                    $adminFeesFromDetails = 0;
                    if (is_array($calculationDetails) && isset($calculationDetails['hierarchical_logic']['admin_fees_amount'])) {
                        $adminFeesFromDetails = (float) $calculationDetails['hierarchical_logic']['admin_fees_amount'];
                    } elseif (is_string($calculationDetails)) {
                        $decoded = json_decode($calculationDetails, true);
                        if (is_array($decoded) && isset($decoded['hierarchical_logic']['admin_fees_amount'])) {
                            $adminFeesFromDetails = (float) $decoded['hierarchical_logic']['admin_fees_amount'];
                        }
                    }
                    
                    // Utiliser la valeur la plus élevée (normalement elles devraient être égales)
                    if ($adminFeesFromDetails > $adminFees) {
                        $adminFees = $adminFeesFromDetails;
                    }
                }
                
                $totalCredits += $integratorShareAmount;
                $totalDebits += $adminFees;
            }
            
            // Pour l'intégrateur, totalDebits = somme de admin_fees_amount (part admin déduite)
            // C'est la somme calculée depuis calculation_details, pas les wallet debits réels
            // car les wallet debits peuvent être incomplets ou incorrects
            // totalDebits contient déjà la somme de tous les admin_fees_amount
            
            Log::info('DEBUG: calculateTotalsFromApprovedTransactions pour Intégrateur', [
                'user_id' => $user->id,
                'transaction_details_count' => $integratorDetails->count(),
                'total_credits' => $totalCredits, // Part intégrateur (integrator_share_amount) - Money in
                'total_debits' => $totalDebits, // Parts admin déduites (admin_share_amount) - Money out
                'net_amount' => $totalCredits - $totalDebits, // Balance nette = Money in - Money out
            ]);
            
            // Vérification de cohérence : totalCredits devrait être égal à la somme des integrator_share_amount
            // car integrator_share_amount est déjà la part nette (après déduction de admin_share_amount)
            // Money in = integrator_share_amount (part nette)
            // Money out = admin_share_amount (part déduite)
            // Balance = integrator_share_amount (part nette)
            $expectedNetAmount = $integratorDetails->sum(function($detail) {
                return (float) ($detail->integrator_share_amount ?? 0);
            });
            
            // Pour les intégrateurs, totalCredits = integrator_share_amount (part nette)
            // Donc totalCredits devrait être égal à expectedNetAmount
            if (abs($totalCredits - $expectedNetAmount) > 0.01) {
                Log::warning('Incohérence dans le calcul Money in pour intégrateur', [
                    'user_id' => $user->id,
                    'calculated_total_credits' => $totalCredits,
                    'expected_from_share_amount' => $expectedNetAmount,
                    'difference' => abs($totalCredits - $expectedNetAmount)
                ]);
                
                // Utiliser la valeur attendue si elle est différente
                $totalCredits = $expectedNetAmount;
            }
            
            // Vérification : Balance = Money in (car Money in est déjà net)
            // Balance = totalCredits = integrator_share_amount
            // Mathématiquement : integrator_share_amount - admin_share_amount = integrator_share_amount
            // car integrator_share_amount est déjà calculé comme net (integrator_fees_amount - admin_fees_amount)
                
        } elseif ($isOperator) {
            // CORRECTION: Pour les opérateurs, inclure TOUTES les transactions avec operator_share_amount > 0
            // peu importe le statut de la transaction, car si un TransactionDetail existe avec des parts opérateur,
            // la transaction doit être comptabilisée dans la balance opérateur
            // Pour les opérateurs, chercher les TransactionDetails où :
            // 1. operator_id correspond à l'utilisateur
            // 2. OU la transaction est liée à une borne dont l'utilisateur est le propriétaire/créateur
            
            // MÉTHODE 1 : Direct par operator_id (plus simple et plus rapide)
            $totalCredits1 = TransactionDetail::whereHas('transaction')
                ->where('operator_id', $user->id)
                ->where('operator_share_amount', '>', 0)
                ->sum('operator_share_amount');
            
            // MÉTHODE 2 : Via chargingPoint (fallback si méthode 1 retourne 0)
            $totalCredits2 = 0;
            if ($totalCredits1 <= 0.01) {
                // Chercher via les transactions liées aux bornes de l'utilisateur/partenaire
                $partnerId = $user->partner_id ?? null;
                $userTransactionIds = Transaction::whereHas('chargingPoint', function($cpQuery) use ($user, $partnerId) {
                        $cpQuery->where(function($cpSubQuery) use ($user, $partnerId) {
                            $cpSubQuery->where('user_id', $user->id)
                                      ->orWhere('created_by_id', $user->id)
                                      ->orWhere('created_by', $user->id);
                            if ($partnerId) {
                                $cpSubQuery->orWhere('partner_id', $partnerId);
                            }
                        })
                        ->orWhereHas('group', function($groupQuery) use ($user, $partnerId) {
                            $groupQuery->where('user_id', $user->id);
                            if ($partnerId) {
                                $groupQuery->orWhere('partner_id', $partnerId);
                            }
                        });
                    })
                    ->pluck('id');
                
                if ($userTransactionIds->isNotEmpty()) {
                    $totalCredits2 = TransactionDetail::whereIn('transaction_id', $userTransactionIds->toArray())
                        ->where('operator_share_amount', '>', 0)
                        ->sum('operator_share_amount');
                    
                    // Si on trouve des TransactionDetails, corriger les operator_id
                    if ($totalCredits2 > 0.01) {
                        TransactionDetail::whereIn('transaction_id', $userTransactionIds->toArray())
                            ->where('operator_share_amount', '>', 0)
                            ->where('operator_id', '!=', $user->id)
                            ->update(['operator_id' => $user->id]);
                    }
                }
            }
            
            $totalCredits = max($totalCredits1, $totalCredits2);
        }
        
        // CORRECTION : Pour les admins, pas de Total Debits (Money out)
        // L'admin reçoit seulement des parts admin (Money in), pas de débits liés aux transactions de réservations
        if ($isAdmin) {
            $totalDebits = 0; // Admin n'a pas de Money out
        } elseif ($isIntegrator) {
            // Pour les intégrateurs, totalDebits a déjà été calculé (part admin déduite)
            // Ne rien faire, totalDebits est déjà défini
        } else {
            // Pour les autres rôles (opérateurs), calculer depuis wallet transactions
            if (!isset($totalDebits)) {
                $wallet = $user->getOrCreateWallet();
                $totalDebits = abs($wallet->debitTransactions()->sum('amount'));
            }
        }
        
        // S'assurer que totalCredits et totalDebits sont bien définis (au moins 0)
        $totalCredits = $totalCredits ?? 0;
        $totalDebits = $totalDebits ?? 0;
        
        // CORRECTION : Pour les intégrateurs, Money in = integrator_share_amount (part nette)
        // Money out = admin_share_amount (part déduite)
        // Balance = integrator_share_amount (part nette) = totalCredits
        // Donc net_amount = totalCredits pour les intégrateurs
        // Pour les admins, net_amount = totalCredits (pas de débits)
        if ($isIntegrator || $isAdmin) {
            $netAmount = $totalCredits; // Balance = Money in (déjà net pour intégrateurs, pas de débits pour admin)
        } else {
            $netAmount = $totalCredits - $totalDebits;
        }
        
        return [
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'net_amount' => $netAmount,
        ];
    }
    
    /**
     * Créer les WalletTransactions manquantes pour les TransactionDetails approuvées
     */
    protected function createMissingWalletTransactions(\Illuminate\Contracts\Auth\Authenticatable $user, Wallet $wallet): void
    {
        $isAdmin = $user->hasRole(['admin', 'super_admin']);
        $isIntegrator = $user->hasRole('integrator');
        $isOperator = $user->hasRole(['operator', 'partner']);
        
        // Récupérer les TransactionDetails approuvées pour cet utilisateur
        // CORRECTION: Utiliser la même logique que calculateTotalsFromApprovedTransactions
        // Pour l'admin, inclure TOUTES les transactions avec admin_share_amount > 0, peu importe le statut
        $query = TransactionDetail::whereHas('transaction')
             ->where(function($detailQuery) use ($user, $isAdmin, $isIntegrator, $isOperator) {
                 if ($isAdmin) {
                     // Pour les admins, inclure TOUS les TransactionDetails
                     // On récupère tous les TransactionDetail et on filtre en PHP pour vérifier admin_share_amount
                     // Peu importe le admin_creator_id (car il n'y a généralement qu'un seul admin)
                     $detailQuery->where(function($adminQuery) {
                         // Inclure tous les TransactionDetails, on filtrera en PHP
                         $adminQuery->whereNotNull('id');
                     });
                } elseif ($isIntegrator) {
                    // IMPORTANT : Inclure TOUS les TransactionDetails pour l'intégrateur
                    // même ceux avec integrator_share_amount = 0, car on veut calculer la part brute
                    // (integrator_share_amount + admin_share_amount) pour tous
                    // Chercher avec user->id ET integrator->id car integrator_creator_id peut être l'un ou l'autre
                    $integratorModel = \App\Models\Integrator::where('user_id', $user->id)->first();
                    $integratorModelId = $integratorModel ? $integratorModel->id : null;
                    
                    $detailQuery->where(function($q) use ($user, $integratorModelId) {
                        $q->where('integrator_creator_id', $user->id);
                        if ($integratorModelId) {
                            $q->orWhere('integrator_creator_id', $integratorModelId);
                        }
                    });
                } elseif ($isOperator) {
                    // Pour les opérateurs/partenaires, inclure aussi les transactions où ils sont propriétaires de la borne
                    $partnerId = $user->partner_id ?? null;
                    $detailQuery->where(function($opQuery) use ($user, $partnerId) {
                        $opQuery->where('operator_id', $user->id)
                               ->orWhereHas('transaction.chargingPoint', function($cpQuery) use ($user, $partnerId) {
                                   $cpQuery->where(function($cpSubQuery) use ($user, $partnerId) {
                                       $cpSubQuery->where('user_id', $user->id)
                                                 ->orWhere('created_by_id', $user->id)
                                                 ->orWhere('created_by', $user->id);
                                       if ($partnerId) {
                                           $cpSubQuery->orWhere('partner_id', $partnerId);
                                       }
                                   });
                                   if ($partnerId) {
                                       $cpQuery->orWhereHas('group', function($groupQ) use ($partnerId) {
                                           $groupQ->where('partner_id', $partnerId);
                                       });
                                   }
                               });
                    })
                    ->where('operator_share_amount', '>', 0);
                }
            });
        
         $transactionDetails = $query->with(['transaction.chargingPoint'])->get();
         
         // Pour l'admin, filtrer en PHP pour vérifier calculation_details si admin_share_amount = 0
         if ($isAdmin) {
             $transactionDetails = $transactionDetails->filter(function($detail) {
                 // Inclure si admin_share_amount > 0
                 if (($detail->admin_share_amount ?? 0) > 0) {
                     return true;
                 }
                 // OU si calculation_details contient admin_fees_amount > 0
                 $calculationDetails = $detail->calculation_details;
                 if ($calculationDetails) {
                     $adminFeesFromDetails = 0;
                     if (is_array($calculationDetails) && isset($calculationDetails['hierarchical_logic']['admin_fees_amount'])) {
                         $adminFeesFromDetails = (float) $calculationDetails['hierarchical_logic']['admin_fees_amount'];
                     } elseif (is_string($calculationDetails)) {
                         $decoded = json_decode($calculationDetails, true);
                         if (is_array($decoded) && isset($decoded['hierarchical_logic']['admin_fees_amount'])) {
                             $adminFeesFromDetails = (float) $decoded['hierarchical_logic']['admin_fees_amount'];
                         }
                     }
                     return $adminFeesFromDetails > 0;
                 }
                 return false;
             });
         }
         
         Log::info('DEBUG: createMissingWalletTransactions', [
             'user_id' => $user->id,
             'role' => $isAdmin ? 'admin' : ($isIntegrator ? 'integrator' : ($isOperator ? 'operator' : 'other')),
             'transaction_details_count' => $transactionDetails->count(),
             'transaction_detail_ids' => $transactionDetails->pluck('id')->toArray()
         ]);
         
         foreach ($transactionDetails as $detail) {
             $transaction = $detail->transaction;
             if (!$transaction) continue;
             
             // Déterminer le montant de crédit attendu
             $expectedCredit = 0;
             $description = '';
             
             if ($isAdmin) {
                 // Pour l'admin, inclure TOUTES les transactions avec admin_share_amount > 0
                 // Peu importe le admin_creator_id (car il n'y a généralement qu'un seul admin)
                 // Utiliser admin_share_amount comme source de vérité
                 $expectedCredit = (float) ($detail->admin_share_amount ?? 0);
                 
                 // Vérifier si admin_fees_amount dans calculation_details est plus élevé
                 $calculationDetails = $detail->calculation_details;
                 if ($calculationDetails) {
                     $adminFeesFromDetails = 0;
                     if (is_array($calculationDetails) && isset($calculationDetails['hierarchical_logic']['admin_fees_amount'])) {
                         $adminFeesFromDetails = (float) $calculationDetails['hierarchical_logic']['admin_fees_amount'];
                     } elseif (is_string($calculationDetails)) {
                         $decoded = json_decode($calculationDetails, true);
                         if (is_array($decoded) && isset($decoded['hierarchical_logic']['admin_fees_amount'])) {
                             $adminFeesFromDetails = (float) $decoded['hierarchical_logic']['admin_fees_amount'];
                         }
                     }
                     
                     // Utiliser la valeur la plus élevée
                     if ($adminFeesFromDetails > $expectedCredit) {
                         $expectedCredit = $adminFeesFromDetails;
                     }
                 }
                 
                 // Si expectedCredit est toujours 0, vérifier dans calculation_details
                 if ($expectedCredit <= 0 && $calculationDetails) {
                     // Déjà fait ci-dessus, mais on continue pour créer la description
                 }
                 
                 if ($expectedCredit > 0) {
                     $description = "Part Admin - Transaction #{$transaction->id}" . 
                         ($transaction->reservation_id ? " - Réservation #{$transaction->reservation_id}" : '');
                 }
            } elseif ($isIntegrator) {
                // Vérifier que integrator_creator_id correspond (user->id OU integrator->id)
                $integratorModel = \App\Models\Integrator::where('user_id', $user->id)->first();
                $integratorModelId = $integratorModel ? $integratorModel->id : null;
                $isIntegratorMatch = ($detail->integrator_creator_id == $user->id) 
                                 || ($integratorModelId && $detail->integrator_creator_id == $integratorModelId);
                
                if ($isIntegratorMatch) {
                    // Pour les intégrateurs :
                    // Money in (Total Credits) = part intégrateur (integrator_share_amount)
                    // Money out (Total Debits) = parts admin déduites (admin_share_amount)
                    
                    $integratorShareAmount = (float) ($detail->integrator_share_amount ?? 0);
                    
                    // Créer un crédit pour la part intégrateur (Money in)
                    if ($integratorShareAmount > 0.01) {
                        $expectedCredit = $integratorShareAmount;
                        $description = "Part Intégrateur - Transaction #{$transaction->id}" . 
                            ($transaction->reservation_id ? " - Réservation #{$transaction->reservation_id}" : '');
                    } else {
                        continue; // Pas de crédit si la part intégrateur est nulle
                    }
                }
            } elseif ($isOperator && $detail->operator_share_amount > 0) {
                // Vérifier si l'opérateur/partenaire correspond (via operator_id OU via la borne)
                $isOperatorMatch = false;
                $partnerId = $user->partner_id ?? null;

                if ($detail->operator_id == $user->id) {
                    $isOperatorMatch = true;
                } elseif ($transaction->chargingPoint) {
                    $cp = $transaction->chargingPoint;
                    $isOperatorMatch = ($cp->user_id == $user->id)
                                     || ($cp->created_by_id == $user->id)
                                     || ($cp->created_by == $user->id)
                                     || ($cp->group && $cp->group->user_id == $user->id)
                                     || ($partnerId && $cp->partner_id == $partnerId)
                                     || ($partnerId && $cp->group && $cp->group->partner_id == $partnerId);
                }
                
                if ($isOperatorMatch) {
                    $expectedCredit = (float) $detail->operator_share_amount;
                    $description = "Part Opérateur - Transaction #{$transaction->id}" . 
                        ($transaction->reservation_id ? " - Réservation #{$transaction->reservation_id}" : '');
                }
            }
            
            if ($expectedCredit <= 0) continue;
            
            // Vérifier si une WalletTransaction existe déjà pour cette transaction
            // Chercher dans les métadonnées JSON et dans la description
            $existingCredit = $wallet->creditTransactions()
                ->where(function($q) use ($transaction, $detail, $expectedCredit) {
                    $q->where(function($subQ) use ($transaction) {
                        // Chercher dans les métadonnées JSON
                        $subQ->whereJsonContains('metadata->transaction_id', $transaction->id)
                            ->orWhere('metadata', 'like', '%"transaction_id":' . $transaction->id . '%');
                    })
                    ->orWhere(function($subQ) use ($transaction) {
                        // Chercher dans la description
                        $subQ->where('description', 'like', "%Transaction #{$transaction->id}%");
                        if ($transaction->reservation_id) {
                            $subQ->orWhere('description', 'like', "%Réservation #{$transaction->reservation_id}%");
                        }
                    })
                    ->orWhere(function($subQ) use ($detail) {
                        // Chercher par transaction_detail_id
                        $subQ->whereJsonContains('metadata->transaction_detail_id', $detail->id)
                            ->orWhere('metadata', 'like', '%"transaction_detail_id":' . $detail->id . '%');
                    });
                })
                ->where(function($amountQ) use ($expectedCredit) {
                    // Tolérance de 0.01 pour les arrondis
                    $amountQ->where('amount', '>=', $expectedCredit - 0.01)
                           ->where('amount', '<=', $expectedCredit + 0.01);
                })
                ->first();
            
            // Si aucune WalletTransaction correspondante, créer une nouvelle
            if (!$existingCredit) {
                try {
                    $wallet->credit(
                        $expectedCredit,
                        $description,
                        [
                            'transaction_id' => $transaction->id,
                            'transaction_detail_id' => $detail->id,
                            'reservation_id' => $transaction->reservation_id,
                            'synchronization' => true,
                            'auto_created' => true,
                            'source' => 'transaction_detail_retroactive'
                        ]
                    );
                    
                    // Pour les intégrateurs, créer aussi un débit pour la part admin (Money out)
                    if ($isIntegrator) {
                        // Vérifier que integrator_creator_id correspond (user->id OU integrator->id)
                        $integratorModel = \App\Models\Integrator::where('user_id', $user->id)->first();
                        $integratorModelId = $integratorModel ? $integratorModel->id : null;
                        $isIntegratorMatch = ($detail->integrator_creator_id == $user->id) 
                                         || ($integratorModelId && $detail->integrator_creator_id == $integratorModelId);
                        
                        if ($isIntegratorMatch) {
                            $adminShareAmount = (float) ($detail->admin_share_amount ?? 0);
                            
                            // Vérifier si admin_fees_amount dans calculation_details est différent
                            $calculationDetails = $detail->calculation_details;
                            if ($calculationDetails) {
                                $adminFeesFromDetails = 0;
                                if (is_array($calculationDetails) && isset($calculationDetails['hierarchical_logic']['admin_fees_amount'])) {
                                    $adminFeesFromDetails = (float) $calculationDetails['hierarchical_logic']['admin_fees_amount'];
                                } elseif (is_string($calculationDetails)) {
                                    $decoded = json_decode($calculationDetails, true);
                                    if (is_array($decoded) && isset($decoded['hierarchical_logic']['admin_fees_amount'])) {
                                        $adminFeesFromDetails = (float) $decoded['hierarchical_logic']['admin_fees_amount'];
                                    }
                                }
                                
                                // Utiliser la valeur la plus élevée
                                if ($adminFeesFromDetails > $adminShareAmount) {
                                    $adminShareAmount = $adminFeesFromDetails;
                                }
                            }
                            
                            // Créer un débit pour la part admin si elle est > 0
                            if ($adminShareAmount > 0.01) {
                                // Vérifier si un débit existe déjà pour cette transaction
                                $existingDebit = $wallet->debitTransactions()
                                    ->where(function($q) use ($transaction, $detail, $adminShareAmount) {
                                        $q->where(function($subQ) use ($transaction) {
                                            $subQ->whereJsonContains('metadata->transaction_id', $transaction->id)
                                                ->orWhere('metadata', 'like', '%"transaction_id":' . $transaction->id . '%');
                                        })
                                        ->orWhere(function($subQ) use ($transaction) {
                                            $subQ->where('description', 'like', "%Transaction #{$transaction->id}%");
                                            if ($transaction->reservation_id) {
                                                $subQ->orWhere('description', 'like', "%Réservation #{$transaction->reservation_id}%");
                                            }
                                        })
                                        ->orWhere(function($subQ) use ($detail) {
                                            $subQ->whereJsonContains('metadata->transaction_detail_id', $detail->id)
                                                ->orWhere('metadata', 'like', '%"transaction_detail_id":' . $detail->id . '%');
                                        });
                                    })
                                    ->where(function($amountQ) use ($adminShareAmount) {
                                        $amountQ->where('amount', '>=', $adminShareAmount - 0.01)
                                               ->where('amount', '<=', $adminShareAmount + 0.01);
                                    })
                                    ->first();
                                
                                if (!$existingDebit) {
                                    $wallet->debit(
                                        $adminShareAmount,
                                        "Déduction part Admin - Transaction #{$transaction->id}" . 
                                            ($transaction->reservation_id ? " - Réservation #{$transaction->reservation_id}" : ''),
                                        [
                                            'transaction_id' => $transaction->id,
                                            'transaction_detail_id' => $detail->id,
                                            'reservation_id' => $transaction->reservation_id,
                                            'synchronization' => true,
                                            'auto_created' => true,
                                            'source' => 'transaction_detail_retroactive',
                                            'type' => 'admin_share_deduction'
                                        ]
                                    );
                                }
                            }
                        }
                    }
                    
                    Log::info('WalletTransaction créée rétroactivement', [
                        'user_id' => $user->id,
                        'transaction_id' => $transaction->id,
                        'transaction_detail_id' => $detail->id,
                        'amount' => $expectedCredit,
                        'description' => $description
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de la création rétroactive de WalletTransaction', [
                        'user_id' => $user->id,
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
    
    /**
     * Synchroniser les balances de tous les utilisateurs
     */
    public function synchronizeAllUserBalances(): array
    {
        $results = [];
        
        // Récupérer tous les utilisateurs avec des rôles pertinents
        $users = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['admin', 'super_admin', 'integrator', 'operator', 'partner']);
        })->get();
        
        foreach ($users as $user) {
            try {
                $result = $this->synchronizeUserBalance($user);
                $results[$user->id] = $result;
            } catch (\Exception $e) {
                $results[$user->id] = [
                    'synchronized' => false,
                    'error' => $e->getMessage(),
                    'user_id' => $user->id
                ];
            }
        }
        
        return $results;
    }
}


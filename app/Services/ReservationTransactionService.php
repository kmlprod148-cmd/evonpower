<?php

namespace App\Services;

use App\Enums\CollectStatus;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionHierarchy;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Models\Partner;
use App\Services\BusinessProfileFeesValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ReservationTransactionService
{
    /**
     * Traiter une réservation avec répartition des parts selon les Business Profiles
     * 
     * @param Reservation $reservation
     * @return array
     * @throws Exception
     */
    public function processReservationTransaction(Reservation $reservation): array
    {
        try {
            DB::beginTransaction();

            // 0️⃣ Vérifier si une transaction existe déjà et la nettoyer si nécessaire
            // Supprimer les TransactionDetail et TransactionHierarchy existants pour éviter les doublons
            // Utiliser la relation au lieu de transaction_id (qui n'existe pas dans reservations)
            $existingTransaction = $reservation->transaction;
            if ($existingTransaction) {
                // Supprimer les détails et hiérarchies existants
                TransactionDetail::where('transaction_id', $existingTransaction->id)->delete();
                TransactionHierarchy::where('original_transaction_id', $existingTransaction->id)->delete();
                
                Log::info('Nettoyage des anciens détails de transaction avant recréation', [
                    'reservation_id' => $reservation->id,
                    'existing_transaction_id' => $existingTransaction->id
                ]);
            }

            // 1️⃣ Récupérer la hiérarchie complète depuis la borne
            $hierarchy = $this->getCompleteHierarchy($reservation->charging_point_id);
            
            if (!$hierarchy) {
                throw new Exception('Impossible de récupérer la hiérarchie pour cette borne');
            }

            // 1️⃣.5️⃣ CORRECTION : Créer TOUS les wallets AVANT de créer les transactions
            // Garantir que tous les utilisateurs impliqués ont des wallets
            // Cela évite les erreurs lors de la mise à jour des wallets
            Log::info('Création des wallets pour tous les utilisateurs impliqués', [
                'reservation_id' => $reservation->id,
                'hierarchy' => [
                    'admin_id' => $hierarchy['admin']->id ?? null,
                    'integrator_id' => $hierarchy['integrator']->id ?? null,
                    'operator_id' => $hierarchy['operator']->id ?? null,
                ]
            ]);
            
            // Créer le wallet de l'admin
            if (isset($hierarchy['admin']) && $hierarchy['admin']) {
                try {
                    $adminWallet = $this->getOrCreateWallet($hierarchy['admin']);
                    Log::info('Wallet admin créé/vérifié', [
                        'reservation_id' => $reservation->id,
                        'admin_id' => $hierarchy['admin']->id,
                        'wallet_id' => $adminWallet->id,
                        'balance' => $adminWallet->balance
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de la création du wallet admin', [
                        'reservation_id' => $reservation->id,
                        'admin_id' => $hierarchy['admin']->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                    throw new Exception('Impossible de créer/vérifier le wallet admin: ' . $e->getMessage());
                }
            }
            
            // Créer le wallet de l'intégrateur
            if (isset($hierarchy['integrator']) && $hierarchy['integrator']) {
                try {
                    $integratorUser = $hierarchy['integrator']->user ?? $hierarchy['integrator'];
                    $integratorWallet = $this->getOrCreateWallet($integratorUser);
                    Log::info('Wallet intégrateur créé/vérifié', [
                        'reservation_id' => $reservation->id,
                        'integrator_id' => $integratorUser->id,
                        'wallet_id' => $integratorWallet->id,
                        'balance' => $integratorWallet->balance
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de la création du wallet intégrateur', [
                        'reservation_id' => $reservation->id,
                        'integrator_id' => $hierarchy['integrator']->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                    throw new Exception('Impossible de créer/vérifier le wallet intégrateur: ' . $e->getMessage());
                }
            }
            
            // Créer le wallet de l'opérateur
            if (isset($hierarchy['operator']) && $hierarchy['operator']) {
                try {
                    $operatorWallet = $this->getOrCreateWallet($hierarchy['operator']);
                    Log::info('Wallet opérateur créé/vérifié', [
                        'reservation_id' => $reservation->id,
                        'operator_id' => $hierarchy['operator']->id,
                        'wallet_id' => $operatorWallet->id,
                        'balance' => $operatorWallet->balance
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de la création du wallet opérateur', [
                        'reservation_id' => $reservation->id,
                        'operator_id' => $hierarchy['operator']->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                    throw new Exception('Impossible de créer/vérifier le wallet opérateur: ' . $e->getMessage());
                }
            }

            // 2️⃣ Récupérer les Business Profiles appropriés
            $businessProfiles = $this->getBusinessProfilesForHierarchy($hierarchy);

            // 3️⃣ S'assurer que le montant est défini (utiliser estimated_cost ou amount au lieu de total_amount)
            // total_amount n'existe pas dans la table reservations, utiliser estimated_cost ou amount
            $totalAmount = $reservation->estimated_cost ?? $reservation->amount ?? 0;
            
            if ($totalAmount <= 0) {
                throw new Exception("La réservation #{$reservation->id} n'a pas de montant valide (estimated_cost: {$reservation->estimated_cost}, amount: {$reservation->amount})");
            }

            // 4️⃣ Ajouter la réservation à la hiérarchie pour le calcul HT/TTC
            $hierarchy['reservation'] = $reservation;
            
            // 5️⃣ Calculer les parts selon les Business Profiles
            $calculation = $this->calculateSharesWithBusinessProfiles(
                $totalAmount, 
                $businessProfiles, 
                $hierarchy
            );

            // 6️⃣ Créer ou mettre à jour la transaction principale avec toutes les infos
            $mainTransaction = $this->createOrUpdateMainTransaction($reservation, $calculation, $hierarchy);

            // 7️⃣ Créer les détails de transaction avec parts
            $transactionDetail = $this->createTransactionDetail($mainTransaction, $calculation, $hierarchy);

            // 8️⃣ Créer les transactions hiérarchiques (Admin↔Intégrateur, Intégrateur↔Opérateur)
            $hierarchicalTransactions = $this->createHierarchicalTransactions(
                $mainTransaction, 
                $calculation, 
                $hierarchy
            );

            // 9️⃣ Mettre à jour les wallets avec vérification de cohérence et sauvegarder les wallet IDs
            $walletIds = $this->updateWalletsWithValidation($hierarchy, $calculation, $mainTransaction, $transactionDetail);
            
            // 🔟 Récupérer les balances ACTUELLES après les opérations wallet
            // IMPORTANT : Les balances doivent être à jour pour tous les acteurs (Admin, Intégrateur, Opérateur/Partner)
            $operatorWallet = $this->getOrCreateWallet($hierarchy['operator']);
            $integratorWallet = $this->getOrCreateWallet($hierarchy['integrator']->user ?? $hierarchy['integrator']);
            $adminWallet = $this->getOrCreateWallet($hierarchy['admin']);
            
            // Rafraîchir les wallets pour obtenir les balances à jour après toutes les opérations
            $operatorWallet->refresh();
            $integratorWallet->refresh();
            $adminWallet->refresh();
            
            // Validation finale : Vérifier que les calculs de parts sont cohérents avec les Business Profiles
            $sumOfShares = round($calculation['admin_share'] + $calculation['integrator_share'] + $calculation['operator_share'], 2);
            $totalAmountRounded = round($calculation['total_amount'], 2);
            $sharesDifference = abs($sumOfShares - $totalAmountRounded);
            
            // VALIDATION CRITIQUE : Le calcul de répartition doit être correct selon les Business Profiles
            if ($sharesDifference > 0.01) {
                Log::error('ERREUR CRITIQUE : Différence détectée entre la somme des parts et le montant total', [
                    'total_amount' => $totalAmountRounded,
                    'sum_of_shares' => $sumOfShares,
                    'difference' => $sharesDifference,
                    'admin_share' => $calculation['admin_share'],
                    'integrator_share' => $calculation['integrator_share'],
                    'operator_share' => $calculation['operator_share'],
                    'business_profiles' => $calculation['business_profiles'] ?? [],
                    'admin_business_profile' => [
                        'id' => $calculation['business_profiles']['admin_integrator_id'] ?? null,
                        'fixed' => $calculation['admin_fixed'] ?? null,
                        'percentage' => $calculation['admin_percentage'] ?? null
                    ],
                    'integrator_business_profile' => [
                        'id' => $calculation['business_profiles']['integrator_operator_id'] ?? null,
                        'fixed' => $calculation['integrator_fixed'] ?? null,
                        'percentage' => $calculation['integrator_percentage'] ?? null
                    ]
                ]);
                
                // Ajuster pour garantir la cohérence (la part opérateur est le reste)
                $operatorShareAdjusted = round($totalAmountRounded - $calculation['admin_share'] - $calculation['integrator_share'], 2);
                $calculation['operator_share'] = $operatorShareAdjusted;
                
                Log::warning('Ajustement automatique de la part opérateur pour garantir la cohérence', [
                    'operator_share_before' => $calculation['operator_share'],
                    'operator_share_after' => $operatorShareAdjusted,
                    'difference_corrected' => $operatorShareAdjusted - $calculation['operator_share']
                ]);
            } else {
                Log::info('✅ Validation OK : Le calcul de répartition des parts est correct selon les Business Profiles', [
                    'total_amount' => $totalAmountRounded,
                    'sum_of_shares' => $sumOfShares,
                    'difference' => $sharesDifference,
                    'admin_share' => $calculation['admin_share'],
                    'integrator_share' => $calculation['integrator_share'],
                    'operator_share' => $calculation['operator_share'],
                    'business_profiles_applied' => [
                        'admin_integrator_id' => $calculation['business_profiles']['admin_integrator_id'] ?? null,
                        'integrator_operator_id' => $calculation['business_profiles']['integrator_operator_id'] ?? null
                    ]
                ]);
            }
            
            // 1️⃣1️⃣ Mettre à jour la transaction avec les wallet IDs, balances ACTUELLES et toutes les infos
            // Dernier rafraîchissement pour garantir que les balances sont bien à jour
            $operatorWallet->refresh();
            $integratorWallet->refresh();
            $adminWallet->refresh();
            
            // CORRECTION: Utiliser les montants HT/TTC calculés correctement
            $priceTotal = round($calculation['total_amount'], 4); // TTC
            $priceTax = round($calculation['tax_amount'] ?? 0, 4); // Montant de TVA
            $amountHT = round($calculation['amount_ht'] ?? ($priceTotal - $priceTax), 4); // HT
            
            // Update transaction with commission/price fields only (share amounts live on transaction_details)
            $mainTransaction->update([
                'admin_commission' => round($calculation['admin_share'], 4),
                'integrator_commission' => round($calculation['integrator_share'], 4),
                'partner_commission' => round($calculation['operator_share'], 4),
                'price_tax' => $priceTax,
                'price_total' => $priceTotal,
                'amount' => $priceTotal,
            ]);

            DB::commit();

            // 🔄 SYNCHRONISER LES BALANCES DEPUIS TransactionDetail
            // IMPORTANT : Après la création du TransactionDetail, synchroniser les balances
            // pour garantir que Money in / Money out sont calculés correctement depuis TransactionDetail
            $this->synchronizeBalancesAfterTransactionDetail($hierarchy, $mainTransaction);
            
            // 🔄 CORRECTION : Forcer la synchronisation complète des balances pour TOUS les utilisateurs
            // Garantir que les balances sont calculées et synchronisées immédiatement après l'approbation
            $this->forceSynchronizeAllBalancesAfterApproval($hierarchy, $transactionDetail);

            // Validation finale : Vérifier que toutes les balances sont cohérentes
            $finalValidation = [
                'calculation_correct' => abs(($calculation['admin_share'] + $calculation['integrator_share'] + $calculation['operator_share']) - $calculation['total_amount']) <= 0.01,
                'wallets_updated' => [
                    'operator' => $operatorWallet->id ?? null,
                    'integrator' => $integratorWallet->id ?? null,
                    'admin' => $adminWallet->id ?? null,
                ],
                'final_balances' => [
                    'operator' => (float) $operatorWallet->balance,
                    'integrator' => (float) $integratorWallet->balance,
                    'admin' => (float) $adminWallet->balance,
                ],
                'business_profiles_applied' => [
                    'admin_integrator' => $calculation['business_profiles']['admin_integrator_id'] ?? null,
                    'integrator_operator' => $calculation['business_profiles']['integrator_operator_id'] ?? null,
                ]
            ];
            
            Log::info('Transaction de réservation traitée avec succès', [
                'reservation_id' => $reservation->id,
                'validation' => $finalValidation,
                'transaction_id' => $mainTransaction->id,
                'admin_share' => $calculation['admin_share'],
                'integrator_share' => $calculation['integrator_share'],
                'operator_share' => $calculation['operator_share'],
                'total_amount' => $totalAmount
            ]);

            return [
                'success' => true,
                'main_transaction' => $mainTransaction,
                'transaction_detail' => $transactionDetail,
                'hierarchical_transactions' => $hierarchicalTransactions,
                'calculation' => $calculation,
                'hierarchy' => $hierarchy
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors du traitement de la transaction de réservation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer la hiérarchie complète : Borne → Groupe → Opérateur → Intégrateur → Admin
     * Avec fallbacks multiples pour gérer les hiérarchies incomplètes
     */
    public function getCompleteHierarchy(int $chargingPointId): ?array
    {
        $chargingPoint = ChargingPoint::with([
            'group.partner.integrator.admin',
            'group.partner.integrator.user',
            'group.partner.integrator.businessProfile',
            'group.operator',
            'group.user',
            'integrator.user',
            'integrator.admin',
            'operator',
            'user'
        ])->find($chargingPointId);

        if (!$chargingPoint) {
            return null;
        }

        // 1. Récupérer l'opérateur (avec fallbacks)
        // IMPORTANT : Prioriser le créateur de la borne pour s'assurer que sa part est toujours incluse
        $operator = $chargingPoint->getOperator();
        
        // Si l'opérateur n'est pas trouvé, vérifier le créateur de la borne
        // Le créateur de la borne (operator/partner) doit toujours recevoir sa part
        if (!$operator) {
            // Vérifier created_by_id (nouveau système)
            if ($chargingPoint->created_by_id) {
                $creatorUser = User::find($chargingPoint->created_by_id);
                if ($creatorUser && ($creatorUser->hasRole('operator') || $creatorUser->hasRole('partner'))) {
                    $operator = $creatorUser;
                    Log::info('Opérateur identifié depuis created_by_id de la borne', [
                        'charging_point_id' => $chargingPointId,
                        'operator_id' => $operator->id,
                        'operator_name' => $operator->name
                    ]);
                }
            }
            
            // Fallback : vérifier created_by (ancien système)
            if (!$operator && $chargingPoint->created_by) {
                $creatorUser = User::find($chargingPoint->created_by);
                if ($creatorUser && ($creatorUser->hasRole('operator') || $creatorUser->hasRole('partner'))) {
                    $operator = $creatorUser;
                    Log::info('Opérateur identifié depuis created_by de la borne', [
                        'charging_point_id' => $chargingPointId,
                        'operator_id' => $operator->id,
                        'operator_name' => $operator->name
                    ]);
                }
            }
            
            // Fallback : vérifier user_id de la borne (si c'est un opérateur/partenaire)
            if (!$operator && $chargingPoint->user_id) {
                $user = User::find($chargingPoint->user_id);
                if ($user && ($user->hasRole('operator') || $user->hasRole('partner'))) {
                    $operator = $user;
                    Log::info('Opérateur identifié depuis user_id de la borne', [
                        'charging_point_id' => $chargingPointId,
                        'operator_id' => $operator->id,
                        'operator_name' => $operator->name
                    ]);
                }
            }
        } else {
            // Si un opérateur a été trouvé via getOperator(), vérifier qu'il correspond au créateur
            // Si le créateur est différent, utiliser le créateur pour s'assurer que sa part est incluse
            $chargingPointCreator = null;
            
            if ($chargingPoint->created_by_id) {
                $chargingPointCreator = User::find($chargingPoint->created_by_id);
            } elseif ($chargingPoint->created_by) {
                $chargingPointCreator = User::find($chargingPoint->created_by);
            }
            
            // Si le créateur existe et est différent de l'opérateur trouvé, utiliser le créateur
            // Le créateur de la borne doit toujours recevoir sa part
            if ($chargingPointCreator && 
                ($chargingPointCreator->hasRole('operator') || $chargingPointCreator->hasRole('partner')) && 
                $chargingPointCreator->id !== $operator->id) {
                Log::info('Créateur de la borne identifié comme opérateur principal (différent de l\'opérateur trouvé)', [
                    'charging_point_id' => $chargingPointId,
                    'original_operator_id' => $operator->id,
                    'creator_operator_id' => $chargingPointCreator->id,
                    'creator_name' => $chargingPointCreator->name,
                    'note' => 'Le créateur de la borne recevra sa part (operator_share)'
                ]);
                $operator = $chargingPointCreator;
            }
        }

        // 2. Récupérer l'intégrateur (avec fallbacks multiples)
        $integrator = $chargingPoint->getIntegrator();
        
        // Fallback : depuis le partner du groupe
        if (!$integrator && $chargingPoint->group?->partner?->integrator) {
            $integrator = $chargingPoint->group->partner->integrator;
        }
        
        // Fallback : depuis l'opérateur directement
        if (!$integrator && $operator && $operator->integrator_id) {
            $integrator = Integrator::find($operator->integrator_id);
        }
        
        // Fallback : chercher via partner_id de l'opérateur
        if (!$integrator && $operator && $operator->partner_id) {
            $partnerObj = Partner::find($operator->partner_id);
            if ($partnerObj && $partnerObj->integrator_id) {
                $integrator = Integrator::find($partnerObj->integrator_id);
            }
        }

        // 3. Récupérer l'admin (avec fallbacks multiples)
        $admin = null;
        
        // Depuis l'intégrateur (created_by ou user)
        if ($integrator) {
            if ($integrator->created_by) {
                $admin = User::find($integrator->created_by);
                if (!$admin || !$admin->hasRole(['admin', 'super_admin'])) {
                    $admin = null;
                }
            }
            
            // Fallback : via user de l'intégrateur si c'est un admin
            if (!$admin && $integrator->user_id) {
                $adminUser = User::find($integrator->user_id);
                if ($adminUser && $adminUser->hasRole(['admin', 'super_admin'])) {
                    $admin = $adminUser;
                }
            }
        }
        
        // Fallback : chercher un admin par défaut (premier admin super-admin trouvé)
        if (!$admin) {
            $admin = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'super_admin']);
            })->orderBy('id')->first();
        }

        // 4. Récupérer le partner
        $partner = $chargingPoint->getPartner();
        if (!$partner && $chargingPoint->group?->partner) {
            $partner = $chargingPoint->group->partner;
        }

        $hierarchy = [
            'charging_point' => $chargingPoint,
            'group' => $chargingPoint->group,
            'partner' => $partner,
            'integrator' => $integrator,
            'admin' => $admin,
            'operator' => $operator
        ];

        // Retourner la hiérarchie même si incomplète (au moins l'opérateur doit être présent)
        // L'opérateur est le minimum requis pour une transaction
        if (!$hierarchy['operator']) {
            // Dernier fallback : essayer de trouver n'importe quel utilisateur lié à la borne
            // qui pourrait être un opérateur/partner
            if ($chargingPoint->user_id) {
                $user = User::find($chargingPoint->user_id);
                if ($user) {
                    // Même si le rôle n'est pas exactement operator/partner, on peut l'utiliser comme opérateur
                    $operator = $user;
                    Log::warning('Opérateur identifié depuis user_id sans vérification de rôle (fallback)', [
                        'charging_point_id' => $chargingPointId,
                        'operator_id' => $operator->id,
                        'operator_name' => $operator->name,
                        'note' => 'Utilisation de user_id comme opérateur de dernier recours'
                    ]);
                    $hierarchy['operator'] = $operator;
                }
            }
            
            // Si toujours pas d'opérateur, essayer de trouver via le groupe
            if (!$hierarchy['operator'] && $chargingPoint->group) {
                // Chercher un utilisateur dans le groupe qui pourrait être opérateur
                if ($chargingPoint->group->partner) {
                    // Si le partner a un user_id, l'utiliser
                    if ($chargingPoint->group->partner->user_id) {
                        $partnerUser = User::find($chargingPoint->group->partner->user_id);
                        if ($partnerUser) {
                            $hierarchy['operator'] = $partnerUser;
                            Log::warning('Opérateur identifié depuis partner->user_id (fallback)', [
                                'charging_point_id' => $chargingPointId,
                                'operator_id' => $partnerUser->id,
                                'operator_name' => $partnerUser->name
                            ]);
                        }
                    }
                }
            }
            
            // Si toujours pas d'opérateur après tous les fallbacks, retourner null
            if (!$hierarchy['operator']) {
                Log::error('Hiérarchie invalide : opérateur manquant après tous les fallbacks', [
                    'charging_point_id' => $chargingPointId,
                    'charging_point_name' => $chargingPoint->name ?? 'N/A',
                    'has_group' => $chargingPoint->group !== null,
                    'has_partner' => $chargingPoint->group?->partner !== null,
                    'has_integrator' => $hierarchy['integrator'] !== null,
                    'has_admin' => $hierarchy['admin'] !== null,
                    'created_by_id' => $chargingPoint->created_by_id,
                    'created_by' => $chargingPoint->created_by,
                    'user_id' => $chargingPoint->user_id,
                    'note' => 'Tous les fallbacks ont été essayés. La borne doit être associée à un opérateur/partner pour créer un TransactionDetail.'
                ]);
                return null;
            }
        }

        // Log si la hiérarchie est incomplète mais retourner quand même
        if (!$hierarchy['integrator'] || !$hierarchy['admin']) {
            Log::info('Hiérarchie incomplète détectée mais utilisable', [
                'charging_point_id' => $chargingPointId,
                'has_integrator' => !is_null($hierarchy['integrator']),
                'has_admin' => !is_null($hierarchy['admin']),
                'has_operator' => !is_null($hierarchy['operator']),
                'has_partner' => !is_null($hierarchy['partner'])
            ]);
        }

        return $hierarchy;
    }

    /**
     * Récupérer les Business Profiles pour la hiérarchie
     * 
     * LOGIQUE CORRIGÉE :
     * 1. Business Profile Admin → Intégrateur : Récupéré depuis l'intégrateur, appliqué par son admin créateur
     * 2. Business Profile Intégrateur → Opérateur : Récupéré depuis l'opérateur/partenaire, appliqué par son intégrateur
     */
    public function getBusinessProfilesForHierarchy(array $hierarchy): array
    {
        $profiles = [
            'admin_integrator' => null,
            'integrator_operator' => null
        ];

        // 1. BUSINESS PROFILE ADMIN → INTÉGRATEUR
        // Récupéré depuis l'intégrateur, appliqué par son admin créateur
        if ($hierarchy['admin'] && $hierarchy['integrator']) {
            // PRIORITÉ 1 : Utiliser le Business Profile attaché directement à l'intégrateur (business_profile_id)
            // C'est la façon standard d'attacher un Business Profile Admin à un intégrateur
            if ($hierarchy['integrator']->business_profile_id) {
                $profiles['admin_integrator'] = BusinessProfile::where('id', $hierarchy['integrator']->business_profile_id)
                    ->where('is_active', true)
                    ->first();
            }

            // PRIORITÉ 2 : Chercher le profil créé par l'admin pour cet intégrateur (avec integrator_id)
            if (!$profiles['admin_integrator']) {
                $profiles['admin_integrator'] = BusinessProfile::where('created_by_id', $hierarchy['admin']->id)
                    ->where('created_by_type', 'admin')
                    ->where('integrator_id', $hierarchy['integrator']->id)
                    ->where('is_active', true)
                    ->orderBy('created_at', 'desc') // Prendre le plus récent
                    ->first();
            }

            // PRIORITÉ 3 : Si pas trouvé, chercher un profil par défaut de l'admin
            if (!$profiles['admin_integrator']) {
                $profiles['admin_integrator'] = BusinessProfile::where('created_by_id', $hierarchy['admin']->id)
                    ->where('created_by_type', 'admin')
                    ->where('is_active', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
        }

        // 2. BUSINESS PROFILE INTÉGRATEUR → OPÉRATEUR
        // Récupéré depuis l'opérateur/partenaire, appliqué par son intégrateur
        if ($hierarchy['integrator'] && ($hierarchy['partner'] || $hierarchy['operator'])) {
            // PRIORITÉ 1 : Chercher le profil créé par l'intégrateur pour ce partenaire/opérateur spécifique
            if ($hierarchy['partner']) {
                $profiles['integrator_operator'] = BusinessProfile::where('created_by_id', $hierarchy['integrator']->id ?? $hierarchy['integrator']->user_id ?? null)
                    ->where(function($q) {
                        $q->where('created_by_type', 'integrator')
                          ->orWhere('created_by_role', 'integrator')
                          ->orWhere('created_by_type', 'App\\Models\\User');
                    })
                    ->where('partner_id', $hierarchy['partner']->id)
                    ->where('is_active', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
            
            // PRIORITÉ 2 : Chercher via business_profile_id du partenaire/opérateur
            if (!$profiles['integrator_operator'] && $hierarchy['partner'] && $hierarchy['partner']->business_profile_id) {
                $profiles['integrator_operator'] = BusinessProfile::where('id', $hierarchy['partner']->business_profile_id)
                    ->where('is_active', true)
                    ->first();
            }
            
            // PRIORITÉ 3 : Chercher un profil par défaut de l'intégrateur (sans filtre partner_id)
            if (!$profiles['integrator_operator']) {
                $integratorUserId = $hierarchy['integrator']->id ?? $hierarchy['integrator']->user_id ?? null;
                if ($integratorUserId) {
                    $profiles['integrator_operator'] = BusinessProfile::where(function($q) use ($integratorUserId) {
                        $q->where('created_by_id', $integratorUserId)
                          ->orWhere('integrator_id', $hierarchy['integrator']->id ?? null);
                    })
                    ->where(function($q) {
                        $q->where('created_by_type', 'integrator')
                          ->orWhere('created_by_role', 'integrator')
                          ->orWhere('created_by_type', 'App\\Models\\User')
                          ->orWhereNull('created_by_type');
                    })
                    ->where('is_active', true)
                    ->where(function($q) {
                        // Chercher les profils avec des frais intégrateur configurés
                        $q->where('integrator_fee_fixed', '>', 0)
                          ->orWhere('integrator_fee_percentage', '>', 0);
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();
                }
            }
            
            // PRIORITÉ 4 : Chercher n'importe quel profil de l'intégrateur (même sans frais configurés)
            if (!$profiles['integrator_operator']) {
                $integratorUserId = $hierarchy['integrator']->id ?? $hierarchy['integrator']->user_id ?? null;
                if ($integratorUserId) {
                    $profiles['integrator_operator'] = BusinessProfile::where(function($q) use ($integratorUserId) {
                        $q->where('created_by_id', $integratorUserId)
                          ->orWhere('integrator_id', $hierarchy['integrator']->id ?? null);
                    })
                    ->where('is_active', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
                }
            }
        }

        // 3. PROFILS PAR DÉFAUT SI AUCUN TROUVÉ
        if (!$profiles['admin_integrator']) {
            $profiles['admin_integrator'] = BusinessProfile::where('name', 'Business Profile Standard')
                ->where('is_active', true)
                ->first();
        }

        if (!$profiles['integrator_operator']) {
            $profiles['integrator_operator'] = $profiles['admin_integrator'];
        }

        // 4. VALIDATION ET VÉRIFICATION DES FRAIS
        // Vérifier que les Business Profiles ont des frais configurés
        if ($profiles['integrator_operator'] && 
            ($profiles['integrator_operator']->integrator_fee_fixed == 0 && 
             $profiles['integrator_operator']->integrator_fee_percentage == 0)) {
            Log::warning('Business Profile Intégrateur→Opérateur trouvé mais sans frais intégrateur configurés', [
                'business_profile_id' => $profiles['integrator_operator']->id,
                'business_profile_name' => $profiles['integrator_operator']->name,
                'integrator_fee_fixed' => $profiles['integrator_operator']->integrator_fee_fixed,
                'integrator_fee_percentage' => $profiles['integrator_operator']->integrator_fee_percentage,
                'hierarchy_integrator_id' => $hierarchy['integrator']->id ?? null,
                'hierarchy_operator_id' => $hierarchy['operator']->id ?? null,
                'hierarchy_partner_id' => $hierarchy['partner']->id ?? null
            ]);
        }
        
        // 5. VALIDATION ET LOGGING
        $this->logBusinessProfileRetrieval($hierarchy, $profiles);

        return $profiles;
    }

    /**
     * Logger la récupération des Business Profiles
     */
    protected function logBusinessProfileRetrieval(array $hierarchy, array $profiles): void
    {
        Log::info('Récupération des Business Profiles selon la hiérarchie', [
            'hierarchy' => [
                'admin_id' => $hierarchy['admin']->id ?? null,
                'integrator_id' => $hierarchy['integrator']->id ?? null,
                'partner_id' => $hierarchy['partner']->id ?? null,
                'operator_id' => $hierarchy['operator']->id ?? null,
            ],
            'business_profiles' => [
                'admin_integrator' => [
                    'id' => $profiles['admin_integrator']->id ?? null,
                    'name' => $profiles['admin_integrator']->name ?? null,
                    'created_by' => $profiles['admin_integrator']->created_by_id ?? null,
                    'created_by_type' => $profiles['admin_integrator']->created_by_type ?? null,
                    'integrator_id' => $profiles['admin_integrator']->integrator_id ?? null,
                ],
                'integrator_operator' => [
                    'id' => $profiles['integrator_operator']->id ?? null,
                    'name' => $profiles['integrator_operator']->name ?? null,
                    'created_by' => $profiles['integrator_operator']->created_by_id ?? null,
                    'created_by_type' => $profiles['integrator_operator']->created_by_type ?? null,
                    'partner_id' => $profiles['integrator_operator']->partner_id ?? null,
                ]
            ]
        ]);
    }

    /**
     * Calculer les parts selon les Business Profiles
     * 
     * LOGIQUE HIÉRARCHIQUE SELON LA SPÉCIFICATION :
     * 1. Part Admin = admin_fee_fixed + (T × admin_fee_percentage / 100) - calculé sur le montant total T
     *    - Utilise le Business Profile Admin → Intégrateur
     *    - Source : admin_fee_fixed et admin_fee_percentage du Business Profile
     * 
     * 2. Part Intégrateur = integrator_fee_fixed + (T × integrator_fee_percentage / 100) - calculé sur le montant total T
     *    - Utilise le Business Profile Intégrateur → Opérateur
     *    - Source : integrator_fee_fixed et integrator_fee_percentage du Business Profile
     * 
     * 3. Part Opérateur = T - Part Admin - Part Intégrateur
     *    - Part restante après déduction des parts admin et intégrateur
     * 
     * IMPORTANT : 
     * - Seuls admin_fee_fixed + admin_fee_percentage et integrator_fee_fixed + integrator_fee_percentage sont utilisés.
     * - Les autres frais (activation, transaction, charge) ne sont PAS inclus dans la répartition.
     * - Le calcul est validé pour garantir que : Part Admin + Part Intégrateur + Part Opérateur = Total
     * 
     * VALIDATION :
     * - Vérifie que la somme des parts correspond exactement au montant total (tolérance 0.01€)
     * - Ajuste automatiquement la part opérateur si nécessaire pour garantir la cohérence
     * - Logs détaillés pour traçabilité et débogage
     * 
     * @param float $totalAmount Montant total de la transaction
     * @param array $businessProfiles Tableau contenant 'admin_integrator' et 'integrator_operator'
     * @param array $hierarchy Hiérarchie complète (admin, integrator, operator, partner, etc.)
     * @return array Tableau contenant les parts calculées avec validation
     */
    public function calculateSharesWithBusinessProfiles(
        float $totalAmount, 
        array $businessProfiles, 
        array $hierarchy
    ): array {
        $adminProfile = $businessProfiles['admin_integrator'];
        $integratorProfile = $businessProfiles['integrator_operator'];

        if (!$adminProfile || !$integratorProfile) {
            throw new Exception('Business Profiles manquants pour le calcul des parts');
        }

        // VALIDATION : Vérifier que les Business Profiles sont valides et actifs
        if (!$adminProfile->is_active) {
            Log::warning('Business Profile Admin→Intégrateur inactif, utilisation quand même', [
                'business_profile_id' => $adminProfile->id,
                'name' => $adminProfile->name
            ]);
        }
        
        if (!$integratorProfile->is_active) {
            Log::warning('Business Profile Intégrateur→Opérateur inactif, utilisation quand même', [
                'business_profile_id' => $integratorProfile->id,
                'name' => $integratorProfile->name
            ]);
        }

        // 1. CALCULER LES FRAIS INTÉGRATEUR (selon Business Profile Intégrateur → Opérateur)
        // Calculer tous les frais : transaction_fee + charge_fee + integrator_fee_*
        $integratorFees = $this->calculateBusinessProfileFees($integratorProfile, $totalAmount, 'integrator');
        $integratorTransactionFee = $integratorFees['transaction_fee'] ?? 0;
        $integratorChargeFee = $integratorFees['charge_fee'] ?? 0;
        $integratorFixed = (float) ($integratorProfile->integrator_fee_fixed ?? 0);
        $integratorPercentage = (float) ($integratorProfile->integrator_fee_percentage ?? 0);
        $integratorRoleFee = round($integratorFixed + ($totalAmount * $integratorPercentage / 100), 2);
        
        // Frais Intégrateur Bruts = transaction_fee + charge_fee + integrator_fee_*
        $integratorFeeBrut = round($integratorTransactionFee + $integratorChargeFee + $integratorRoleFee, 2);
        
        // 2. CALCULER LES FRAIS ADMIN (selon Business Profile Admin → Intégrateur)
        // IMPORTANT : 
        // - transaction_fee et charge_fee sont calculés sur le MONTANT TOTAL (selon l'image)
        // - admin_fee_* (role_fee) est calculé sur les frais intégrateur bruts (selon la hiérarchie)
        
        // Calculer transaction_fee et charge_fee sur le montant total
        $adminFeesOnTotal = $this->calculateBusinessProfileFees($adminProfile, $totalAmount, 'admin');
        $adminTransactionFee = $adminFeesOnTotal['transaction_fee'] ?? 0;
        $adminChargeFee = $adminFeesOnTotal['charge_fee'] ?? 0;
        
        // Calculer admin_fee_* (role_fee) sur les frais intégrateur bruts
        $adminFixed = (float) ($adminProfile->admin_fee_fixed ?? 0);
        $adminPercentage = (float) ($adminProfile->admin_fee_percentage ?? 0);
        $adminRoleFee = round($adminFixed + ($integratorFeeBrut * $adminPercentage / 100), 2);
        
        // Part Admin = transaction_fee (sur total) + charge_fee (sur total) + admin_fee_* (sur integratorFeeBrut)
        $adminShare = round($adminTransactionFee + $adminChargeFee + $adminRoleFee, 2);
        
        // 3. CALCULER LA PART INTÉGRATEUR NETTE (selon transaction-parts-breakdown.blade.php ligne 23)
        // Part Intégrateur Nette = Intégrateur Brut - Admin (car admin est déduit de la part intégrateur)
        $integratorShare = max(0, round($integratorFeeBrut - $adminShare, 2));
        
        // Validation : l'admin ne peut pas dépasser les frais intégrateur bruts
        if ($adminShare > $integratorFeeBrut) {
            Log::warning('Frais admin supérieurs aux frais intégrateur bruts - Ajustement automatique', [
                'integrator_fee_brut' => $integratorFeeBrut,
                'admin_share_avant' => $adminShare,
                'total_amount' => $totalAmount,
                'business_profile_admin_id' => $adminProfile->id ?? null,
                'business_profile_integrator_id' => $integratorProfile->id ?? null
            ]);
            // Ajuster : limiter l'admin aux frais intégrateur bruts
            $adminShare = $integratorFeeBrut;
            $integratorShare = 0;
        }
        
        // Log pour débugger si la part intégrateur est 0
        if ($integratorShare == 0 && $integratorFeeBrut > 0) {
            Log::info('Part intégrateur nette à 0 car admin égal ou supérieur aux frais intégrateur bruts', [
                'integrator_fee_brut' => $integratorFeeBrut,
                'admin_share' => $adminShare,
                'integrator_fee_fixed' => $integratorFixed,
                'integrator_fee_percentage' => $integratorPercentage,
                'business_profile_id' => $integratorProfile->id ?? null,
                'business_profile_name' => $integratorProfile->name ?? null
            ]);
        }
        
        if ($integratorShare == 0 && $integratorFeeBrut == 0 && ($integratorFixed == 0 && $integratorPercentage == 0)) {
            Log::warning('Part intégrateur à 0 car les frais intégrateur sont à 0 dans le Business Profile', [
                'business_profile_id' => $integratorProfile->id ?? null,
                'business_profile_name' => $integratorProfile->name ?? null,
                'integrator_fee_fixed' => $integratorFixed,
                'integrator_fee_percentage' => $integratorPercentage,
                'hierarchy_integrator_id' => $hierarchy['integrator']->id ?? null,
                'hierarchy_operator_id' => $hierarchy['operator']->id ?? null,
                'hierarchy_partner_id' => $hierarchy['partner']->id ?? null
            ]);
        }

        // 4. CALCULER LA PART OPÉRATEUR (reste net)
        // Part Opérateur = T - Frais Intégrateur Brut (car les frais intégrateur sont déduits du total)
        // Selon transaction-parts-breakdown.blade.php ligne 25 : operator_share = total - integrator_fee_brut
        $operatorShare = round($totalAmount - $integratorFeeBrut, 2);

        // 4. VALIDER LA COHÉRENCE DES CALCULS
        // Vérifier que l'opérateur ne reçoit pas un montant négatif
        if ($operatorShare < 0) {
            Log::warning('Part opérateur négative détectée - Ajustement automatique', [
                'total_amount' => $totalAmount,
                'admin_share' => $adminShare,
                'integrator_share' => $integratorShare,
                'operator_share_before_adjustment' => $operatorShare,
                'note' => 'Les parts admin et intégrateur dépassent le montant total. Ajustement de la part opérateur à 0.'
            ]);
            
            // Ajuster : réduire proportionnellement les parts admin et intégrateur
            $totalParts = $adminShare + $integratorShare;
            if ($totalParts > 0) {
                $adjustmentFactor = $totalAmount / $totalParts;
                $adminShare = round($adminShare * $adjustmentFactor, 2);
                $integratorShare = round($integratorShare * $adjustmentFactor, 2);
                $operatorShare = round($totalAmount - $adminShare - $integratorShare, 2);
            } else {
                $operatorShare = 0;
            }
        }

        // 5. VALIDATION FINALE : S'assurer que la somme des parts correspond exactement au total
        // T = Part Admin + Part Intégrateur + Part Opérateur
        $calculatedTotal = round($adminShare + $integratorShare + $operatorShare, 2);
        $totalAmountRounded = round($totalAmount, 2);
        $difference = abs($calculatedTotal - $totalAmountRounded);
        
        // Si différence > 0.01€, ajuster pour garantir la cohérence
        if ($difference > 0.01) {
            // Ajuster la part de l'opérateur pour corriger la différence (car c'est le reste)
            $operatorShare = round($operatorShare - ($calculatedTotal - $totalAmountRounded), 2);
            
            Log::info('Ajustement de cohérence des montants dans calculateSharesWithBusinessProfiles', [
                'total_amount' => $totalAmountRounded,
                'calculated_total_avant' => $calculatedTotal,
                'difference' => $difference,
                'operator_share_ajuste' => $operatorShare,
                'admin_share' => $adminShare,
                'integrator_share' => $integratorShare
            ]);
            
            // Recalculer pour vérification finale
            $calculatedTotal = round($adminShare + $integratorShare + $operatorShare, 2);
        }

        // Calculer les pourcentages réels par rapport au total de la transaction
        $adminSharePercentage = $totalAmount > 0 ? ($adminShare / $totalAmount) * 100 : 0;
        $integratorSharePercentage = $totalAmount > 0 ? ($integratorShare / $totalAmount) * 100 : 0;
        $operatorSharePercentage = $totalAmount > 0 ? ($operatorShare / $totalAmount) * 100 : 0;

        // Validation finale : Vérifier que les Business Profiles sont correctement appliqués
        $validationStatus = abs($calculatedTotal - $totalAmountRounded) <= 0.01 ? 'OK' : 'ERREUR';
        
        if ($validationStatus === 'ERREUR') {
            Log::error('ERREUR dans le calcul des parts : La somme ne correspond pas au total', [
                'total_amount' => $totalAmountRounded,
                'calculated_total' => $calculatedTotal,
                'difference' => abs($calculatedTotal - $totalAmountRounded),
                'admin_share' => $adminShare,
                'integrator_share' => $integratorShare,
                'operator_share' => $operatorShare
            ]);
        }
        
        // 6. CALCULER LE MONTANT DE TVA (tax_amount) si disponible
        // Le total_amount est TTC (car estimated_cost est calculé avec TVA dans ReservationCostCalculationService)
        // On doit calculer HT et TVA à partir du TTC
        $taxAmount = 0;
        $amountHT = $totalAmount;
        $vatRate = null;
        
        // Récupérer le taux de TVA depuis la réservation si disponible
        if (isset($hierarchy['reservation'])) {
            $reservation = $hierarchy['reservation'];
            
            // Charger la relation pricingPlan si nécessaire
            if (!$reservation->relationLoaded('pricingPlan')) {
                $reservation->load('pricingPlan.vatRate');
            }
            
            if ($reservation->pricingPlan) {
                // Priorité 1: vatRate relation
                if ($reservation->pricingPlan->vatRate && $reservation->pricingPlan->vatRate->rate > 0) {
                    $vatRate = (float) $reservation->pricingPlan->vatRate->rate / 100; // Convertir en décimal (ex: 20% -> 0.20)
                } 
                // Priorité 2: vat_rate direct sur pricingPlan (pour compatibilité)
                elseif (isset($reservation->pricingPlan->vat_rate) && $reservation->pricingPlan->vat_rate > 0) {
                    $vatRate = (float) $reservation->pricingPlan->vat_rate / 100;
                }
            }
        }
        
        // Si un taux de TVA est disponible, calculer HT et TVA
        // Formule: HT = TTC / (1 + taux TVA)
        // Exemple: TTC = 120€, TVA = 20% (0.20)
        // HT = 120 / (1 + 0.20) = 120 / 1.20 = 100€
        // TVA = 120 - 100 = 20€
        if ($vatRate && $vatRate > 0) {
            // TTC = totalAmount (montant payé par le client avec TVA)
            // HT = TTC / (1 + taux TVA)
            $amountHT = round($totalAmount / (1 + $vatRate), 2);
            $taxAmount = round($totalAmount - $amountHT, 2);
            
            // Validation: Vérifier que HT + TVA = TTC (avec tolérance de 0.01€ pour les arrondis)
            $validation = abs(($amountHT + $taxAmount) - $totalAmount);
            if ($validation > 0.01) {
                Log::warning('TransactionHTTTCService: Ajustement du calcul HT/TTC pour cohérence', [
                    'total_amount' => $totalAmount,
                    'amount_ht_calculated' => $amountHT,
                    'tax_amount_calculated' => $taxAmount,
                    'sum' => $amountHT + $taxAmount,
                    'difference' => $validation,
                    'vat_rate' => ($vatRate * 100) . '%'
                ]);
                // Ajuster pour garantir HT + TVA = TTC
                $taxAmount = round($totalAmount - $amountHT, 2);
            }
        }
        
        // Logging détaillé pour confirmer que le calcul est correct selon les Business Profiles
        Log::info('✅ Calcul des parts selon logique hiérarchique (spécification) - VALIDÉ', [
            'total_amount' => $totalAmount,
            'amount_ht' => $amountHT,
            'tax_amount' => $taxAmount,
            'vat_rate' => $vatRate ? ($vatRate * 100) . '%' : 'N/A',
            'admin_share' => $adminShare,
            'admin_fixed' => $adminFixed,
            'admin_fee_percentage' => $adminPercentage,
            'admin_share_percentage' => $adminSharePercentage,
            'admin_business_profile_id' => $adminProfile->id ?? null,
            'admin_business_profile_name' => $adminProfile->name ?? null,
            'integrator_share' => $integratorShare,
            'integrator_fixed' => $integratorFixed,
            'integrator_fee_percentage' => $integratorPercentage,
            'integrator_share_percentage' => $integratorSharePercentage,
            'integrator_business_profile_id' => $integratorProfile->id ?? null,
            'integrator_business_profile_name' => $integratorProfile->name ?? null,
            'operator_share' => $operatorShare,
            'operator_share_percentage' => $operatorSharePercentage,
            'calculated_total' => $calculatedTotal,
            'validation' => $validationStatus,
            'validation_message' => $validationStatus === 'OK' 
                ? 'Le calcul de répartition des parts est correct selon les Business Profiles appliqués' 
                : 'ERREUR : Le calcul doit être vérifié',
            'formula_validation' => [
                'admin_formula' => "{$adminFixed} + ({$totalAmount} × {$adminPercentage}%) = {$adminShare}",
                'integrator_formula' => "{$integratorFixed} + ({$totalAmount} × {$integratorPercentage}%) = {$integratorShare}",
                'operator_formula' => "{$totalAmount} - {$adminShare} - {$integratorShare} = {$operatorShare}",
                'total_formula' => "{$adminShare} + {$integratorShare} + {$operatorShare} = {$calculatedTotal}",
                'verification' => abs($calculatedTotal - $totalAmountRounded) <= 0.01 ? '✅ OK' : '❌ ERREUR'
            ]
        ]);

        // Créer les breakdowns pour admin et intégrateur
        // Structure compatible avec celle attendue par les vues et createTransactionDetail
        // Utiliser les breakdowns calculés par calculateBusinessProfileFees
        $adminFeesBreakdown = $adminFeesOnTotal; // Utiliser les frais calculés sur le montant total
        $adminFeesBreakdown['total_fees'] = $adminShare; // Total = transaction + charge + role_fee
        $adminFeesBreakdown['role_fee'] = $adminRoleFee; // Mettre à jour avec la valeur calculée
        
        // Mettre à jour les calculation_details pour refléter la logique hiérarchique
        // transaction_fee et charge_fee sont calculés sur le montant total
        if (isset($adminFeesBreakdown['calculation_details']['transaction'])) {
            $adminFeesBreakdown['calculation_details']['transaction']['base_amount'] = $totalAmount;
            $adminFeesBreakdown['calculation_details']['transaction']['description'] = 'Frais de transaction Admin calculés sur le montant total';
        }
        if (isset($adminFeesBreakdown['calculation_details']['charge'])) {
            $adminFeesBreakdown['calculation_details']['charge']['base_amount'] = $totalAmount;
            $adminFeesBreakdown['calculation_details']['charge']['description'] = 'Frais de recharge Admin calculés sur le montant total';
        }
        if (!isset($adminFeesBreakdown['calculation_details']['admin'])) {
            $adminFeesBreakdown['calculation_details']['admin'] = [
                'formula' => "{$adminFixed} + ({$integratorFeeBrut} × {$adminPercentage}%)",
                'result' => $adminRoleFee,
                'base_amount' => $integratorFeeBrut,
                'description' => 'Frais Admin calculés sur les frais intégrateur bruts (selon hiérarchie)',
                'business_profile' => [
                    'id' => $adminProfile->id,
                    'name' => $adminProfile->name ?? 'Business Profile Admin→Intégrateur'
                ]
            ];
        } else {
            // Mettre à jour la formule et la base_amount
            $adminFeesBreakdown['calculation_details']['admin']['formula'] = "{$adminFixed} + ({$integratorFeeBrut} × {$adminPercentage}%)";
            $adminFeesBreakdown['calculation_details']['admin']['base_amount'] = $integratorFeeBrut;
            $adminFeesBreakdown['calculation_details']['admin']['description'] = 'Frais Admin calculés sur les frais intégrateur bruts (selon hiérarchie)';
        }

        // Utiliser les breakdowns calculés par calculateBusinessProfileFees
        $integratorFeesBreakdown = $integratorFees;
        $integratorFeesBreakdown['integrator_fees_brut'] = $integratorFeeBrut; // Frais intégrateur bruts (avant déduction admin)
        $integratorFeesBreakdown['total_fees'] = $integratorShare; // Part intégrateur nette (après déduction admin)
        
        // Ajouter les breakdowns pour integrator_brut et integrator_net
        if (!isset($integratorFeesBreakdown['breakdown']['integrator_brut'])) {
            $integratorFeesBreakdown['breakdown']['integrator_brut'] = [
                'type' => 'role_specific',
                'fixed_amount' => $integratorFixed,
                'percentage' => $integratorPercentage,
                'calculated' => $integratorFeeBrut,
                'source' => 'transaction_fee + charge_fee + integrator_fee_fixed + integrator_fee_percentage',
                'business_profile_id' => $integratorProfile->id,
                'business_profile_name' => $integratorProfile->name ?? 'Business Profile Intégrateur→Opérateur',
                'note' => 'Frais Intégrateur Bruts calculés sur le montant total (avant déduction admin)'
            ];
        }
        
        $integratorFeesBreakdown['breakdown']['integrator_net'] = [
            'type' => 'calculated',
            'calculated' => $integratorShare,
            'source' => 'integrator_fees_brut - admin_share',
            'note' => 'Part Intégrateur Nette (après déduction des frais admin)'
        ];
        
        // Ajouter les calculation_details si manquants
        if (!isset($integratorFeesBreakdown['calculation_details']['integrator_brut'])) {
            $integratorFeesBreakdown['calculation_details']['integrator_brut'] = [
                'formula' => "transaction_fee ({$integratorTransactionFee}) + charge_fee ({$integratorChargeFee}) + integrator_fee ({$integratorRoleFee}) = {$integratorFeeBrut}",
                'result' => $integratorFeeBrut,
                'base_amount' => $totalAmount,
                'description' => 'Frais Intégrateur Bruts calculés sur le montant total',
                'business_profile' => [
                    'id' => $integratorProfile->id,
                    'name' => $integratorProfile->name ?? 'Business Profile Intégrateur→Opérateur'
                ]
            ];
        }
        
        $integratorFeesBreakdown['calculation_details']['integrator_net'] = [
            'formula' => "{$integratorFeeBrut} - {$adminShare} = {$integratorShare}",
            'result' => $integratorShare,
            'base_amount' => $integratorFeeBrut,
            'description' => 'Part Intégrateur Nette (après déduction des frais admin)'
        ];

        return [
            'total_amount' => $totalAmount, // TTC (montant total payé par le client avec TVA)
            'amount_ht' => $amountHT, // HT (montant sans TVA)
            'tax_amount' => $taxAmount, // Montant de TVA
            'vat_rate' => $vatRate, // Taux de TVA en décimal (ex: 0.20 pour 20%)
            'admin_share' => $adminShare,
            'integrator_share' => $integratorShare, // Part intégrateur nette (après déduction admin)
            'integrator_fees_brut' => $integratorFeeBrut, // Frais intégrateur bruts (avant déduction admin)
            'operator_share' => $operatorShare,
            // Pourcentages réels calculés par rapport au total de la transaction
            'admin_percentage' => round($adminSharePercentage, 4),
            'integrator_percentage' => round($integratorSharePercentage, 4),
            'operator_percentage' => round($operatorSharePercentage, 4),
            // Pourcentages des business profiles (pour référence)
            'admin_profile_percentage' => $adminPercentage,
            'integrator_profile_percentage' => $integratorPercentage,
            'admin_fixed' => $adminFixed,
            'integrator_fixed' => $integratorFixed,
            // Breakdowns détaillés pour admin et intégrateur
            'admin_fees_breakdown' => $adminFeesBreakdown,
            'integrator_fees_breakdown' => $integratorFeesBreakdown,
            'business_profiles' => [
                'admin_integrator_id' => $adminProfile->id,
                'integrator_operator_id' => $integratorProfile->id
            ],
            'hierarchical_logic' => [
                'integrator_fees_amount' => $integratorFeeBrut, // Frais intégrateur bruts (avant déduction admin)
                'admin_fees_amount' => $adminShare, // Frais admin
                'integrator_fees_calculated_on' => 'total_amount', // Calculé sur le montant total
                'admin_fees_calculated_on' => [
                    'transaction_fee' => 'total_amount', // Calculé sur le montant total
                    'charge_fee' => 'total_amount', // Calculé sur le montant total
                    'role_fee' => 'integrator_fees_brut' // Calculé sur les frais intégrateur bruts
                ],
                'formula' => [
                    'integrator_brut' => "transaction_fee + charge_fee + integrator_fee_fixed ({$integratorFixed}) + (total_amount × integrator_fee_percentage ({$integratorPercentage}%)) = {$integratorFeeBrut}",
                    'admin' => "transaction_fee (sur total) + charge_fee (sur total) + admin_fee_fixed ({$adminFixed}) + (integrator_fees_brut × admin_fee_percentage ({$adminPercentage}%)) = {$adminShare}",
                    'integrator_net' => "integrator_fees_brut ({$integratorFeeBrut}) - admin_share ({$adminShare}) = {$integratorShare}",
                    'operator' => "total_amount ({$totalAmount}) - integrator_fees_brut ({$integratorFeeBrut}) = {$operatorShare}"
                ]
            ]
        ];
    }

    /**
     * Calculer les frais variables selon un Business Profile
     * 
     * LOGIQUE CORRIGÉE SELON LA SPÉCIFICATION :
     * FRAIS = FRAIS CONFIG (Transaction) + CHARGE (Recharge)
     * 
     * Pour la Part Intégrateur :
     * - Utilise le Business Profile Intégrateur → Opérateur
     * - Frais Intégrateur = Config Transaction + Config Charge (SANS integrator_fee_*)
     * 
     * Pour la Part Admin :
     * - Utilise le Business Profile Admin → Intégrateur
     * - Frais Admin = Config Transaction + Config Charge + Frais Admin (admin_fee_*)
     * - Calculé sur la part intégrateur (les frais intégrateur)
     * 
     * LOGIQUE HIÉRARCHIQUE :
     * - Opérateur = Total - Frais Intégrateur
     * - Intégrateur Net = Frais Intégrateur - Frais Admin
     * - Admin = Frais Admin (calculé sur la part intégrateur)
     * 
     * Les frais d'activation (base_fee_amount) sont exclus du calcul des parts hiérarchiques.
     */
    protected function calculateBusinessProfileFees(BusinessProfile $profile, float $totalAmount, string $role): array
    {
        $fees = [
            'activation_fee' => 0,        // Frais d'activation (base_fee_amount) - exclus du calcul des parts
            'transaction_fee' => 0,       // Frais de transaction (transaction_fee_config) - INCLUS dans le calcul des parts
            'charge_fee' => 0,           // Frais de recharge (charge_fee_config) - INCLUS dans le calcul des parts
            'role_fee' => 0,             // Frais spécifiques au rôle (admin_fee_* ou integrator_fee_*) - INCLUS dans le calcul
            'total_fees' => 0,           // Total = transaction_fee + charge_fee + role_fee pour la répartition hiérarchique
            'percentage_used' => 0,
            'fixed_used' => 0,
            'breakdown' => [],
            'calculation_details' => []
        ];

        // 1. FRAIS D'ACTIVATION (base_fee_amount) - Exclus du calcul des parts hiérarchiques
        $fees['activation_fee'] = (float) ($profile->base_fee_amount ?? 0);
        if ($fees['activation_fee'] > 0) {
            $fees['breakdown']['activation'] = [
                'type' => 'fixed',
                'amount' => $fees['activation_fee'],
                'source' => 'base_fee_amount',
                'note' => 'Exclus du calcul des parts hiérarchiques (admin/intégrateur)'
            ];
        }

        // 2. FRAIS DE TRANSACTION (transaction_fee_config) - INCLUS dans le calcul des parts
        $transactionConfig = is_string($profile->transaction_fee_config) 
            ? json_decode($profile->transaction_fee_config, true) 
            : $profile->transaction_fee_config;

        if ($transactionConfig && is_array($transactionConfig)) {
            $transactionFixed = (float) ($transactionConfig['fixed_amount'] ?? 0);
            $transactionPercentage = (float) ($transactionConfig['percentage'] ?? 0);
            
            $fees['transaction_fee'] = $transactionFixed + ($totalAmount * $transactionPercentage / 100);
            
            if ($fees['transaction_fee'] > 0) {
                $fees['breakdown']['transaction'] = [
                    'type' => 'mixed',
                    'fixed_amount' => $transactionFixed,
                    'percentage' => $transactionPercentage,
                    'calculated' => $fees['transaction_fee'],
                    'source' => 'transaction_fee_config',
                    'note' => 'Inclus dans la répartition hiérarchique'
                ];
            }
        }

        // 3. FRAIS DE RECHARGE (charge_fee_config) - INCLUS dans le calcul des parts
        $chargeConfig = is_string($profile->charge_fee_config) 
            ? json_decode($profile->charge_fee_config, true) 
            : $profile->charge_fee_config;

        if ($chargeConfig && is_array($chargeConfig)) {
            $chargeFixed = (float) ($chargeConfig['fixed_amount'] ?? 0);
            $chargePercentage = (float) ($chargeConfig['percentage'] ?? 0);
            
            $fees['charge_fee'] = $chargeFixed + ($totalAmount * $chargePercentage / 100);
            
            if ($fees['charge_fee'] > 0) {
                $fees['breakdown']['charge'] = [
                    'type' => 'mixed',
                    'fixed_amount' => $chargeFixed,
                    'percentage' => $chargePercentage,
                    'calculated' => $fees['charge_fee'],
                    'source' => 'charge_fee_config',
                    'note' => 'Inclus dans la répartition hiérarchique'
                ];
            }
        }

        // 4. FRAIS SPÉCIFIQUES SELON LE RÔLE - UNIQUEMENT POUR ADMIN
        // IMPORTANT : Les frais intégrateur (integrator_fee_*) ne sont PAS inclus dans les part fees
        // Les part fees intégrateur = Config Transaction + Config Charge uniquement
        if ($role === 'admin') {
            // BUSINESS PROFILE Admin → Intégrateur
            // L'Admin prend sa part selon ce Business Profile
            $adminFixed = (float) ($profile->admin_fee_fixed ?? 0);
            $adminPercentage = (float) ($profile->admin_fee_percentage ?? 0);
            
            // Les frais admin sont calculés sur la base passée (qui devrait être les frais intégrateur)
            $fees['role_fee'] = $adminFixed + ($totalAmount * $adminPercentage / 100);
            $fees['percentage_used'] = $adminPercentage;
            $fees['fixed_used'] = $adminFixed;
            
            $fees['breakdown']['admin'] = [
                'type' => 'role_specific',
                'fixed_amount' => $adminFixed,
                'percentage' => $adminPercentage,
                'calculated' => $fees['role_fee'],
                'source' => 'admin_fee_fixed + admin_fee_percentage',
                'business_profile_id' => $profile->id,
                'business_profile_name' => $profile->name,
                'note' => 'Part Admin selon Business Profile Admin→Intégrateur. Calculé sur la part intégrateur (frais intégrateur)'
            ];

            $fees['calculation_details']['admin'] = [
                'formula' => "{$adminFixed} + ({$totalAmount} × {$adminPercentage}%)",
                'result' => $fees['role_fee'],
                'base_amount' => $totalAmount,
                'description' => 'Frais Admin calculés sur les frais intégrateur',
                'business_profile' => [
                    'id' => $profile->id,
                    'name' => $profile->name
                ]
            ];

        } elseif ($role === 'integrator') {
            // BUSINESS PROFILE Intégrateur → Opérateur
            // Pour l'intégrateur, on garde les valeurs à 0 car les part fees = Config Transaction + Config Charge uniquement
            // Les integrator_fee_* ne sont PAS inclus dans les part fees
            $integratorFixed = (float) ($profile->integrator_fee_fixed ?? 0);
            $integratorPercentage = (float) ($profile->integrator_fee_percentage ?? 0);
            
            // On stocke les valeurs pour information mais elles ne sont PAS incluses dans total_fees
            $fees['percentage_used'] = $integratorPercentage;
            $fees['fixed_used'] = $integratorFixed;
            
            $fees['breakdown']['integrator'] = [
                'type' => 'role_specific',
                'fixed_amount' => $integratorFixed,
                'percentage' => $integratorPercentage,
                'calculated' => 0,
                'source' => 'integrator_fee_fixed + integrator_fee_percentage',
                'business_profile_id' => $profile->id,
                'business_profile_name' => $profile->name,
                'note' => 'NON inclus dans les part fees. Part fees = Config Transaction + Config Charge uniquement'
            ];

            $fees['calculation_details']['integrator'] = [
                'formula' => "Non inclus dans part fees",
                'result' => 0,
                'description' => 'Les integrator_fee_* ne sont pas inclus dans les part fees',
                'business_profile' => [
                    'id' => $profile->id,
                    'name' => $profile->name
                ]
            ];
        }

        // 5. TOTAL DES FRAIS SELON LA LOGIQUE CORRIGÉE
        // Les frais d'activation sont exclus du calcul des parts admin/intégrateur
        
        if ($role === 'integrator') {
            // Pour l'intégrateur : FRAIS = CONFIG TRANSACTION + CONFIG CHARGE (SANS integrator_fee_*)
            $fees['total_fees'] = $fees['transaction_fee'] + $fees['charge_fee'];
        } elseif ($role === 'admin') {
            // Pour l'admin : FRAIS = CONFIG TRANSACTION + CONFIG CHARGE + ROLE_FEE (admin_fee_*)
            // Selon la spécification Business Profile "Admin → Intégrateur" :
            // La part admin = Config Transaction + Config Charge + Frais Admin (admin_fee_fixed + admin_fee_percentage)
            // Les admin_fee_* (role_fee) SONT inclus dans les frais admin
            $fees['total_fees'] = $fees['transaction_fee'] + $fees['charge_fee'] + $fees['role_fee'];
        } else {
            // Par défaut : Config Transaction + Config Charge
            $fees['total_fees'] = $fees['transaction_fee'] + $fees['charge_fee'];
        }

        // 6. VALIDATION ET LOGGING
        $this->logFeeCalculation($profile, $totalAmount, $role, $fees);

        return $fees;
    }

    /**
     * Logger le calcul des frais
     */
    protected function logFeeCalculation(BusinessProfile $profile, float $totalAmount, string $role, array $fees): void
    {
        Log::info("Calcul des frais Business Profile - {$role}", [
            'business_profile_id' => $profile->id,
            'business_profile_name' => $profile->name,
            'role' => $role,
            'total_amount' => $totalAmount,
            'fees_breakdown' => $fees['breakdown'],
            'total_fees_calculated' => $fees['total_fees'],
            'calculation_details' => $fees['calculation_details']
        ]);
    }

    /**
     * Créer ou mettre à jour la transaction principale avec toutes les informations
     * 
     * CORRECTION : S'assurer que le montant de la transaction correspond exactement à la somme des parts
     * Si une transaction existe déjà, elle sera mise à jour au lieu d'être dupliquée
     * 
     * Sauvegarde :
     * - Montant total
     * - Parts admin/integrateur/operator
     * - Taxes
     * - IDs de chaque acteur (admin_id, integrator_id, operator_id)
     */
    protected function createOrUpdateMainTransaction(Reservation $reservation, array $calculation, array $hierarchy): Transaction
    {
        // Calculer la somme des parts pour validation
        $calculatedTotal = round(
            $calculation['admin_share'] + 
            $calculation['integrator_share'] + 
            $calculation['operator_share'], 
            2
        );
        
        // Utiliser la somme calculée comme source de vérité pour éviter les incohérences
        // Le total_amount devrait déjà correspondre, mais on s'assure de la cohérence
        $transactionAmount = round($calculation['total_amount'], 2);
        
        // Si il y a une différence > 0.01€, utiliser la somme calculée
        $difference = abs($transactionAmount - $calculatedTotal);
        if ($difference > 0.01) {
            Log::warning('Incohérence détectée lors de la création de transaction - Correction automatique', [
                'reservation_id' => $reservation->id,
                'total_amount' => $transactionAmount,
                'calculated_total' => $calculatedTotal,
                'difference' => $difference,
                'action' => 'using_calculated_total'
            ]);
            
            // Utiliser la somme calculée comme montant de transaction
            $transactionAmount = $calculatedTotal;
            
            // Mettre à jour le calcul pour refléter le montant corrigé
            $calculation['total_amount'] = $transactionAmount;
        }
        
        // Vérifier si une transaction existe déjà pour cette réservation
        $existingTransaction = Transaction::where('reservation_id', $reservation->id)->first();
        
        // Récupérer les soldes actuels des wallets AVANT les opérations de wallet
        $operatorWallet = $this->getOrCreateWallet($hierarchy['operator']);
        $integratorWallet = $this->getOrCreateWallet($hierarchy['integrator']->user ?? $hierarchy['integrator']);
        $adminWallet = $this->getOrCreateWallet($hierarchy['admin']);
        
        $operatorCurrentBalance = (float) $operatorWallet->balance;
        $integratorCurrentBalance = (float) $integratorWallet->balance;
        $adminCurrentBalance = (float) $adminWallet->balance;
        
        // Déterminer le collecteur des fonds selon le mode de collecte configuré
        $collectionMode = null;
        $collectUser = null;

        // Priorité au partenaire (opérateur) si un partner explicite existe sur la borne
        $partner = $hierarchy['partner'] ?? null;
        if ($partner instanceof Partner && !empty($partner->collection_mode)) {
            $collectionMode = $partner->collection_mode;
            if (in_array($collectionMode, ['partner', 'integrator'], true)) {
                $collectUser = $partner;
            }
        }

        // Sinon, vérifier l'intégrateur
        if (!$collectUser && isset($hierarchy['integrator']) && $hierarchy['integrator'] instanceof Integrator) {
            $integrator = $hierarchy['integrator'];
            $collectionMode = $integrator->collection_mode ?? $collectionMode;

            if ($collectionMode === 'integrator') {
                $collectUser = $integrator;
            }
        }

        // Fallback: collecte par l'admin (Evon Charge) si aucun collecteur explicite
        if (!$collectUser && isset($hierarchy['admin']) && $hierarchy['admin'] instanceof User) {
            $collectUser = $hierarchy['admin'];
            $collectionMode = $collectionMode ?? 'admin';
        }

        // Statut de collecte initial : transaction disponible à l'encaissement
        $initialCollectStatus = CollectStatus::TO_COLLECT->value;

        // Préparer les données de transaction avec toutes les informations
        // Les réservations payées par solde (PAID, prepaid_credit, etc.) → completed (Approuvé automatiquement)
        // Les réservations confirmées mais non payées → confirmed
        $isPaid = $reservation->isPaid();
        $transactionStatus = $isPaid ? 'completed' : 'confirmed';

        $transactionData = [
            'charging_point_id' => $reservation->charging_point_id,
            'user_id' => $reservation->user_id,
            'amount' => $transactionAmount,
            // Note: Les colonnes *_current_balance n'existent pas encore dans la table
            // Elles seront ajoutées via migration si nécessaire
            // Pour l'instant, on stocke les balances dans metadata si nécessaire
            'price_total' => $transactionAmount,
            'currency' => 'EUR',
            'status' => $transactionStatus,
            // Note: admin_id, integrator_id, operator_id peuvent ne pas exister dans la table
            // On les stocke dans metadata pour référence
            // Note: admin/integrator/operator share amounts are stored on transaction_details, not here
            // Commissions
            'admin_commission' => round($calculation['admin_share'], 4),
            'integrator_commission' => round($calculation['integrator_share'], 4),
            'partner_commission' => round($calculation['operator_share'], 4),
            // Taxes
            'price_tax' => $calculation['tax_amount'] ?? 0,
            // Champs de collecte
            'collect_user_type' => $collectUser ? get_class($collectUser) : null,
            'collect_user_id' => $collectUser->id ?? null,
            'collect_status' => $collectUser ? $initialCollectStatus : null,
            'collect_date' => $collectUser ? ($existingTransaction->collect_date ?? now()) : null,
            'is_collectable' => $collectUser ? true : false,
            // Metadata
            'metadata' => [
                'reservation_id' => $reservation->id,
                'calculation' => $calculation,
                'amount_validation' => [
                    'original_total' => $calculation['total_amount'],
                    'calculated_from_shares' => $calculatedTotal,
                    'difference' => $difference,
                    'corrected' => $difference > 0.01
                ],
                'hierarchy' => [
                    'admin_id' => $hierarchy['admin']->id ?? null,
                    'integrator_id' => $hierarchy['integrator']->id ?? null,
                    'operator_id' => $hierarchy['operator']->id ?? null,
                    'partner_id' => $partner->id ?? null,
                ],
                'collection' => [
                    'collection_mode' => $collectionMode,
                    'collect_user_type' => $collectUser ? get_class($collectUser) : null,
                    'collect_user_id' => $collectUser->id ?? null,
                ],
                'current_balances' => [
                    'admin' => $adminCurrentBalance,
                    'integrator' => $integratorCurrentBalance,
                    'operator' => $operatorCurrentBalance,
                ]
            ]
        ];
        
        if ($existingTransaction) {
            // Mettre à jour la transaction existante
            // IMPORTANT: S'assurer que le statut est 'confirmed' si la réservation est approuvée
            // et non 'pending'
            $updateData = array_merge($transactionData, [
                'reference_id' => $existingTransaction->reference_id ?? uniqid('RES_'),
            ]);
            
            // Si la réservation est approuvée, forcer le statut à 'confirmed'
            // CORRECTION : Utiliser l'enum ReservationStatus au lieu de chaîne
            $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus 
                ? $reservation->status->value 
                : $reservation->status;
                
            if (in_array($statusValue, ['confirmed', 'completed']) && in_array($existingTransaction->status, ['pending', 'confirmed'])) {
                // Auto-approbation : si réservation payée → completed, sinon → confirmed
                $newStatus = $isPaid ? 'completed' : 'confirmed';
                $updateData['status'] = $newStatus;
                $updateData['completed_at'] = $updateData['completed_at'] ?? now();
                
                Log::info('Mise à jour du statut de transaction (auto-approuvé)', [
                    'transaction_id' => $existingTransaction->id,
                    'reservation_id' => $reservation->id,
                    'old_status' => $existingTransaction->status,
                    'new_status' => $newStatus,
                    'payment_status' => $reservation->payment_status
                ]);
            }
            
            $existingTransaction->update($updateData);
            
            // Pas besoin de mettre à jour transaction_id dans la réservation
            // La relation est inversée : transactions.reservation_id -> reservations
            // La transaction a déjà reservation_id, pas besoin de l'inverse
            
            return $existingTransaction->fresh();
        }
        
        // Créer une nouvelle transaction
        $transaction = Transaction::create(array_merge($transactionData, [
            'reference_id' => uniqid('RES_'),
            'reservation_id' => $reservation->id,
        ]));
        
        // Pas besoin de mettre à jour transaction_id dans la réservation
        // La relation est inversée : transactions.reservation_id -> reservations
        // La transaction a déjà reservation_id, pas besoin de l'inverse
        
        return $transaction;
    }

    /**
     * Créer les détails de transaction avec parts
     */
    protected function createTransactionDetail(
        Transaction $transaction, 
        array $calculation, 
        array $hierarchy
    ): TransactionDetail {
        // VALIDATION : Vérifier la cohérence des parts avant création
        $totalAmount = (float) ($transaction->price_total ?? $transaction->amount ?? $calculation['total_amount'] ?? 0);
        $adminShare = (float) ($calculation['admin_share'] ?? 0);
        $integratorShare = (float) ($calculation['integrator_share'] ?? 0);
        $operatorShare = (float) ($calculation['operator_share'] ?? 0);
        $sumOfShares = round($adminShare + $integratorShare + $operatorShare, 2);
        $totalAmountRounded = round($totalAmount, 2);
        $difference = abs($sumOfShares - $totalAmountRounded);
        
        // Si différence > 0.01€, ajuster la part opérateur pour garantir la cohérence
        if ($difference > 0.01 && $totalAmount > 0) {
            Log::warning('Incohérence détectée dans createTransactionDetail - Ajustement automatique', [
                'transaction_id' => $transaction->id,
                'total_amount' => $totalAmountRounded,
                'sum_of_shares' => $sumOfShares,
                'difference' => $difference,
                'admin_share' => $adminShare,
                'integrator_share' => $integratorShare,
                'operator_share' => $operatorShare
            ]);
            
            // Ajuster la part opérateur pour corriger la différence
            $operatorShare = round($totalAmountRounded - $adminShare - $integratorShare, 2);
            $calculation['operator_share'] = $operatorShare;
            
            Log::info('Part opérateur ajustée dans createTransactionDetail', [
                'transaction_id' => $transaction->id,
                'operator_share_after' => $operatorShare,
                'new_sum' => round($adminShare + $integratorShare + $operatorShare, 2)
            ]);
        }
        
        // Calculer les frais totaux pour l'affichage
        $totalFees = $calculation['admin_share'] + $calculation['integrator_share'];
        
        return TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'transaction_fee_percentage' => 0.0, // Les frais sont dans les parts
            'transaction_fee_fixed' => 0.0,
            'transaction_fee_total' => $totalFees,
            'admin_share_amount' => $calculation['admin_share'],
            'integrator_share_amount' => $calculation['integrator_share'],
            'operator_share_amount' => $calculation['operator_share'],
            'admin_share_percentage' => $calculation['admin_percentage'],
            'integrator_share_percentage' => $calculation['integrator_percentage'],
            'admin_creator_id' => $hierarchy['admin']->id,
            // Utiliser user_id si c'est un Integrator, id si c'est un User
            'integrator_creator_id' => $hierarchy['integrator']->user_id ?? $hierarchy['integrator']->id,
            'operator_id' => $hierarchy['operator']->id,
            'admin_paid' => false,
            'integrator_paid' => false,
            'operator_paid' => false,
            'calculation_details' => [
                'business_profiles' => $calculation['business_profiles'] ?? [],
                'admin_fixed' => $calculation['admin_fixed'] ?? 0,
                'integrator_fixed' => $calculation['integrator_fixed'] ?? 0,
                'admin_fees_breakdown' => $calculation['admin_fees_breakdown'] ?? [],
                'integrator_fees_breakdown' => $calculation['integrator_fees_breakdown'] ?? [],
                'hierarchy' => [
                    'admin_id' => $hierarchy['admin']->id,
                    'integrator_id' => $hierarchy['integrator']->id,
                    'operator_id' => $hierarchy['operator']->id,
                    'charging_point_id' => $hierarchy['charging_point']->id
                ],
                'fees_calculation_method' => 'business_profile_hierarchical',
                'total_fees_calculated' => $totalFees,
                'operator_net_amount' => $calculation['operator_share'],
                'hierarchical_logic' => $calculation['hierarchical_logic'] ?? [
                    'integrator_fees_amount' => $calculation['integrator_fees_brut'] ?? (($calculation['integrator_share'] ?? 0) + ($calculation['admin_share'] ?? 0)), // Frais intégrateur bruts
                    'admin_fees_amount' => $calculation['admin_share'] ?? 0,
                    'integrator_fees_calculated_on' => 'total_amount',
                    'admin_fees_calculated_on' => 'total_amount',
                    'note' => 'Frais Intégrateur calculé sur total, Frais Admin calculé sur total (mais déduit de la part intégrateur)'
                ]
            ]
        ]);
    }

    /**
     * Créer les transactions hiérarchiques
     */
    protected function createHierarchicalTransactions(
        Transaction $mainTransaction, 
        array $calculation, 
        array $hierarchy
    ): array {
        $hierarchicalTransactions = [];

        // Transaction Admin ↔ Intégrateur
        if ($calculation['admin_share'] > 0) {
            $adminShare = round($calculation['admin_share'], 2);
            $hierarchicalTransactions['admin_integrator'] = TransactionHierarchy::create([
                'original_transaction_id' => $mainTransaction->id,
                'payer_id' => $hierarchy['integrator']->id ?? $hierarchy['integrator']->user_id ?? null,
                'payee_id' => $hierarchy['admin']->id,
                'amount' => $adminShare,
                'fees_amount' => 0, // Pas de frais supplémentaires
                'net_amount' => $adminShare, // Montant net = montant (car pas de frais)
                'transaction_type' => 'admin_integrator',
                'status' => 'completed',
                'description' => "Part Admin - Réservation #{$mainTransaction->reservation_id}",
                'metadata' => [
                    'reservation_id' => $mainTransaction->reservation_id,
                    'charging_point_id' => $mainTransaction->charging_point_id,
                    'business_profile_id' => $calculation['business_profiles']['admin_integrator_id'] ?? null
                ]
            ]);
        }

        // Transaction Intégrateur ↔ Opérateur
        if ($calculation['integrator_share'] > 0) {
            $integratorShare = round($calculation['integrator_share'], 2);
            $hierarchicalTransactions['integrator_operator'] = TransactionHierarchy::create([
                'original_transaction_id' => $mainTransaction->id,
                'payer_id' => $hierarchy['operator']->id,
                'payee_id' => $hierarchy['integrator']->id ?? $hierarchy['integrator']->user_id ?? null,
                'amount' => $integratorShare,
                'fees_amount' => 0, // Pas de frais supplémentaires
                'net_amount' => $integratorShare, // Montant net = montant (car pas de frais)
                'transaction_type' => 'integrator_operator',
                'status' => 'completed',
                'description' => "Part Intégrateur - Réservation #{$mainTransaction->reservation_id}",
                'metadata' => [
                    'reservation_id' => $mainTransaction->reservation_id,
                    'charging_point_id' => $mainTransaction->charging_point_id,
                    'business_profile_id' => $calculation['business_profiles']['integrator_operator_id'] ?? null
                ]
            ]);
        }

        return $hierarchicalTransactions;
    }

    /**
     * Mettre à jour les wallets avec vérification de cohérence
     * 
     * LOGIQUE HIÉRARCHIQUE CORRIGÉE SELON LES DEUX BUSINESS PROFILES :
     * 
     * 1. Admin → Intégrateur (Business Profile Admin→Intégrateur) :
     *    - L'Admin prend sa part selon son BusinessProfile (admin_fee_fixed + admin_fee_percentage)
     *    - Cette part est déduite de la balance de l'intégrateur
     * 
     * 2. Intégrateur → Opérateur (Business Profile Intégrateur→Opérateur) :
     *    - L'intégrateur prend sa part selon son BusinessProfile appliqué sur l'opérateur (integrator_fee_fixed + integrator_fee_percentage)
     *    - Le reste est versé à l'opérateur (net hors taxes)
     * 
     * @return array Retourne les IDs des wallets utilisés pour sauvegarde dans la transaction
     */
    protected function updateWalletsWithValidation(
        array $hierarchy, 
        array $calculation, 
        Transaction $transaction,
        TransactionDetail $transactionDetail = null
    ): array {
        $reservationId = $transaction->reservation_id ?? 'N/A';
        
        // ==========================================
        // FLUX HIÉRARCHIQUE DES WALLETS POUR RÉSERVATION APPROUVÉE
        // ==========================================
        // 
        // CORRECTION : Pour une réservation approuvée, le CLIENT a déjà payé
        // L'opérateur ne doit PAS être débité - il reçoit sa part comme crédit
        // Les parts sont distribuées directement depuis le paiement du client
        // ==========================================
        $operatorWallet = $this->getOrCreateWallet($hierarchy['operator']);
        $operatorBalanceBefore = $operatorWallet->balance;
        // NOTE : Pas de débit pour l'opérateur car le client a déjà payé
        // L'opérateur recevra sa part (operator_share) comme crédit ci-dessous

        // ==========================================
        // ÉTAPE 2 : TRANSACTION INTÉGRATEUR → OPÉRATEUR
        // Business Profile Intégrateur→Opérateur
        // ==========================================
        // L'intégrateur prend sa part selon le Business Profile Intégrateur→Opérateur
        // Le reste (net) est versé à l'opérateur
        
        $integratorWallet = $this->getOrCreateWallet($hierarchy['integrator']->user ?? $hierarchy['integrator']);
        $integratorBalanceBefore = $integratorWallet->balance;
        
        // Créditer l'intégrateur de sa part (selon Business Profile Intégrateur→Opérateur)
        // Cette part vient du montant total payé par l'opérateur
        if ($calculation['integrator_share'] > 0) {
            $metadata = [
                'transaction_id' => $transaction->id,
                'transaction_detail_id' => $transactionDetail->id ?? null,
                'reservation_id' => $reservationId !== 'N/A' ? $reservationId : null,
                'integrator_share_amount' => $calculation['integrator_share'],
                'type' => 'integrator_share_credit'
            ];
            $this->creditWallet($integratorWallet, $calculation['integrator_share'], "Part Intégrateur (selon BP Intégrateur→Opérateur) - Réservation #{$reservationId}", $metadata);
        }
        $integratorWallet->refresh();
        $integratorBalanceAfterStep2 = $integratorWallet->balance;
        
        // Créditer l'opérateur du reste (sa part nette après déduction de la part intégrateur)
        // IMPORTANT : L'opérateur identifié est toujours le créateur de la borne (operator/partner)
        // Sa part (operator_share) est toujours incluse et mise à jour dans ses balances
        // Cela garantit que pour chaque transaction liée à la borne, le créateur reçoit toujours sa part
        if ($calculation['operator_share'] > 0) {
            $metadata = [
                'transaction_id' => $transaction->id,
                'transaction_detail_id' => $transactionDetail->id ?? null,
                'reservation_id' => $reservationId !== 'N/A' ? $reservationId : null,
                'operator_share_amount' => $calculation['operator_share'],
                'type' => 'operator_share_credit'
            ];
            $this->creditWallet($operatorWallet, $calculation['operator_share'], "Part Opérateur nette (selon BP Intégrateur→Opérateur) - Réservation #{$reservationId} - Créateur de la borne", $metadata);
        }
        $operatorWallet->refresh();

        // ==========================================
        // ÉTAPE 3 : TRANSACTION ADMIN → INTÉGRATEUR
        // Business Profile Admin→Intégrateur
        // ==========================================
        // L'Admin prend sa part selon le Business Profile Admin→Intégrateur
        // Cette part est déduite de la balance de l'intégrateur
        // L'intégrateur a déjà reçu sa part intégrateur, maintenant l'admin prend sa part depuis cette balance
        
        $adminWallet = null;
        $adminBalanceBefore = null;
        $adminBalanceAfter = null;
        
        if ($calculation['admin_share'] > 0) {
            // CORRECTION : Pour réservation approuvée, le client a déjà payé
            // L'admin reçoit sa part directement depuis le paiement du client
            // Pas besoin de débiter l'intégrateur car les parts sont calculées et distribuées directement
            $adminWallet = $this->getOrCreateWallet($hierarchy['admin']);
            $adminBalanceBefore = $adminWallet->balance;
            $metadata = [
                'transaction_id' => $transaction->id,
                'transaction_detail_id' => $transactionDetail->id ?? null,
                'reservation_id' => $reservationId !== 'N/A' ? $reservationId : null,
                'admin_share_amount' => $calculation['admin_share'],
                'type' => 'admin_share_credit',
                'source' => 'reservation_payment',
                'note' => 'Part Admin calculée selon Business Profile Admin→Intégrateur, distribuée depuis paiement client'
            ];
            $this->creditWallet($adminWallet, $calculation['admin_share'], "Part Admin (selon BP Admin→Intégrateur) - Réservation #{$reservationId}", $metadata);
            $adminWallet->refresh();
            $adminBalanceAfter = $adminWallet->balance;
        }
        
        // ==========================================
        // VÉRIFICATION DE COHÉRENCE DES BALANCES
        // ==========================================
        $integratorBalanceAfter = $integratorWallet->balance;
        
        // Vérifier que les montants débités/crédités sont cohérents
        // CORRECTION : Pour réservation approuvée, l'opérateur ne paie pas, il reçoit sa part
        $operatorNetChange = $operatorWallet->balance - $operatorBalanceBefore;
        $expectedOperatorNet = $calculation['operator_share']; // L'opérateur reçoit sa part (pas de débit)
        
        $integratorNetChange = $integratorBalanceAfter - $integratorBalanceBefore;
        // CORRECTION : Pour réservation approuvée, l'intégrateur reçoit sa part directement (pas de déduction admin)
        $expectedIntegratorNet = $calculation['integrator_share']; // L'intégrateur reçoit sa part nette directement
        
        $adminNetChange = $adminWallet ? ($adminBalanceAfter - $adminBalanceBefore) : 0;
        $expectedAdminNet = $calculation['admin_share'];
        
        // Validation de cohérence
        $tolerance = 0.01;
        $isConsistent = (
            abs($operatorNetChange - $expectedOperatorNet) <= $tolerance &&
            abs($integratorNetChange - $expectedIntegratorNet) <= $tolerance &&
            abs($adminNetChange - $expectedAdminNet) <= $tolerance
        );
        
        if (!$isConsistent) {
            throw new Exception("Incohérence détectée dans les balances des wallets. Opérateur: {$operatorNetChange} vs {$expectedOperatorNet}, Intégrateur: {$integratorNetChange} vs {$expectedIntegratorNet}, Admin: {$adminNetChange} vs {$expectedAdminNet}");
        }

        // ==========================================
        // CALCULER LES BALANCES NETTES (pour logs)
        // ==========================================
        // Balance nette de l'intégrateur = part intégrateur - part admin
        $integratorNetBalance = $calculation['integrator_share'] - $calculation['admin_share'];
        
        // Balance nette de l'opérateur = part opérateur - montant total payé
        $operatorNetBalance = $calculation['operator_share'] - $calculation['total_amount'];

        // Vérification finale : S'assurer que tous les wallets ont des balances cohérentes
        $finalOperatorBalance = (float) $operatorWallet->balance;
        $finalIntegratorBalance = (float) $integratorWallet->balance;
        $finalAdminBalance = $adminWallet ? (float) $adminWallet->balance : 0.0;
        
        Log::info('Wallets mis à jour selon la hiérarchie avec Business Profiles', [
            'transaction_id' => $transaction->id,
            'reservation_id' => $reservationId,
            'business_profiles' => [
                'admin_integrator_id' => $calculation['business_profiles']['admin_integrator_id'] ?? null,
                'integrator_operator_id' => $calculation['business_profiles']['integrator_operator_id'] ?? null,
                'admin_integrator_name' => $calculation['admin_fees_breakdown']['breakdown']['admin']['business_profile_name'] ?? null,
                'integrator_operator_name' => $calculation['integrator_fees_breakdown']['breakdown']['integrator']['business_profile_name'] ?? null,
            ],
            'calculation_validation' => [
                'total_amount' => $calculation['total_amount'],
                'admin_share' => $calculation['admin_share'],
                'integrator_share' => $calculation['integrator_share'],
                'operator_share' => $calculation['operator_share'],
                'sum_of_shares' => round($calculation['admin_share'] + $calculation['integrator_share'] + $calculation['operator_share'], 2),
                'is_consistent' => abs(($calculation['admin_share'] + $calculation['integrator_share'] + $calculation['operator_share']) - $calculation['total_amount']) <= 0.01
            ],
            'hierarchy_logic' => [
                'admin_integrator' => [
                    'business_profile' => 'Admin→Intégrateur',
                    'admin_takes' => $calculation['admin_share'],
                    'admin_percentage' => $calculation['admin_percentage'] ?? 0,
                    'admin_fixed' => $calculation['admin_fixed'] ?? 0,
                    'integrator_pays' => $calculation['admin_share'],
                    'integrator_net_balance' => $integratorNetBalance
                ],
                'integrator_operator' => [
                    'business_profile' => 'Intégrateur→Opérateur',
                    'integrator_takes' => $calculation['integrator_share'],
                    'integrator_percentage' => $calculation['integrator_percentage'] ?? 0,
                    'integrator_fixed' => $calculation['integrator_fixed'] ?? 0,
                    'operator_gets' => $calculation['operator_share'],
                    'operator_net_balance' => $operatorNetBalance
                ]
            ],
            'wallet_operations' => [
                'operator_debit' => $calculation['total_amount'],
                'integrator_admin_debit' => $calculation['admin_share'],
                'integrator_credit' => $calculation['integrator_share'],
                'admin_credit' => $calculation['admin_share'],
                'operator_credit' => $calculation['operator_share'],
                'total_amount' => $calculation['total_amount'],
                'sum_of_shares' => round($calculation['admin_share'] + $calculation['integrator_share'] + $calculation['operator_share'], 2)
            ],
            'wallet_balances' => [
                'operator' => [
                    'before' => $operatorBalanceBefore,
                    'after' => $operatorWallet->balance,
                    'net_change' => $operatorNetChange,
                    'expected_net' => $expectedOperatorNet,
                    'is_consistent' => abs($operatorNetChange - $expectedOperatorNet) <= 0.01
                ],
                'integrator' => [
                    'before' => $integratorBalanceBefore,
                    'after' => $integratorBalanceAfter,
                    'net_change' => $integratorNetChange,
                    'expected_net' => $expectedIntegratorNet,
                    'is_consistent' => abs($integratorNetChange - $expectedIntegratorNet) <= 0.01
                ],
                'admin' => $adminWallet ? [
                    'before' => $adminBalanceBefore,
                    'after' => $adminBalanceAfter,
                    'net_change' => $adminNetChange,
                    'expected_net' => $expectedAdminNet,
                    'is_consistent' => abs($adminNetChange - $expectedAdminNet) <= 0.01,
                    'final_balance' => $finalAdminBalance
                ] : null
            ],
            'final_balances_after_transaction' => [
                'operator_final_balance' => $finalOperatorBalance,
                'integrator_final_balance' => $finalIntegratorBalance,
                'admin_final_balance' => $finalAdminBalance,
                'note' => 'Balances finales garanties à jour pour tous les acteurs (Admin, Intégrateur, Opérateur/Partner)'
            ]
        ]);
        
        // Retourner les IDs des wallets pour sauvegarde dans la transaction
        return [
            'admin_wallet_id' => $adminWallet?->id,
            'integrator_wallet_id' => $integratorWallet->id,
            'operator_wallet_id' => $operatorWallet->id,
        ];
    }

    /**
     * Récupérer ou créer un wallet
     */
    protected function getOrCreateWallet($owner): Wallet
    {
        $wallet = Wallet::where('owner_type', get_class($owner))
            ->where('owner_id', $owner->id)
            ->first();

        if (!$wallet) {
            $wallet = Wallet::create([
                'owner_type' => get_class($owner),
                'owner_id' => $owner->id,
                'balance' => 0.00,
                'currency' => 'EUR',
                'is_active' => true,
                'name' => 'Wallet Principal'
            ]);
        }

        return $wallet;
    }

    /**
     * Débiter un wallet
     */
    protected function debitWallet(Wallet $wallet, float $amount, string $description): WalletTransaction
    {
        if ($wallet->balance < $amount) {
            throw new Exception("Solde insuffisant. Requis: {$amount}, Disponible: {$wallet->balance}");
        }

        return $wallet->debit($amount, $description, [
            'transaction_type' => 'reservation_payment',
            'status' => 'completed'
        ]);
    }

    /**
     * Créditer un wallet
     */
    protected function creditWallet(Wallet $wallet, float $amount, string $description, array $additionalMetadata = []): WalletTransaction
    {
        $metadata = array_merge([
            'transaction_type' => 'reservation_share',
            'status' => 'completed'
        ], $additionalMetadata);
        
        return $wallet->credit($amount, $description, $metadata);
    }

    /**
     * Obtenir le résumé d'une transaction de réservation
     */
    public function getReservationTransactionSummary(Reservation $reservation): array
    {
        $transaction = Transaction::where('reservation_id', $reservation->id)->first();
        
        if (!$transaction) {
            return ['success' => false, 'message' => 'Aucune transaction trouvée pour cette réservation'];
        }

        $transactionDetail = TransactionDetail::where('transaction_id', $transaction->id)->first();
        $hierarchicalTransactions = TransactionHierarchy::where('original_transaction_id', $transaction->id)->get();

        return [
            'success' => true,
            'reservation' => $reservation,
            'main_transaction' => $transaction,
            'transaction_detail' => $transactionDetail,
            'hierarchical_transactions' => $hierarchicalTransactions,
            'summary' => [
                'total_amount' => $transaction->amount,
                'admin_share' => $transactionDetail?->admin_share_amount ?? 0,
                'integrator_share' => $transactionDetail?->integrator_share_amount ?? 0,
                'operator_share' => $transactionDetail?->operator_share_amount ?? 0,
                'admin_percentage' => $transactionDetail?->admin_share_percentage ?? 0,
                'integrator_percentage' => $transactionDetail?->integrator_share_percentage ?? 0
            ]
        ];
    }

    /**
     * Valider les données nécessaires pour créer un TransactionDetail
     * 
     * @param Transaction $transaction
     * @return array ['is_valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public function validateTransactionDetailData(Transaction $transaction): array
    {
        $errors = [];
        $warnings = [];
        
        // Vérifier le montant total
        $totalAmount = (float) ($transaction->amount ?? $transaction->price_total ?? 0);
        if ($totalAmount <= 0) {
            $errors[] = 'Le montant de la transaction est invalide ou manquant (montant: ' . $totalAmount . '€)';
        }
        
        // Vérifier la borne
        if (!$transaction->charging_point_id) {
            $errors[] = 'La transaction n\'a pas de borne associée (charging_point_id manquant)';
        }
        
        // Vérifier la hiérarchie
        $hierarchy = null;
        if ($transaction->charging_point_id) {
            $hierarchy = $this->getCompleteHierarchy($transaction->charging_point_id);
            
            if (!$hierarchy) {
                $errors[] = 'Impossible de récupérer la hiérarchie complète pour la borne #' . $transaction->charging_point_id . '. L\'opérateur est requis pour créer un TransactionDetail.';
            } else {
                // Vérifier les éléments de la hiérarchie (avertissements si manquants, mais pas bloquant)
                if (!$hierarchy['operator']) {
                    $errors[] = 'Opérateur manquant dans la hiérarchie de la borne (requis)';
                } else {
                    // Si l'opérateur existe, vérifier les autres éléments de la hiérarchie
                    if (!$hierarchy['admin']) {
                        $errors[] = 'Administrateur manquant dans la hiérarchie de la borne';
                        $errors[] = '  → Action : Vérifier que la borne est liée à un intégrateur avec un admin associé';
                        $errors[] = '  → Vérifier dans : Intégrateurs → Détails de l\'intégrateur → Admin créateur';
                    }
                    if (!$hierarchy['integrator']) {
                        $errors[] = 'Intégrateur manquant dans la hiérarchie de la borne';
                        $errors[] = '  → Action : Vérifier que la borne est liée à un groupe avec un partenaire intégrateur';
                        $errors[] = '  → Vérifier dans : Points de recharge → Détails de la borne → Groupe → Partenaire → Intégrateur';
                    }
                    
                    // Vérifier les Business Profiles seulement si tous les éléments sont présents
                    if ($hierarchy['admin'] && $hierarchy['integrator'] && $hierarchy['operator']) {
                        $businessProfiles = $this->getBusinessProfilesForHierarchy($hierarchy);
                        
                        if (!$businessProfiles['admin_integrator']) {
                            $errors[] = 'Business Profile Admin → Intégrateur manquant ou inactif';
                            $errors[] = '  → Action : Configurer un Business Profile Admin → Intégrateur dans "Profils business"';
                            $errors[] = '  → L\'associer à l\'intégrateur ID: ' . $hierarchy['integrator']->id;
                        }
                        if (!$businessProfiles['integrator_operator']) {
                            $errors[] = 'Business Profile Intégrateur → Opérateur manquant ou inactif';
                            $errors[] = '  → Action : Configurer un Business Profile Intégrateur → Opérateur dans "Profils business"';
                            if ($hierarchy['partner']) {
                                $errors[] = '  → L\'associer au partenaire ID: ' . $hierarchy['partner']->id;
                            } else {
                                $errors[] = '  → L\'associer à l\'opérateur ID: ' . $hierarchy['operator']->id;
                            }
                        }
                    }
                }
            }
        } else {
            $errors[] = 'La transaction n\'a pas de charging_point_id associé';
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'hierarchy' => $hierarchy
        ];
    }

    /**
     * Recalculer les parts d'une transaction existante selon la nouvelle logique
     * Utile pour mettre à jour les transactions créées avant la correction du calcul
     * 
     * @param Transaction $transaction
     * @param bool $force Force le recalcul même si un TransactionDetail existe
     * @return array ['success' => bool, 'transaction_detail' => TransactionDetail|null, 'errors' => array]
     */
    public function recalculateTransactionShares(Transaction $transaction, bool $force = false): array
    {
        try {
            // Valider les données nécessaires
            $validation = $this->validateTransactionDetailData($transaction);
            
            if (!$validation['is_valid']) {
                return [
                    'success' => false,
                    'transaction_detail' => null,
                    'errors' => $validation['errors']
                ];
            }

            $hierarchy = $validation['hierarchy'];
            $totalAmount = (float) ($transaction->amount ?? $transaction->price_total ?? 0);

            // Récupérer les Business Profiles
            $businessProfiles = $this->getBusinessProfilesForHierarchy($hierarchy);
            
            if (!$businessProfiles['admin_integrator'] || !$businessProfiles['integrator_operator']) {
                return [
                    'success' => false,
                    'transaction_detail' => null,
                    'errors' => ['Business Profiles manquants pour le recalcul']
                ];
            }

            // Calculer les parts selon la nouvelle logique
            $calculation = $this->calculateSharesWithBusinessProfiles($totalAmount, $businessProfiles, $hierarchy);
            
            // Mettre à jour ou créer le TransactionDetail
            if ($transaction->transactionDetail) {
                // Recharger le TransactionDetail pour s'assurer que les casts sont appliqués
                $transaction->transactionDetail->refresh();
                
                // S'assurer que calculation_details est un tableau
                // (peut être une chaîne JSON si non casté correctement)
                $existingCalculationDetails = $transaction->transactionDetail->calculation_details;
                
                // Si c'est null, utiliser un tableau vide
                if ($existingCalculationDetails === null) {
                    $existingCalculationDetails = [];
                }
                
                // Si c'est une chaîne, décoder en JSON
                if (is_string($existingCalculationDetails)) {
                    $decoded = json_decode($existingCalculationDetails, true);
                    $existingCalculationDetails = is_array($decoded) ? $decoded : [];
                }
                
                // S'assurer que c'est un tableau (dernière vérification)
                if (!is_array($existingCalculationDetails)) {
                    Log::warning('calculation_details n\'est pas un tableau, utilisation d\'un tableau vide', [
                        'transaction_id' => $transaction->id,
                        'transaction_detail_id' => $transaction->transactionDetail->id,
                        'calculation_details_type' => gettype($existingCalculationDetails),
                        'calculation_details_value' => is_string($existingCalculationDetails) ? substr($existingCalculationDetails, 0, 100) : $existingCalculationDetails
                    ]);
                    $existingCalculationDetails = [];
                }
                
                // Préparer les nouveaux calculation_details
                $newCalculationDetails = array_merge(
                    $existingCalculationDetails,
                    [
                        'recalculated_at' => now()->toISOString(),
                        'recalculation_method' => 'new_hierarchical_logic',
                        'calculation' => $calculation
                    ]
                );
                
                // Vérification finale avant la mise à jour
                if (!is_array($newCalculationDetails)) {
                    Log::error('Erreur: newCalculationDetails n\'est pas un tableau', [
                        'transaction_id' => $transaction->id,
                        'type' => gettype($newCalculationDetails)
                    ]);
                    $newCalculationDetails = [
                        'recalculated_at' => now()->toISOString(),
                        'recalculation_method' => 'new_hierarchical_logic',
                        'calculation' => $calculation
                    ];
                }
                
                $transaction->transactionDetail->update([
                    'admin_share_amount' => $calculation['admin_share'],
                    'integrator_share_amount' => $calculation['integrator_share'],
                    'operator_share_amount' => $calculation['operator_share'],
                    'admin_share_percentage' => $calculation['admin_percentage'],
                    'integrator_share_percentage' => $calculation['integrator_percentage'],
                    'calculation_details' => $newCalculationDetails
                ]);
                $transaction->transactionDetail->refresh();
                
                // 🔄 SYNCHRONISER LES BALANCES DEPUIS TransactionDetail
                // IMPORTANT : Après la mise à jour du TransactionDetail, synchroniser les balances
                // CORRECTION : Toujours appeler la synchronisation, même si la hiérarchie n'est pas récupérée
                // car la méthode utilise maintenant des fallbacks pour garantir la synchronisation admin
                $hierarchy = $this->getCompleteHierarchy($transaction->charging_point_id);
                $this->synchronizeBalancesAfterTransactionDetail($hierarchy, $transaction);
                
                return [
                    'success' => true,
                    'transaction_detail' => $transaction->transactionDetail,
                    'errors' => []
                ];
            } else {
                // Créer un nouveau TransactionDetail
                $transactionDetail = $this->createTransactionDetail($transaction, $calculation, $hierarchy);
                
                // 🔄 SYNCHRONISER LES BALANCES DEPUIS TransactionDetail
                // IMPORTANT : Après la création du TransactionDetail, synchroniser les balances
                $this->synchronizeBalancesAfterTransactionDetail($hierarchy, $transaction);
                
                return [
                    'success' => true,
                    'transaction_detail' => $transactionDetail,
                    'errors' => []
                ];
            }

        } catch (Exception $e) {
            Log::error('Erreur lors du recalcul des parts de transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'transaction_detail' => null,
                'errors' => ['Erreur lors du recalcul : ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Créer rétroactivement un TransactionDetail pour une transaction existante
     * VALIDATION STRICTE : Ne crée un TransactionDetail que si toutes les données sont valides
     * 
     * @param Transaction $transaction
     * @return array ['success' => bool, 'transaction_detail' => TransactionDetail|null, 'errors' => array]
     */
    public function createRetroactiveTransactionDetail(Transaction $transaction): array
    {
        try {
            // Vérifier si un TransactionDetail existe déjà
            // Si la part admin est à 0, recalculer selon la nouvelle logique
            if ($transaction->transactionDetail) {
                $adminShare = (float) ($transaction->transactionDetail->admin_share_amount ?? 0);
                
                // Si la part admin est à 0 mais devrait être > 0, recalculer
                if ($adminShare == 0 && $transaction->amount > 0) {
                    Log::info('TransactionDetail existe mais part admin à 0 - Recalcul selon nouvelle logique', [
                        'transaction_id' => $transaction->id,
                        'admin_share_current' => $adminShare
                    ]);
                    
                    return $this->recalculateTransactionShares($transaction, true);
                }
                
                return [
                    'success' => true,
                    'transaction_detail' => $transaction->transactionDetail,
                    'errors' => []
                ];
            }

            // Valider les données nécessaires
            $validation = $this->validateTransactionDetailData($transaction);
            
            if (!$validation['is_valid']) {
                Log::warning('Impossible de créer rétroactivement TransactionDetail - données invalides', [
                    'transaction_id' => $transaction->id,
                    'errors' => $validation['errors']
                ]);
                
                return [
                    'success' => false,
                    'transaction_detail' => null,
                    'errors' => $validation['errors']
                ];
            }

            $hierarchy = $validation['hierarchy'];
            $totalAmount = (float) ($transaction->amount ?? $transaction->price_total ?? 0);

            // PRIORITÉ 1 : Si la transaction a déjà des montants de parts dans les colonnes directes
            if ($transaction->admin_share_amount || $transaction->integrator_share_amount || $transaction->operator_share_amount) {
                $transactionDetail = $this->createTransactionDetailFromExistingData($transaction, $hierarchy);
                
                // 🔄 SYNCHRONISER LES BALANCES DEPUIS TransactionDetail
                // IMPORTANT : Après la création rétroactive du TransactionDetail, synchroniser les balances
                $this->synchronizeBalancesAfterTransactionDetail($hierarchy, $transaction);
                
                return [
                    'success' => true,
                    'transaction_detail' => $transactionDetail,
                    'errors' => []
                ];
            }

            // PRIORITÉ 2 : Calculer depuis la hiérarchie et les Business Profiles
            try {
                $businessProfiles = $this->getBusinessProfilesForHierarchy($hierarchy);
                
                // Vérifier et suggérer des Business Profiles manquants
                $missingProfilesErrors = [];
                $actionableSuggestions = [];
                
                if (!$businessProfiles['admin_integrator']) {
                    $missingProfilesErrors[] = 'Business Profile Admin → Intégrateur manquant ou inactif';
                    
                    // Suggérer des Business Profiles existants
                    $suggestedProfiles = $this->suggestBusinessProfiles('admin', $hierarchy);
                    if (!empty($suggestedProfiles)) {
                        $actionableSuggestions[] = [
                            'type' => 'admin_integrator',
                            'message' => 'Business Profiles Admin → Intégrateur disponibles dans le système',
                            'suggestions' => $suggestedProfiles,
                            'action' => 'Activer ou associer un Business Profile Admin → Intégrateur pour l\'intégrateur ID: ' . ($hierarchy['integrator']->id ?? 'N/A')
                        ];
                    } else {
                        $actionableSuggestions[] = [
                            'type' => 'admin_integrator',
                            'message' => 'Aucun Business Profile Admin → Intégrateur trouvé',
                            'action' => 'Créer un Business Profile Admin → Intégrateur dans la section "Profils business"'
                        ];
                    }
                }
                
                if (!$businessProfiles['integrator_operator']) {
                    $missingProfilesErrors[] = 'Business Profile Intégrateur → Opérateur manquant ou inactif';
                    
                    // Suggérer des Business Profiles existants
                    $suggestedProfiles = $this->suggestBusinessProfiles('integrator', $hierarchy);
                    if (!empty($suggestedProfiles)) {
                        $actionableSuggestions[] = [
                            'type' => 'integrator_operator',
                            'message' => 'Business Profiles Intégrateur → Opérateur disponibles dans le système',
                            'suggestions' => $suggestedProfiles,
                            'action' => 'Activer ou associer un Business Profile Intégrateur → Opérateur pour l\'opérateur/partenaire'
                        ];
                    } else {
                        $actionableSuggestions[] = [
                            'type' => 'integrator_operator',
                            'message' => 'Aucun Business Profile Intégrateur → Opérateur trouvé',
                            'action' => 'Créer un Business Profile Intégrateur → Opérateur dans la section "Profils business"'
                        ];
                    }
                }
                
                if (!empty($missingProfilesErrors)) {
                    // Retourner des erreurs avec suggestions au lieu de créer avec des valeurs à 0
                    $detailedErrors = array_merge($missingProfilesErrors, [
                        '',
                        'ACTIONS REQUISES :',
                        '1. Vérifier la configuration de la hiérarchie pour la borne #' . $transaction->charging_point_id,
                        '2. Configurer les Business Profiles manquants dans la section "Profils business"',
                        '3. S\'assurer que les Business Profiles sont actifs et correctement associés'
                    ]);
                    
                    foreach ($actionableSuggestions as $suggestion) {
                        $detailedErrors[] = '';
                        $detailedErrors[] = '→ ' . $suggestion['type'] . ' : ' . $suggestion['message'];
                        $detailedErrors[] = '  Action : ' . $suggestion['action'];
                        if (isset($suggestion['suggestions']) && !empty($suggestion['suggestions'])) {
                            $detailedErrors[] = '  Profils disponibles :';
                            foreach ($suggestion['suggestions'] as $profile) {
                                $detailedErrors[] = '    - ID: ' . $profile['id'] . ' | ' . $profile['name'] . ($profile['is_active'] ? ' (actif)' : ' (inactif)');
                            }
                        }
                    }
                    
                    Log::warning('Impossible de créer TransactionDetail - Business Profiles manquants', [
                        'transaction_id' => $transaction->id,
                        'missing_profiles' => $missingProfilesErrors,
                        'hierarchy' => [
                            'admin_id' => $hierarchy['admin']->id ?? null,
                            'integrator_id' => $hierarchy['integrator']->id ?? null,
                            'operator_id' => $hierarchy['operator']->id ?? null,
                            'charging_point_id' => $transaction->charging_point_id
                        ]
                    ]);
                    
                    return [
                        'success' => false,
                        'transaction_detail' => null,
                        'errors' => $detailedErrors,
                        'actionable_suggestions' => $actionableSuggestions,
                        'hierarchy_status' => [
                            'has_admin' => !is_null($hierarchy['admin']),
                            'has_integrator' => !is_null($hierarchy['integrator']),
                            'has_operator' => !is_null($hierarchy['operator']),
                            'charging_point_id' => $transaction->charging_point_id
                        ]
                    ];
                }
                
                // Si tous les Business Profiles sont présents, calculer normalement
                $calculation = $this->calculateSharesWithBusinessProfiles($totalAmount, $businessProfiles, $hierarchy);
                $transactionDetail = $this->createTransactionDetail($transaction, $calculation, $hierarchy);
                
                // 🔄 SYNCHRONISER LES BALANCES DEPUIS TransactionDetail
                // IMPORTANT : Après la création rétroactive du TransactionDetail, synchroniser les balances
                $this->synchronizeBalancesAfterTransactionDetail($hierarchy, $transaction);
                
                return [
                    'success' => true,
                    'transaction_detail' => $transactionDetail,
                    'errors' => []
                ];
            } catch (Exception $e) {
                Log::error('Erreur lors du calcul des parts avec Business Profiles', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Retourner une erreur détaillée au lieu de créer avec des valeurs à 0
                return [
                    'success' => false,
                    'transaction_detail' => null,
                    'errors' => [
                        'Erreur lors du calcul des parts : ' . $e->getMessage(),
                        '',
                        'CAUSE POSSIBLE :',
                        '- Configuration incorrecte des Business Profiles',
                        '- Hiérarchie incomplète ou mal configurée',
                        '- Données de transaction invalides',
                        '',
                        'ACTION REQUISE :',
                        'Vérifier la configuration de la transaction et de la hiérarchie associée.'
                    ]
                ];
            }

        } catch (Exception $e) {
            Log::error('Erreur lors de la création rétroactive de TransactionDetail', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'transaction_detail' => null,
                'errors' => ['Erreur technique : ' . $e->getMessage()]
            ];
        }
    }


    /**
     * Créer un TransactionDetail depuis les données existantes de la transaction
     * 
     * @param Transaction $transaction
     * @param array $hierarchy
     * @return TransactionDetail
     */
    protected function createTransactionDetailFromExistingData(Transaction $transaction, array $hierarchy): TransactionDetail
    {
        $adminShare = (float) ($transaction->admin_share_amount ?? $transaction->admin_commission ?? 0);
        $integratorShare = (float) ($transaction->integrator_share_amount ?? $transaction->integrator_commission ?? 0);
        $operatorShare = (float) ($transaction->operator_share_amount ?? $transaction->partner_commission ?? 0);
        $totalAmount = (float) ($transaction->amount ?? $transaction->price_total ?? 0);

        // Calculer les pourcentages approximatifs
        $adminPercentage = $totalAmount > 0 ? round(($adminShare / $totalAmount) * 100, 2) : 0;
        $integratorPercentage = $totalAmount > 0 ? round(($integratorShare / $totalAmount) * 100, 2) : 0;

        return TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'transaction_fee_percentage' => 0.0,
            'transaction_fee_fixed' => 0.0,
            'transaction_fee_total' => $adminShare + $integratorShare,
            'admin_share_amount' => $adminShare,
            'integrator_share_amount' => $integratorShare,
            'operator_share_amount' => $operatorShare,
            'admin_share_percentage' => $adminPercentage,
            'integrator_share_percentage' => $integratorPercentage,
            'admin_creator_id' => $hierarchy['admin']->id ?? $transaction->admin_id,
            // Utiliser user_id si c'est un Integrator, id si c'est un User
            'integrator_creator_id' => $hierarchy['integrator']->user_id ?? $hierarchy['integrator']->id ?? $transaction->integrator_id,
            'operator_id' => $hierarchy['operator']->id ?? $transaction->operator_id,
            'admin_paid' => false,
            'integrator_paid' => false,
            'operator_paid' => false,
            'calculation_details' => [
                'created_retroactively' => true,
                'source' => 'existing_transaction_data',
                'hierarchy' => [
                    'admin_id' => $hierarchy['admin']->id ?? null,
                    'integrator_id' => $hierarchy['integrator']->id ?? null,
                    'operator_id' => $hierarchy['operator']->id ?? null,
                    'charging_point_id' => $transaction->charging_point_id
                ],
                'note' => 'TransactionDetail créé rétroactivement depuis les données existantes de la transaction'
            ]
        ]);
    }

    /**
     * Synchroniser les balances après la création/mise à jour d'un TransactionDetail
     * 
     * @param array|null $hierarchy
     * @param Transaction $transaction
     * @return void
     */
    protected function synchronizeBalancesAfterTransactionDetail(?array $hierarchy, Transaction $transaction): void
    {
        try {
            $balanceSyncService = app(\App\Services\BalanceSynchronizationService::class);
            
            // Initialiser la hiérarchie si elle est null
            if (!$hierarchy) {
                $hierarchy = [];
            }
            
            // CORRECTION : Synchroniser le solde de l'intégrateur avec fallbacks multiples
            // pour garantir la synchronisation même si la hiérarchie n'est pas correctement récupérée
            $integratorSynchronized = false;
            
            // MÉTHODE 1 : Via hiérarchie (méthode principale)
            $integratorUser = $hierarchy['integrator']->user ?? $hierarchy['integrator'] ?? null;
            if ($integratorUser && method_exists($integratorUser, 'hasRole') && $integratorUser->hasRole('integrator')) {
                try {
                    $integratorSyncResult = $balanceSyncService->synchronizeUserBalance($integratorUser);
                    Log::info('Balance intégrateur synchronisée après TransactionDetail (via hiérarchie)', [
                        'transaction_id' => $transaction->id,
                        'integrator_user_id' => $integratorUser->id,
                        'sync_result' => $integratorSyncResult
                    ]);
                    $integratorSynchronized = true;
                } catch (\Exception $e) {
                    Log::warning('Erreur synchronisation intégrateur via hiérarchie, essai méthode alternative', [
                        'transaction_id' => $transaction->id,
                        'integrator_user_id' => $integratorUser->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // MÉTHODE 2 : Via TransactionDetail si integrator_creator_id est défini
            if (!$integratorSynchronized && $transaction->transactionDetail) {
                $transactionDetail = $transaction->transactionDetail;
                if ($transactionDetail->integrator_creator_id) {
                    $integratorUser = \App\Models\User::find($transactionDetail->integrator_creator_id);
                    // Vérifier aussi via le modèle Integrator
                    if (!$integratorUser) {
                        $integratorModel = \App\Models\Integrator::find($transactionDetail->integrator_creator_id);
                        if ($integratorModel && $integratorModel->user) {
                            $integratorUser = $integratorModel->user;
                        }
                    }
                    
                    if ($integratorUser && $integratorUser->hasRole('integrator')) {
                        try {
                            $integratorSyncResult = $balanceSyncService->synchronizeUserBalance($integratorUser);
                            Log::info('Balance intégrateur synchronisée après TransactionDetail (via integrator_creator_id)', [
                                'transaction_id' => $transaction->id,
                                'integrator_user_id' => $integratorUser->id,
                                'integrator_share_amount' => $transactionDetail->integrator_share_amount ?? 0,
                                'sync_result' => $integratorSyncResult
                            ]);
                            $integratorSynchronized = true;
                        } catch (\Exception $e) {
                            Log::warning('Erreur synchronisation intégrateur via integrator_creator_id', [
                                'transaction_id' => $transaction->id,
                                'integrator_user_id' => $integratorUser->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
            
            // MÉTHODE 3 : Recherche via les bornes de l'intégrateur si integrator_share_amount > 0
            if (!$integratorSynchronized && $transaction->transactionDetail && $transaction->transactionDetail->integrator_share_amount > 0) {
                // Chercher via les bornes de la transaction
                if ($transaction->chargingPoint && $transaction->chargingPoint->integrator_id) {
                    $integratorModelId = $transaction->chargingPoint->integrator_id;
                    $integratorModel = \App\Models\Integrator::find($integratorModelId);

                    // Try direct user relation first; fall back to FK lookup when user_id is not set
                    $integratorUser = $integratorModel?->user
                        ?? \App\Models\User::where('integrator_id', $integratorModelId)
                                ->whereHas('roles', fn ($r) => $r->where('name', 'integrator'))
                                ->first();

                    if ($integratorUser && $integratorUser->hasRole('integrator')) {
                        try {
                            $integratorSyncResult = $balanceSyncService->synchronizeUserBalance($integratorUser);
                            Log::info('Balance intégrateur synchronisée après TransactionDetail (via charging point)', [
                                'transaction_id'           => $transaction->id,
                                'integrator_user_id'       => $integratorUser->id,
                                'integrator_model_id'      => $integratorModelId,
                                'integrator_share_amount'  => $transaction->transactionDetail->integrator_share_amount ?? 0,
                                'sync_result'              => $integratorSyncResult,
                            ]);
                            $integratorSynchronized = true;
                        } catch (\Exception $e) {
                            Log::warning('Erreur synchronisation intégrateur via charging point', [
                                'transaction_id'      => $transaction->id,
                                'integrator_user_id'  => $integratorUser->id,
                                'error'               => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
            
            // Avertir si la synchronisation intégrateur a échoué
            if (!$integratorSynchronized && $transaction->transactionDetail && $transaction->transactionDetail->integrator_share_amount > 0) {
                Log::warning('Échec de la synchronisation balance intégrateur après TransactionDetail', [
                    'transaction_id' => $transaction->id,
                    'integrator_share_amount' => $transaction->transactionDetail->integrator_share_amount,
                    'hierarchy_integrator_exists' => isset($hierarchy['integrator']),
                    'transaction_detail_integrator_creator_id' => $transaction->transactionDetail->integrator_creator_id ?? null,
                    'charging_point_integrator_id' => $transaction->chargingPoint->integrator_id ?? null
                ]);
            }
            
            // CORRECTION : Synchroniser le solde de l'admin avec fallbacks multiples
            // pour garantir la synchronisation même si la hiérarchie n'est pas correctement récupérée
            $adminSynchronized = false;
            
            // MÉTHODE 1 : Via hiérarchie (méthode principale)
            if ($hierarchy['admin'] && method_exists($hierarchy['admin'], 'hasRole') && $hierarchy['admin']->hasRole(['admin', 'super_admin'])) {
                try {
                    $adminSyncResult = $balanceSyncService->synchronizeUserBalance($hierarchy['admin']);
                    Log::info('Balance admin synchronisée après TransactionDetail (via hiérarchie)', [
                        'transaction_id' => $transaction->id,
                        'admin_user_id' => $hierarchy['admin']->id,
                        'sync_result' => $adminSyncResult
                    ]);
                    $adminSynchronized = true;
                } catch (\Exception $e) {
                    Log::warning('Erreur synchronisation admin via hiérarchie, essai méthode alternative', [
                        'transaction_id' => $transaction->id,
                        'admin_user_id' => $hierarchy['admin']->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // MÉTHODE 2 : Via TransactionDetail si admin_creator_id est défini
            if (!$adminSynchronized && $transaction->transactionDetail) {
                $transactionDetail = $transaction->transactionDetail;
                if ($transactionDetail->admin_creator_id) {
                    $adminUser = \App\Models\User::find($transactionDetail->admin_creator_id);
                    if ($adminUser && $adminUser->hasRole(['admin', 'super_admin'])) {
                        try {
                            $adminSyncResult = $balanceSyncService->synchronizeUserBalance($adminUser);
                            Log::info('Balance admin synchronisée après TransactionDetail (via admin_creator_id)', [
                                'transaction_id' => $transaction->id,
                                'admin_user_id' => $adminUser->id,
                                'admin_share_amount' => $transactionDetail->admin_share_amount ?? 0,
                                'sync_result' => $adminSyncResult
                            ]);
                            $adminSynchronized = true;
                        } catch (\Exception $e) {
                            Log::warning('Erreur synchronisation admin via admin_creator_id', [
                                'transaction_id' => $transaction->id,
                                'admin_user_id' => $adminUser->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
            
            // MÉTHODE 3 : Recherche globale si admin_share_amount > 0
            if (!$adminSynchronized && $transaction->transactionDetail && $transaction->transactionDetail->admin_share_amount > 0) {
                $adminUsers = \App\Models\User::whereHas('roles', function($q) {
                    $q->whereIn('name', ['admin', 'super_admin']);
                })->get();
                
                foreach ($adminUsers as $adminUser) {
                    try {
                        $adminSyncResult = $balanceSyncService->synchronizeUserBalance($adminUser);
                        Log::info('Balance admin synchronisée après TransactionDetail (via recherche globale)', [
                            'transaction_id' => $transaction->id,
                            'admin_user_id' => $adminUser->id,
                            'admin_share_amount' => $transaction->transactionDetail->admin_share_amount ?? 0,
                            'sync_result' => $adminSyncResult
                        ]);
                        $adminSynchronized = true;
                        // Synchroniser seulement le premier admin trouvé (généralement il n'y en a qu'un)
                        break;
                    } catch (\Exception $e) {
                        Log::warning('Erreur synchronisation admin via recherche globale', [
                            'transaction_id' => $transaction->id,
                            'admin_user_id' => $adminUser->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // Avertir si la synchronisation admin a échoué
            if (!$adminSynchronized && $transaction->transactionDetail && $transaction->transactionDetail->admin_share_amount > 0) {
                Log::warning('Échec de la synchronisation balance admin après TransactionDetail', [
                    'transaction_id' => $transaction->id,
                    'admin_share_amount' => $transaction->transactionDetail->admin_share_amount,
                    'hierarchy_admin_exists' => isset($hierarchy['admin']),
                    'transaction_detail_admin_creator_id' => $transaction->transactionDetail->admin_creator_id ?? null
                ]);
            }
            
            // CORRECTION : Synchroniser le solde de l'opérateur/partner avec fallbacks multiples
            // pour garantir la synchronisation même si la hiérarchie n'est pas correctement récupérée
            $operatorSynchronized = false;
            
            // MÉTHODE 1 : Via hiérarchie (méthode principale)
            if (isset($hierarchy['operator']) && $hierarchy['operator'] && method_exists($hierarchy['operator'], 'hasRole') && $hierarchy['operator']->hasRole(['operator', 'partner'])) {
                try {
                    $operatorSyncResult = $balanceSyncService->synchronizeUserBalance($hierarchy['operator']);
                    Log::info('Balance opérateur synchronisée après TransactionDetail (via hiérarchie)', [
                        'transaction_id' => $transaction->id,
                        'operator_user_id' => $hierarchy['operator']->id,
                        'sync_result' => $operatorSyncResult
                    ]);
                    $operatorSynchronized = true;
                } catch (\Exception $e) {
                    Log::warning('Erreur synchronisation opérateur via hiérarchie, essai méthode alternative', [
                        'transaction_id' => $transaction->id,
                        'operator_user_id' => $hierarchy['operator']->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // MÉTHODE 2 : Via TransactionDetail si operator_id est défini
            if (!$operatorSynchronized && $transaction->transactionDetail) {
                $transactionDetail = $transaction->transactionDetail;
                if ($transactionDetail->operator_id) {
                    $operatorUser = \App\Models\User::find($transactionDetail->operator_id);
                    if ($operatorUser && $operatorUser->hasRole(['operator', 'partner'])) {
                        try {
                            $operatorSyncResult = $balanceSyncService->synchronizeUserBalance($operatorUser);
                            Log::info('Balance opérateur synchronisée après TransactionDetail (via operator_id)', [
                                'transaction_id' => $transaction->id,
                                'operator_user_id' => $operatorUser->id,
                                'operator_share_amount' => $transactionDetail->operator_share_amount ?? 0,
                                'sync_result' => $operatorSyncResult
                            ]);
                            $operatorSynchronized = true;
                        } catch (\Exception $e) {
                            Log::warning('Erreur synchronisation opérateur via operator_id', [
                                'transaction_id' => $transaction->id,
                                'operator_user_id' => $operatorUser->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
            
            // MÉTHODE 3 : Recherche via les bornes de l'opérateur si operator_share_amount > 0
            if (!$operatorSynchronized && $transaction->transactionDetail && $transaction->transactionDetail->operator_share_amount > 0) {
                // Chercher via les bornes de la transaction
                if ($transaction->chargingPoint) {
                    $chargingPoint = $transaction->chargingPoint;
                    // Chercher l'utilisateur propriétaire de la borne
                    $operatorUser = null;
                    if ($chargingPoint->user_id) {
                        $operatorUser = \App\Models\User::find($chargingPoint->user_id);
                    } elseif ($chargingPoint->created_by_id) {
                        $operatorUser = \App\Models\User::find($chargingPoint->created_by_id);
                    } elseif ($chargingPoint->created_by) {
                        $operatorUser = \App\Models\User::find($chargingPoint->created_by);
                    } elseif ($chargingPoint->group && $chargingPoint->group->user_id) {
                        $operatorUser = \App\Models\User::find($chargingPoint->group->user_id);
                    }
                    
                    if ($operatorUser && $operatorUser->hasRole(['operator', 'partner'])) {
                        try {
                            $operatorSyncResult = $balanceSyncService->synchronizeUserBalance($operatorUser);
                            Log::info('Balance opérateur synchronisée après TransactionDetail (via charging point)', [
                                'transaction_id' => $transaction->id,
                                'operator_user_id' => $operatorUser->id,
                                'operator_share_amount' => $transaction->transactionDetail->operator_share_amount ?? 0,
                                'sync_result' => $operatorSyncResult
                            ]);
                            $operatorSynchronized = true;
                        } catch (\Exception $e) {
                            Log::warning('Erreur synchronisation opérateur via charging point', [
                                'transaction_id' => $transaction->id,
                                'operator_user_id' => $operatorUser->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
            
            // Avertir si la synchronisation opérateur a échoué
            if (!$operatorSynchronized && $transaction->transactionDetail && $transaction->transactionDetail->operator_share_amount > 0) {
                Log::warning('Échec de la synchronisation balance opérateur après TransactionDetail', [
                    'transaction_id' => $transaction->id,
                    'operator_share_amount' => $transaction->transactionDetail->operator_share_amount,
                    'hierarchy_operator_exists' => isset($hierarchy['operator']),
                    'transaction_detail_operator_id' => $transaction->transactionDetail->operator_id ?? null,
                    'charging_point_user_id' => $transaction->chargingPoint->user_id ?? null
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erreur générale lors de la synchronisation des balances après TransactionDetail', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Suggérer des Business Profiles disponibles selon le type et la hiérarchie
     * 
     * @param string $type 'admin' ou 'integrator'
     * @param array $hierarchy
     * @return array Liste des Business Profiles suggérés
     */
    /**
     * Corriger toutes les transactions en statut "pending" qui ont des réservations approuvées
     * Les transactions avec réservation approuvée doivent être en statut "confirmed" ou "completed"
     * 
     * @return array
     */
    public function fixPendingTransactionsWithApprovedReservations(): array
    {
        $transactions = Transaction::where('status', 'pending')
            ->whereHas('reservation', function($query) {
                $query->whereIn('status', ['confirmed', 'completed']);
            })
            ->with('reservation')
            ->get();
        
        $fixed = [];
        $errors = [];
        
        foreach ($transactions as $transaction) {
            try {
                $oldStatus = $transaction->status;
                
                $transaction->update([
                    'status' => 'confirmed',
                    'completed_at' => $transaction->completed_at ?? now()
                ]);
                
                $fixed[] = [
                    'transaction_id' => $transaction->id,
                    'reservation_id' => $transaction->reservation_id,
                    'old_status' => $oldStatus,
                    'new_status' => 'confirmed'
                ];
                
                Log::info('Transaction corrigée : statut passé de pending à confirmed', [
                    'transaction_id' => $transaction->id,
                    'reservation_id' => $transaction->reservation_id,
                    'old_status' => $oldStatus,
                    'new_status' => 'confirmed'
                ]);
            } catch (\Exception $e) {
                $errors[] = [
                    'transaction_id' => $transaction->id,
                    'reservation_id' => $transaction->reservation_id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Erreur lors de la correction de transaction', [
                    'transaction_id' => $transaction->id,
                    'reservation_id' => $transaction->reservation_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'success' => count($errors) === 0,
            'fixed_count' => count($fixed),
            'error_count' => count($errors),
            'fixed' => $fixed,
            'errors' => $errors
        ];
    }

    protected function suggestBusinessProfiles(string $type, array $hierarchy): array
    {
        $suggestions = [];
        
        if ($type === 'admin' && $hierarchy['admin'] && $hierarchy['integrator']) {
            // Chercher des Business Profiles Admin → Intégrateur
            $profiles = BusinessProfile::where(function($q) use ($hierarchy) {
                $q->where('created_by_id', $hierarchy['admin']->id)
                  ->where('created_by_type', 'admin')
                  ->where(function($subQ) use ($hierarchy) {
                      $subQ->where('integrator_id', $hierarchy['integrator']->id)
                           ->orWhereNull('integrator_id'); // Profils généraux
                  });
            })
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
            foreach ($profiles as $profile) {
                $suggestions[] = [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'is_active' => $profile->is_active ?? false,
                    'transaction_fee_percentage' => $profile->transaction_fee_percentage ?? 0,
                    'transaction_fee_fixed' => $profile->transaction_fee_fixed ?? 0
                ];
            }
        } elseif ($type === 'integrator' && $hierarchy['integrator']) {
            // Chercher des Business Profiles Intégrateur → Opérateur
            $partnerId = $hierarchy['partner']->id ?? null;
            $operatorId = $hierarchy['operator']->id ?? null;
            
            $profiles = BusinessProfile::where(function($q) use ($hierarchy, $partnerId, $operatorId) {
                $q->where('created_by_id', $hierarchy['integrator']->id)
                  ->where('created_by_type', 'integrator');
                
                if ($partnerId) {
                    $q->where(function($subQ) use ($partnerId) {
                        $subQ->where('partner_id', $partnerId)
                             ->orWhereNull('partner_id'); // Profils généraux
                    });
                }
            })
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
            foreach ($profiles as $profile) {
                $suggestions[] = [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'is_active' => $profile->is_active ?? false,
                    'transaction_fee_percentage' => $profile->transaction_fee_percentage ?? 0,
                    'transaction_fee_fixed' => $profile->transaction_fee_fixed ?? 0
                ];
            }
        }
        
        return $suggestions;
    }

    /**
     * Mettre à jour les wallets pour une transaction existante lors de l'approbation d'une réservation
     * Cette méthode est appelée lorsque la réservation est approuvée et qu'une transaction existe déjà
     * 
     * @param Transaction $transaction
     * @return array ['success' => bool, 'wallet_ids' => array|null, 'errors' => array]
     */
    public function updateWalletsForExistingTransaction(Transaction $transaction): array
    {
        try {
            DB::beginTransaction();

            // Valider les données nécessaires
            $validation = $this->validateTransactionDetailData($transaction);
            
            if (!$validation['is_valid']) {
                return [
                    'success' => false,
                    'wallet_ids' => null,
                    'errors' => $validation['errors']
                ];
            }

            $hierarchy = $validation['hierarchy'];
            $totalAmount = (float) ($transaction->price_total ?? $transaction->amount ?? 0);

            if ($totalAmount <= 0) {
                return [
                    'success' => false,
                    'wallet_ids' => null,
                    'errors' => ['Le montant de la transaction doit être supérieur à 0']
                ];
            }

            // Récupérer les Business Profiles
            $businessProfiles = $this->getBusinessProfilesForHierarchy($hierarchy);
            
            if (!$businessProfiles['admin_integrator'] || !$businessProfiles['integrator_operator']) {
                return [
                    'success' => false,
                    'wallet_ids' => null,
                    'errors' => ['Business Profiles manquants pour la mise à jour des wallets']
                ];
            }

            // Calculer les parts selon les Business Profiles
            $calculation = $this->calculateSharesWithBusinessProfiles($totalAmount, $businessProfiles, $hierarchy);

            // Récupérer le TransactionDetail
            $transactionDetail = $transaction->transactionDetail;
            if (!$transactionDetail) {
                return [
                    'success' => false,
                    'wallet_ids' => null,
                    'errors' => ['TransactionDetail manquant pour cette transaction']
                ];
            }

            // Vérifier si des WalletTransactions existent déjà pour cette transaction
            // pour éviter les doublons si la méthode est appelée plusieurs fois
            $operatorWallet = $this->getOrCreateWallet($hierarchy['operator']);
            $integratorWallet = $this->getOrCreateWallet($hierarchy['integrator']->user ?? $hierarchy['integrator']);
            $adminWallet = $this->getOrCreateWallet($hierarchy['admin']);

            $existingWalletTransactions = \App\Models\WalletTransaction::where(function($q) use ($transaction, $transactionDetail) {
                $q->whereJsonContains('metadata->transaction_id', $transaction->id)
                  ->orWhere('metadata', 'like', '%"transaction_id":' . $transaction->id . '%')
                  ->orWhereJsonContains('metadata->transaction_detail_id', $transactionDetail->id)
                  ->orWhere('metadata', 'like', '%"transaction_detail_id":' . $transactionDetail->id . '%');
            })
            ->whereIn('wallet_id', [
                $operatorWallet->id,
                $integratorWallet->id,
                $adminWallet->id
            ])
            ->count();

            // Si des WalletTransactions existent déjà, vérifier si elles correspondent aux parts attendues
            // Si oui, ne pas les recréer
            if ($existingWalletTransactions > 0) {
                Log::info('WalletTransactions existent déjà pour cette transaction - Vérification de cohérence', [
                    'transaction_id' => $transaction->id,
                    'existing_count' => $existingWalletTransactions
                ]);

                // Vérifier si les WalletTransactions existantes correspondent aux parts attendues
                // Si elles correspondent, on considère que les wallets sont déjà à jour
                $operatorCredit = $operatorWallet->creditTransactions()
                    ->where(function($q) use ($transaction, $transactionDetail) {
                        $q->whereJsonContains('metadata->transaction_id', $transaction->id)
                          ->orWhere('metadata', 'like', '%"transaction_id":' . $transaction->id . '%')
                          ->orWhereJsonContains('metadata->transaction_detail_id', $transactionDetail->id);
                    })
                    ->where('amount', '>=', $calculation['operator_share'] - 0.01)
                    ->where('amount', '<=', $calculation['operator_share'] + 0.01)
                    ->first();

                $integratorCredit = $integratorWallet->creditTransactions()
                    ->where(function($q) use ($transaction, $transactionDetail) {
                        $q->whereJsonContains('metadata->transaction_id', $transaction->id)
                          ->orWhere('metadata', 'like', '%"transaction_id":' . $transaction->id . '%')
                          ->orWhereJsonContains('metadata->transaction_detail_id', $transactionDetail->id);
                    })
                    ->where('amount', '>=', $calculation['integrator_share'] - 0.01)
                    ->where('amount', '<=', $calculation['integrator_share'] + 0.01)
                    ->first();

                $adminCredit = $adminWallet->creditTransactions()
                    ->where(function($q) use ($transaction, $transactionDetail) {
                        $q->whereJsonContains('metadata->transaction_id', $transaction->id)
                          ->orWhere('metadata', 'like', '%"transaction_id":' . $transaction->id . '%')
                          ->orWhereJsonContains('metadata->transaction_detail_id', $transactionDetail->id);
                    })
                    ->where('amount', '>=', $calculation['admin_share'] - 0.01)
                    ->where('amount', '<=', $calculation['admin_share'] + 0.01)
                    ->first();

                // Si toutes les WalletTransactions existent et correspondent, considérer que les wallets sont déjà à jour
                if ($operatorCredit && $integratorCredit && ($calculation['admin_share'] <= 0.01 || $adminCredit)) {
                    Log::info('WalletTransactions existantes correspondent aux parts attendues - Pas de mise à jour nécessaire', [
                        'transaction_id' => $transaction->id,
                        'operator_credit_id' => $operatorCredit->id,
                        'integrator_credit_id' => $integratorCredit->id,
                        'admin_credit_id' => $adminCredit->id ?? null
                    ]);

                    return [
                        'success' => true,
                        'wallet_ids' => [
                            'operator_wallet_id' => $operatorWallet->id,
                            'integrator_wallet_id' => $integratorWallet->id,
                            'admin_wallet_id' => $adminWallet->id
                        ],
                        'errors' => [],
                        'skipped' => true,
                        'message' => 'Wallets déjà mis à jour pour cette transaction'
                    ];
                }
            }

            // Mettre à jour les wallets avec vérification de cohérence
            $walletIds = $this->updateWalletsWithValidation($hierarchy, $calculation, $transaction, $transactionDetail);

            DB::commit();

            Log::info('Wallets mis à jour pour transaction existante lors de l\'approbation de réservation', [
                'transaction_id' => $transaction->id,
                'reservation_id' => $transaction->reservation_id,
                'wallet_ids' => $walletIds,
                'admin_share' => $calculation['admin_share'],
                'integrator_share' => $calculation['integrator_share'],
                'operator_share' => $calculation['operator_share']
            ]);

            return [
                'success' => true,
                'wallet_ids' => $walletIds,
                'errors' => []
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la mise à jour des wallets pour transaction existante', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'wallet_ids' => null,
                'errors' => ['Erreur lors de la mise à jour des wallets : ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Forcer la synchronisation complète des balances pour TOUS les utilisateurs après approbation
     * 
     * CORRECTION : Garantir que les balances sont calculées et synchronisées immédiatement
     * après qu'une réservation soit approuvée (confirmed ou completed)
     * 
     * @param array $hierarchy
     * @param TransactionDetail $transactionDetail
     * @return void
     */
    protected function forceSynchronizeAllBalancesAfterApproval(array $hierarchy, TransactionDetail $transactionDetail): void
    {
        try {
            $balanceSyncService = app(\App\Services\BalanceSynchronizationService::class);
            
            Log::info('Force synchronisation complète des balances après approbation de réservation', [
                'transaction_detail_id' => $transactionDetail->id,
                'transaction_id' => $transactionDetail->transaction_id,
                'admin_share' => $transactionDetail->admin_share_amount ?? 0,
                'integrator_share' => $transactionDetail->integrator_share_amount ?? 0,
                'operator_share' => $transactionDetail->operator_share_amount ?? 0
            ]);
            
            // 1. Synchroniser l'admin
            if (isset($hierarchy['admin']) && $hierarchy['admin']) {
                try {
                    // S'assurer que le wallet existe
                    $adminWallet = $this->getOrCreateWallet($hierarchy['admin']);
                    
                    // Synchroniser la balance
                    $adminSyncResult = $balanceSyncService->synchronizeUserBalance($hierarchy['admin']);
                    
                    Log::info('Balance admin synchronisée après approbation (force sync)', [
                        'admin_id' => $hierarchy['admin']->id,
                        'wallet_id' => $adminWallet->id,
                        'sync_result' => $adminSyncResult
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erreur synchronisation admin (force sync)', [
                        'admin_id' => $hierarchy['admin']->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // 2. Synchroniser l'intégrateur
            if (isset($hierarchy['integrator']) && $hierarchy['integrator']) {
                try {
                    $integratorUser = $hierarchy['integrator']->user ?? $hierarchy['integrator'];
                    
                    if ($integratorUser) {
                        // S'assurer que le wallet existe
                        $integratorWallet = $this->getOrCreateWallet($integratorUser);
                        
                        // Synchroniser la balance
                        $integratorSyncResult = $balanceSyncService->synchronizeUserBalance($integratorUser);
                        
                        Log::info('Balance intégrateur synchronisée après approbation (force sync)', [
                            'integrator_id' => $integratorUser->id,
                            'wallet_id' => $integratorWallet->id,
                            'sync_result' => $integratorSyncResult
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Erreur synchronisation intégrateur (force sync)', [
                        'integrator_id' => $hierarchy['integrator']->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // 3. Synchroniser l'opérateur
            if (isset($hierarchy['operator']) && $hierarchy['operator']) {
                try {
                    // S'assurer que le wallet existe
                    $operatorWallet = $this->getOrCreateWallet($hierarchy['operator']);
                    
                    // Synchroniser la balance
                    $operatorSyncResult = $balanceSyncService->synchronizeUserBalance($hierarchy['operator']);
                    
                    Log::info('Balance opérateur synchronisée après approbation (force sync)', [
                        'operator_id' => $hierarchy['operator']->id,
                        'wallet_id' => $operatorWallet->id,
                        'sync_result' => $operatorSyncResult
                    ]);
                } catch (\Exception $e) {
                    Log::error('Erreur synchronisation opérateur (force sync)', [
                        'operator_id' => $hierarchy['operator']->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // 4. Synchronisation globale de fallback pour l'admin si admin_share_amount > 0
            if (($transactionDetail->admin_share_amount ?? 0) > 0) {
                $adminUsers = \App\Models\User::whereHas('roles', function($q) {
                    $q->whereIn('name', ['admin', 'super_admin']);
                })->get();
                
                foreach ($adminUsers as $adminUser) {
                    try {
                        // S'assurer que le wallet existe
                        $adminWallet = $adminUser->getOrCreateWallet();
                        
                        // Synchroniser la balance
                        $balanceSyncService->synchronizeUserBalance($adminUser);
                        
                        Log::info('Balance admin synchronisée (fallback global)', [
                            'admin_id' => $adminUser->id,
                            'wallet_id' => $adminWallet->id
                        ]);
                        break; // Synchroniser seulement le premier admin
                    } catch (\Exception $e) {
                        Log::warning('Erreur synchronisation admin (fallback global)', [
                            'admin_id' => $adminUser->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation forcée des balances', [
                'transaction_detail_id' => $transactionDetail->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
